<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AiContentService
{
    public function __construct(private readonly ConditionMentorPromptManifest $mentorManifest)
    {
    }

    public function summarizeTurn(Region $region, CarbonImmutable $windowStart, CarbonImmutable $windowEnd, ?User $requestedBy = null): string
    {
        $prompt = $this->buildTurnSummaryPrompt($region, $windowStart, $windowEnd);

        $request = $this->storeRequest(
            requestType: 'summary',
            context: $region,
            prompt: $prompt,
            meta: [
                'window_start' => $windowStart->toIso8601String(),
                'window_end' => $windowEnd->toIso8601String(),
            ],
            requestedBy: $requestedBy,
        );

        return $this->dispatch($request, $prompt, config('ai.prompts.turn_summary.system'));
    }

    public function delegateRegionToAi(Region $region, ?User $requestedBy = null, ?string $focus = null): array
    {
        $prompt = $this->buildDmTakeoverPrompt($region, $focus);

        $request = $this->storeRequest(
            requestType: 'dm_takeover',
            context: $region,
            prompt: $prompt,
            meta: array_filter([
                'focus' => $focus,
            ]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $prompt, config('ai.prompts.dm_takeover.system'));

        $region->forceFill([
            'ai_controlled' => true,
            'dungeon_master_id' => null,
            'ai_delegate_summary' => $response,
        ])->save();

        return [
            'request' => $request->fresh(),
            'plan' => $response,
        ];
    }

    public function npcDialogue(CampaignSession $session, string $npcName, string $prompt, ?User $requestedBy = null, ?string $tone = null): array
    {
        $fullPrompt = $this->buildNpcPrompt($session, $npcName, $prompt, $tone);

        $request = $this->storeRequest(
            requestType: 'npc_dialogue',
            context: $session,
            prompt: $fullPrompt,
            meta: array_filter([
                'npc_name' => $npcName,
                'tone' => $tone,
            ]),
            requestedBy: $requestedBy,
        );

        $reply = $this->dispatch($request, $fullPrompt, config('ai.prompts.npc_dialogue.system'));

        return [
            'request' => $request->fresh(),
            'reply' => $reply,
        ];
    }

    /**
     * @param  array<string, mixed>  $focus
     */
    public function mentorBriefing(Group $group, array $focus, ?User $requestedBy = null): array
    {
        $prompt = $this->buildMentorBriefingPrompt($group, $focus);

        $request = $this->storeRequest(
            requestType: 'mentor_briefing',
            context: $group,
            prompt: $prompt,
            meta: ['focus' => $focus],
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $prompt, config('ai.prompts.mentor_briefing.system'));

        return [
            'request' => $request->fresh(),
            'briefing' => $response,
        ];
    }

    protected function dispatch(AiRequest $request, string $prompt, ?string $systemPrompt = null): string
    {
        try {
            $messages = [];

            if ($systemPrompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $prompt,
            ];

            $payload = [
                'model' => config('ai.ollama.model'),
                'messages' => $messages,
                'stream' => false,
            ];

            $response = Http::baseUrl(rtrim((string) config('ai.ollama.base_url'), '/'))
                ->timeout((int) config('ai.ollama.timeout', 30))
                ->acceptJson()
                ->post('/api/chat', $payload);

            if ($response->failed()) {
                throw new \RuntimeException($response->body());
            }

            $data = $response->json();
            $content = trim(Arr::get($data, 'message.content', ''));

            if ($content === '') {
                $content = $this->fallbackText($request->request_type, $prompt);
            }

            $request->markCompleted($content, $data ?? []);

            return $content;
        } catch (Throwable $exception) {
            $request->markFailed($exception->getMessage());
            Log::warning('AI request failed', [
                'request_id' => $request->id,
                'type' => $request->request_type,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackText($request->request_type, $prompt);
        }
    }

    protected function storeRequest(string $requestType, Model $context, string $prompt, array $meta = [], ?User $requestedBy = null): AiRequest
    {
        /** @var AiRequest $request */
        $request = AiRequest::query()->create([
            'request_type' => $requestType,
            'context_type' => $context::class,
            'context_id' => $context->getKey(),
            'prompt' => $prompt,
            'meta' => $meta,
            'status' => AiRequest::STATUS_PENDING,
            'provider' => 'ollama',
            'model' => config('ai.ollama.model'),
            'created_by' => $requestedBy?->getAuthIdentifier(),
        ]);

        return $request;
    }

    protected function buildTurnSummaryPrompt(Region $region, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): string
    {
        $lore = trim($region->summary ?: $region->description ?: '');

        $intro = sprintf(
            '%s in the %s collective needs a chronicle for the window %s to %s (UTC).',
            $region->name,
            optional($region->group)->name ?? 'campaign',
            $windowStart->toIso8601String(),
            $windowEnd->toIso8601String()
        );

        if ($lore !== '') {
            $intro .= ' Existing lore: '.$lore;
        }

        $recentTurns = $region->turns()
            ->latest('number')
            ->take(3)
            ->pluck('summary')
            ->filter()
            ->all();

        if (! empty($recentTurns)) {
            $intro .= '\nRecent chronicles:\n- '.implode("\n- ", array_map(fn ($entry) => Str::limit($entry, 180), $recentTurns));
        }

        return $intro;
    }

    protected function buildDmTakeoverPrompt(Region $region, ?string $focus = null): string
    {
        $lines = [];
        $lines[] = sprintf('Region: %s (group: %s)', $region->name, optional($region->group)->name ?? 'Unknown group');

        if ($region->summary) {
            $lines[] = 'Summary: '.$region->summary;
        }

        if ($region->description) {
            $lines[] = 'Description: '.Str::limit($region->description, 400);
        }

        if ($focus) {
            $lines[] = 'Special requests: '.$focus;
        }

        return implode("\n", $lines);
    }

    protected function buildNpcPrompt(CampaignSession $session, string $npcName, string $prompt, ?string $tone = null): string
    {
        $campaign = $session->campaign;

        $lines = [];
        $lines[] = sprintf('Campaign: %s', $campaign->title);
        $lines[] = sprintf('Session: %s', $session->title);
        $lines[] = sprintf('NPC name: %s', $npcName);

        if ($tone) {
            $lines[] = 'Desired tone: '.$tone;
        }

        if ($session->summary) {
            $lines[] = 'Session summary: '.Str::limit($session->summary, 400);
        }

        if ($session->agenda) {
            $lines[] = 'Agenda: '.Str::limit($session->agenda, 250);
        }

        $lines[] = 'Player prompt: '.$prompt;

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $focus
     */
    protected function buildMentorBriefingPrompt(Group $group, array $focus): string
    {
        $manifest = $this->mentorManifest->mentorBriefing();
        $sections = $this->mentorManifest->sections($manifest);
        $toneTags = $this->mentorManifest->toneTags($manifest);

        $lines = [];
        $lines[] = sprintf('Group: %s (ID %d)', $group->name, $group->id);

        $intro = Arr::get($manifest, 'intro');

        if (is_string($intro) && $intro !== '') {
            $lines[] = $intro;
        }

        if ($toneTags !== []) {
            $lines[] = 'Tone tags: '.implode(', ', $toneTags);
        }

        $critical = Arr::get($focus, 'critical_conditions', []);
        $unacknowledged = Arr::get($focus, 'unacknowledged_tokens', []);
        $recurring = Arr::get($focus, 'recurring_conditions', []);

        $focusIncluded = false;

        if ($critical !== []) {
            $focusIncluded = true;
            $lines = array_merge(
                $lines,
                $this->renderManifestSection($sections['critical_conditions'] ?? [], $critical)
            );
        }

        if ($unacknowledged !== []) {
            $focusIncluded = true;
            $lines = array_merge(
                $lines,
                $this->renderManifestSection($sections['unacknowledged_tokens'] ?? [], $unacknowledged)
            );
        }

        if ($recurring !== []) {
            $focusIncluded = true;
            $lines = array_merge(
                $lines,
                $this->renderManifestSection($sections['recurring_conditions'] ?? [], $recurring)
            );
        }

        if (! $focusIncluded) {
            $fallback = Arr::get($manifest, 'fallback');
            $lines[] = is_string($fallback) && $fallback !== ''
                ? $fallback
                : 'No urgent conditions detected; offer congratulations and light scouting advice.';
        }

        $closing = Arr::get($manifest, 'closing');

        if (is_string($closing) && $closing !== '') {
            $lines[] = $closing;
        }

        return implode("\n", array_filter($lines, fn ($line) => $line !== ''));
    }

    /**
     * @param  array<int, string>  $entries
     * @return array<int, string>
     */
    protected function renderManifestSection(array $definition, array $entries): array
    {
        $lines = [];

        $heading = Arr::get($definition, 'heading', 'Focus items:');
        $notes = Arr::get($definition, 'narrative_notes');
        $moderation = Arr::get($definition, 'moderation');
        $tone = Arr::get($definition, 'tone');

        if (is_string($heading) && $heading !== '') {
            $lines[] = $heading;
        }

        if (is_string($tone) && $tone !== '') {
            $lines[] = 'Suggested tone: '.$tone;
        }

        foreach ($entries as $entry) {
            $lines[] = '- '.$entry;
        }

        if (is_string($notes) && $notes !== '') {
            $lines[] = 'Narrative notes: '.$notes;
        }

        if (is_string($moderation) && $moderation !== '') {
            $lines[] = 'Moderation guardrails: '.$moderation;
        }

        return $lines;
    }

    protected function fallbackText(string $requestType, string $prompt): string
    {
        return match ($requestType) {
            'dm_takeover' => 'The AI delegate is reviewing the latest lore and will return shortly. Continue with collaborative planning until the summary is ready.',
            'npc_dialogue' => 'The NPC considers the request but remains silent for now. Try again after a brief pause.',
            'mentor_briefing' => 'The mentor is quietly gathering intel—expect a tactful briefing soon.',
            default => 'AI chronicler advanced the storyline but needs a mortal to embellish the tale.',
        };
    }
}
