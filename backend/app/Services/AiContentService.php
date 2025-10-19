<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\Group;
use App\Models\Map;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\ConditionMentorPromptManifest;
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

    public function draftWorld(Group $group, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildWorldIdeaPrompt($group, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.world.request_type', 'creative_world'),
            context: $group,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.world.system'));

        $fallback = $this->fallbackWorldIdea($group, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $fields = $fallback['fields'];
        $fields['name'] = $this->coalesceString($decoded, ['name', 'title'], (string) $fields['name']);
        $fields['summary'] = $this->coalesceString($decoded, ['summary', 'tagline'], (string) $fields['summary']);
        $fields['description'] = $this->coalesceString($decoded, ['description', 'lore'], (string) $fields['description']);
        $fields['default_turn_duration_hours'] = (int) ($decoded['default_turn_duration_hours'] ?? $decoded['turn_cadence'] ?? $fields['default_turn_duration_hours']);

        return [
            'summary' => $this->coalesceString($decoded, ['summary', 'overview', 'description'], $fallback['summary']),
            'fields' => $fields,
            'tips' => $this->stringList($decoded['beats'] ?? $decoded['tips'] ?? $fallback['tips']),
            'image_prompt' => $this->coalesceOptionalString($decoded, ['image_prompt', 'art_prompt'], $fallback['image_prompt']),
        ];
    }

    public function draftRegion(Group $group, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildRegionIdeaPrompt($group, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.region.request_type', 'creative_region'),
            context: $group,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.region.system'));

        $fallback = $this->fallbackRegionIdea($group, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $fields = $fallback['fields'];
        $fields['name'] = $this->coalesceString($decoded, ['name', 'title'], (string) $fields['name']);
        $fields['summary'] = $this->coalesceString($decoded, ['summary', 'hook'], (string) $fields['summary']);
        $fields['description'] = $this->coalesceString($decoded, ['description', 'details'], (string) $fields['description']);
        $fields['turn_duration_hours'] = (int) ($decoded['turn_duration_hours'] ?? $decoded['cadence'] ?? $fields['turn_duration_hours']);

        return [
            'summary' => $this->coalesceString($decoded, ['summary', 'overview', 'description'], $fallback['summary']),
            'fields' => $fields,
            'tips' => $this->stringList($decoded['beats'] ?? $decoded['encounters'] ?? $fallback['tips']),
            'image_prompt' => $this->coalesceOptionalString($decoded, ['image_prompt', 'map_prompt'], $fallback['image_prompt']),
        ];
    }

    public function draftTileTemplate(Group $group, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildTileTemplatePrompt($group, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.tile_template.request_type', 'creative_tile_template'),
            context: $group,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.tile_template.system'));

        $fallback = $this->fallbackTileTemplateIdea($group, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $fields = $fallback['fields'];
        $fields['name'] = $this->coalesceString($decoded, ['name', 'title'], (string) $fields['name']);
        $fields['terrain_type'] = $this->coalesceString($decoded, ['terrain_type', 'terrain'], (string) $fields['terrain_type']);
        $fields['movement_cost'] = (int) ($decoded['movement_cost'] ?? $decoded['travel_cost'] ?? $fields['movement_cost']);
        $fields['defense_bonus'] = (int) ($decoded['defense_bonus'] ?? $decoded['defence_bonus'] ?? $fields['defense_bonus']);
        $fields['edge_profile'] = $this->coalesceString($decoded, ['edge_profile', 'edges'], (string) $fields['edge_profile']);

        return [
            'summary' => $this->coalesceString($decoded, ['summary', 'description', 'notes'], $fallback['summary']),
            'fields' => $fields,
            'tips' => $this->stringList($decoded['tactics'] ?? $decoded['tips'] ?? $fallback['tips']),
            'image_prompt' => $this->coalesceOptionalString($decoded, ['image_prompt', 'texture_prompt'], $fallback['image_prompt']),
        ];
    }

    public function draftMapPlan(Map $map, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildMapPlanPrompt($map, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.region_map.request_type', 'creative_region_map'),
            context: $map,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.region_map.system'));

        $fallback = $this->fallbackMapPlan($map, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $fields = $fallback['fields'];
        $fields['width'] = (int) ($decoded['width'] ?? $fields['width']);
        $fields['height'] = (int) ($decoded['height'] ?? $fields['height']);
        $fields['orientation'] = $this->coalesceString($decoded, ['orientation', 'grid_orientation'], (string) $fields['orientation']);
        $fields['fog_data'] = $this->coalesceString($decoded, ['fog_data', 'fog'], (string) $fields['fog_data']);

        return [
            'summary' => $this->coalesceString($decoded, ['summary', 'description'], $fallback['summary']),
            'fields' => $fields,
            'tips' => $this->stringList($decoded['points_of_interest'] ?? $decoded['tips'] ?? $fallback['tips']),
            'image_prompt' => $this->coalesceOptionalString($decoded, ['image_prompt', 'render_prompt'], $fallback['image_prompt']),
        ];
    }

    public function draftCampaignTasks(Campaign $campaign, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildTaskIdeaPrompt($campaign, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.campaign_tasks.request_type', 'creative_campaign_tasks'),
            context: $campaign,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.campaign_tasks.system'));

        $fallback = $this->fallbackCampaignTasks($campaign, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $tasks = $decoded['tasks'] ?? [];
        if (is_array($tasks) && count($tasks) > 0) {
            $first = $tasks[0];
            if (is_array($first)) {
                $fallback['fields']['title'] = $this->coalesceString($first, ['title', 'name'], (string) $fallback['fields']['title']);
                $fallback['fields']['description'] = $this->coalesceString($first, ['description', 'details'], (string) $fallback['fields']['description']);
            }
            $fallback['tips'] = array_map(fn ($task) => is_array($task) ? ($task['title'] ?? $task['name'] ?? '') : (string) $task, $tasks);
            $fallback['tips'] = array_values(array_filter(array_map('strval', $fallback['tips'])));
        } else {
            $fallback['fields']['title'] = $this->coalesceString($decoded, ['title', 'task'], (string) $fallback['fields']['title']);
            $fallback['fields']['description'] = $this->coalesceString($decoded, ['description', 'details'], (string) $fallback['fields']['description']);
            $fallback['tips'] = $this->stringList($decoded['tips'] ?? $fallback['tips']);
        }

        return $fallback;
    }

    public function draftLoreEntry(Campaign $campaign, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildLoreIdeaPrompt($campaign, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.lore.request_type', 'creative_lore_entry'),
            context: $campaign,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.lore.system'));

        $fallback = $this->fallbackLoreIdea($campaign, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $fields = $fallback['fields'];
        $fields['name'] = $this->coalesceString($decoded, ['name', 'title'], (string) $fields['name']);
        $fields['alias'] = $this->coalesceOptionalString($decoded, ['alias', 'epithet'], $fields['alias']);
        $fields['entity_type'] = $this->coalesceString($decoded, ['entity_type', 'type'], (string) $fields['entity_type']);
        $fields['description'] = $this->coalesceString($decoded, ['description', 'lore'], (string) $fields['description']);
        $fields['tags'] = $this->stringList($decoded['tags'] ?? $fields['tags']);

        return [
            'summary' => $this->coalesceString($decoded, ['summary', 'description'], $fallback['summary']),
            'fields' => $fields,
            'tips' => $this->stringList($decoded['hooks'] ?? $decoded['tips'] ?? $fallback['tips']),
            'image_prompt' => $this->coalesceOptionalString($decoded, ['image_prompt', 'portrait_prompt'], $fallback['image_prompt']),
        ];
    }

    public function draftQuest(Campaign $campaign, string $prompt, ?User $requestedBy = null): array
    {
        $fullPrompt = $this->buildQuestIdeaPrompt($campaign, $prompt);

        $request = $this->storeRequest(
            requestType: config('ai.prompts.creative.quest.request_type', 'creative_quest'),
            context: $campaign,
            prompt: $fullPrompt,
            meta: array_filter(['prompt' => $prompt]),
            requestedBy: $requestedBy,
        );

        $response = $this->dispatch($request, $fullPrompt, config('ai.prompts.creative.quest.system'));

        $fallback = $this->fallbackQuestIdea($campaign, $prompt);
        $decoded = $this->decodeIdeaPayload($response);

        if (! $decoded) {
            return $fallback;
        }

        $fields = $fallback['fields'];
        $fields['title'] = $this->coalesceString($decoded, ['title', 'name'], (string) $fields['title']);
        $fields['summary'] = $this->coalesceString($decoded, ['summary', 'hook'], (string) $fields['summary']);
        $fields['description'] = $this->coalesceString($decoded, ['description', 'details'], (string) $fields['description']);
        $fields['objectives'] = $this->stringList($decoded['objectives'] ?? $fields['objectives']);

        return [
            'summary' => $this->coalesceString($decoded, ['summary', 'description'], $fallback['summary']),
            'fields' => $fields,
            'tips' => $this->stringList($decoded['twists'] ?? $decoded['tips'] ?? $fallback['tips']),
            'image_prompt' => $this->coalesceOptionalString($decoded, ['image_prompt', 'scene_prompt'], $fallback['image_prompt']),
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

    protected function buildWorldIdeaPrompt(Group $group, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Group: %s (ID %d)', $group->name, $group->id);
        $lines[] = 'Task: Draft a JSON payload describing a new shared campaign world.';
        $lines[] = 'Required keys: name (string), summary (string), description (string), default_turn_duration_hours (integer), tips (array of strings), image_prompt (string).';
        $lines[] = 'Keep the tone cooperative, adventurous, and inclusive for D&D tables.';

        if (trim($prompt) !== '') {
            $lines[] = 'Inspiration: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function buildRegionIdeaPrompt(Group $group, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Group: %s (ID %d)', $group->name, $group->id);
        $lines[] = 'Task: Produce JSON describing a new region assignment within an existing world.';
        $lines[] = 'Required keys: name, summary, description, turn_duration_hours, tips (array of strings), image_prompt (string).';
        $lines[] = 'Highlight exploration hooks and pacing guidance for turn-based play.';

        if (trim($prompt) !== '') {
            $lines[] = 'Prompt focus: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function buildTileTemplatePrompt(Group $group, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Group: %s (ID %d)', $group->name, $group->id);
        $lines[] = 'Task: Return JSON for a reusable terrain tile template.';
        $lines[] = 'Required keys: name, terrain_type, movement_cost, defense_bonus, edge_profile (JSON string), summary, tips (array), image_prompt.';
        $lines[] = 'Keep movement_cost between 1 and 12 and defense_bonus between 0 and 8.';

        if (trim($prompt) !== '') {
            $lines[] = 'Terrain inspiration: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function buildMapPlanPrompt(Map $map, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Map: %s (ID %d)', $map->title, $map->id);
        $lines[] = 'Task: Provide JSON for a map setup plan with grid width, height, orientation, fog_data (JSON string), tips (array), image_prompt.';
        $lines[] = sprintf('Current base layer: %s; orientation: %s.', $map->base_layer, $map->orientation);

        if ($map->region) {
            $lines[] = sprintf('Region context: %s.', $map->region->name);
        }

        if (trim($prompt) !== '') {
            $lines[] = 'Desired adjustments: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function buildTaskIdeaPrompt(Campaign $campaign, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Campaign: %s (ID %d)', $campaign->title, $campaign->id);
        $lines[] = 'Task: Suggest JSON for task board updates. Include tasks (array) with title and description, plus tips (array of strings).';
        $lines[] = 'Focus on actionable steps for upcoming turns.';

        if (trim($prompt) !== '') {
            $lines[] = 'Focus prompt: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function buildLoreIdeaPrompt(Campaign $campaign, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Campaign: %s (ID %d)', $campaign->title, $campaign->id);
        $lines[] = 'Task: Return JSON for a lore entry. Keys: name, alias, entity_type, description, tags (array of strings), summary, tips (array), image_prompt.';
        $lines[] = 'Tone should be evocative but safe for players.';

        if (trim($prompt) !== '') {
            $lines[] = 'Lore inspiration: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function buildQuestIdeaPrompt(Campaign $campaign, string $prompt): string
    {
        $lines = [];
        $lines[] = sprintf('Campaign: %s (ID %d)', $campaign->title, $campaign->id);
        $lines[] = 'Task: Produce JSON describing a quest. Keys: title, summary, description, objectives (array of strings), tips (array), image_prompt.';
        $lines[] = 'Keep objectives concise and collaborative.';

        if (trim($prompt) !== '') {
            $lines[] = 'Quest seed: '.$prompt;
        }

        return implode("\n", $lines);
    }

    protected function decodeIdeaPayload(string $response): ?array
    {
        $text = trim($response);

        if ($text === '') {
            return null;
        }

        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $snippet = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($snippet, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function coalesceString(array $data, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $default;
    }

    protected function coalesceOptionalString(array $data, array $keys, ?string $default = null): ?string
    {
        $value = $this->coalesceString($data, $keys, '');

        if ($value === '') {
            return $default;
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    protected function stringList(mixed $value): array
    {
        if (is_string($value)) {
            $parts = preg_split('/[\n\r]+/', $value) ?: [];
            $trimmed = array_map(fn ($part) => trim((string) $part), $parts);

            return array_values(array_filter($trimmed, fn ($part) => $part !== ''));
        }

        if (is_array($value)) {
            $items = [];

            foreach ($value as $entry) {
                $text = '';

                if (is_string($entry)) {
                    $text = trim($entry);
                } elseif (is_array($entry)) {
                    $candidate = Arr::get($entry, 'title') ?? Arr::get($entry, 'name') ?? Arr::get($entry, 'summary');
                    $text = is_string($candidate) ? trim($candidate) : '';
                }

                if ($text !== '') {
                    $items[] = $text;
                }
            }

            return $items;
        }

        return [];
    }

    protected function fallbackWorldIdea(Group $group, string $prompt): array
    {
        $seed = trim($prompt) !== '' ? Str::title(Str::limit($prompt, 40, '')) : 'Radiant Expanse';
        $summary = sprintf('%s beckons adventurers with luminous ley lines and a tapestry of cultures ready to explore.', $seed);

        return [
            'summary' => $summary,
            'fields' => [
                'name' => $seed,
                'summary' => $summary,
                'description' => sprintf('Forged as a collaborative setting for %s, this realm balances intrigue, respite, and space for new heroes.', $group->name),
                'default_turn_duration_hours' => 24,
            ],
            'tips' => [
                'Introduce two anchor settlements and a mysterious frontier region.',
                'Define a signature magical phenomenon that influences travel and encounters.',
                'Highlight a seasonal celebration that pulls adventurers into local intrigue.',
            ],
            'image_prompt' => sprintf('Painterly world map of %s, glowing ley lines, collaborative fantasy aesthetic', $seed),
        ];
    }

    protected function fallbackRegionIdea(Group $group, string $prompt): array
    {
        $seed = trim($prompt) !== '' ? Str::title(Str::limit($prompt, 40, '')) : 'Auric Scriptorium Marches';
        $summary = sprintf('A frontier of %s where each turn uncovers relics and shifting alliances.', $seed);

        return [
            'summary' => $summary,
            'fields' => [
                'name' => $seed,
                'summary' => $summary,
                'description' => 'Clockwork ruins rise from dunes of memory sand while caravans and wardens negotiate fragile truces.',
                'turn_duration_hours' => 48,
            ],
            'tips' => [
                'Feature a faction that offers escalating boons as players complete tasks.',
                'Rotate environmental twists every other turn to keep planning dynamic.',
            ],
            'image_prompt' => sprintf('Isometric regional map, desert clockwork ruins, twilight hues, banners for %s', $group->name),
        ];
    }

    protected function fallbackTileTemplateIdea(Group $group, string $prompt): array
    {
        $seed = trim($prompt) !== '' ? Str::snake(Str::limit($prompt, 24, '')) : 'luminous_brambles';
        $name = Str::title(str_replace('_', ' ', $seed));

        return [
            'summary' => sprintf('A terrain tile of %s shaped for exploration encounters.', $name),
            'fields' => [
                'name' => $name,
                'terrain_type' => 'enchanted-thicket',
                'movement_cost' => 3,
                'defense_bonus' => 1,
                'edge_profile' => json_encode(['north' => 'open', 'south' => 'roots', 'east' => 'clearing', 'west' => 'clearing']),
            ],
            'tips' => [
                'Pair with stream or road tiles to create contrast in speed.',
                'Grant advantage on stealth checks when characters linger here.',
            ],
            'image_prompt' => sprintf('512x512 tile art, %s terrain, soft bioluminescent plants, tabletop top-down style', $name),
        ];
    }

    protected function fallbackMapPlan(Map $map, string $prompt): array
    {
        $summary = sprintf('Sketch a grid that makes %s easy to adjudicate at the table.', $map->title);
        $width = $map->width ?? 18;
        $height = $map->height ?? 12;

        return [
            'summary' => $summary,
            'fields' => [
                'width' => $width,
                'height' => $height,
                'orientation' => $map->orientation,
                'fog_data' => json_encode(['mode' => 'mask', 'opacity' => 0.6, 'revealed' => []]),
            ],
            'tips' => [
                'Reserve two hexes near entrances as staging areas for tokens.',
                'Place three notable landmarks to anchor tactical choices.',
            ],
            'image_prompt' => sprintf('Map concept art for %s, %dx%d grid, %s orientation', $map->title, $width, $height, $map->orientation),
        ];
    }

    protected function fallbackCampaignTasks(Campaign $campaign, string $prompt): array
    {
        $title = trim($prompt) !== '' ? Str::title(Str::limit($prompt, 50, '')) : 'Stabilize the frontier routes';

        return [
            'summary' => sprintf('Prep actionable beats to keep %s on pace with its turns.', $campaign->title),
            'fields' => [
                'title' => $title,
                'description' => 'Outline the obstacles, stakeholders, and success signals for the team.',
            ],
            'tips' => [
                'Track which turn the task should complete and who owns the follow-up.',
                'Add a support task that empowers non-frontline characters.',
                'Capture one celebratory beat so the board feels encouraging.',
            ],
            'image_prompt' => null,
        ];
    }

    protected function fallbackLoreIdea(Campaign $campaign, string $prompt): array
    {
        $name = trim($prompt) !== '' ? Str::title(Str::limit($prompt, 40, '')) : 'Archivist Seraphine';

        return [
            'summary' => sprintf('Codify a lore entry that deepens %s.', $campaign->title),
            'fields' => [
                'name' => $name,
                'alias' => 'The Echoed Quill',
                'entity_type' => 'character',
                'description' => sprintf('%s preserves memories within sentient tomes, guiding heroes with gentle foresight.', $name),
                'tags' => ['ally', 'mystic'],
            ],
            'tips' => [
                'Add a relationship hook to another lore entry.',
                'Note a secret only the GM should see for future reveals.',
            ],
            'image_prompt' => sprintf('Portrait of %s, shimmering quill, arcane librarian aesthetic', $name),
        ];
    }

    protected function fallbackQuestIdea(Campaign $campaign, string $prompt): array
    {
        $title = trim($prompt) !== '' ? Str::title(Str::limit($prompt, 50, '')) : 'Calm the Whispering Leyline';

        return [
            'summary' => sprintf('Outline a cooperative quest for %s.', $campaign->title),
            'fields' => [
                'title' => $title,
                'summary' => 'A destabilized leyline threatens nearby settlements; the party must weave it calm.',
                'description' => 'Detail the key locations, allies, and risks tied to the quest. Use Markdown for readability.',
                'objectives' => [
                    'Secure an anchor artifact from the Sapphire Vault.',
                    'Negotiate aid from the Verdant Chorus druids.',
                    'Channel the ley energy safely back into the warded obelisk.',
                ],
            ],
            'tips' => [
                'Add at least one optional objective for flexibility.',
                'Specify how success changes the world or future turns.',
            ],
            'image_prompt' => sprintf('Quest illustration for %s, luminous leyline, cooperative heroes, painterly fantasy style', $campaign->title),
        ];
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
            'summary' => 'Stormbreak Vale thrived under vigilant spirits.',
            'dm_takeover' => 'The delegate recommends focusing on three beats: stabilize the grove, parley with the warden, and chart a retreat. Tone: warm guardian.',
            'npc_dialogue' => '[Captain Mirela] "Hold fast, friends. The winds favor honest hearts tonight."',
            'mentor_briefing' => 'Fresh word from the Mentor: rally your healers, cleanse the grove, and celebrate the resilience you have shown.',
            config('ai.prompts.creative.world.request_type', 'creative_world') => 'Radiant Expanse beckons with leyline-lit horizons and a collaborative frontier to explore.',
            config('ai.prompts.creative.region.request_type', 'creative_region') => 'Auric Scriptorium Marches shifts with every turn—anchor factions, rotating hazards, and emergent intrigue await.',
            config('ai.prompts.creative.tile_template.request_type', 'creative_tile_template') => 'Luminous Brambles tile: enchanted-thicket terrain with open north path, rooted south edge, and glimmering flora.',
            config('ai.prompts.creative.region_map.request_type', 'creative_region_map') => 'Draft a pointy grid with staging hexes at the gate, a trio of landmarks, and fog tuned for dramatic reveals.',
            config('ai.prompts.creative.campaign_tasks.request_type', 'creative_campaign_tasks') => 'Stabilize the frontier routes, assign allies to support cards, and log a celebratory beat for the task board.',
            config('ai.prompts.creative.lore.request_type', 'creative_lore_entry') => 'Archivist Seraphine safeguards living tomes, offering foresight to allies and secrets for the GM.',
            config('ai.prompts.creative.quest.request_type', 'creative_quest') => 'Calm the Whispering Leyline with anchor artifacts, Verdant Chorus negotiations, and a restored obelisk.',
            default => 'AI chronicler advanced the storyline but needs a mortal to embellish the tale.',
        };
    }
}
