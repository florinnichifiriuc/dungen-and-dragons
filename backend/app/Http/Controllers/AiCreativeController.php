<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiCreativeRequest;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class AiCreativeController extends Controller
{
    public function __invoke(AiCreativeRequest $request, AiContentService $ai): JsonResponse
    {
        $data = $request->validated();
        $domain = $data['domain'];
        $userPrompt = trim((string) ($data['prompt'] ?? ''));
        $context = $data['context'] ?? [];

        $config = (array) config("ai.prompts.creative.$domain");
        abort_unless($config !== [], 422, 'Unsupported creative domain.');

        $systemPrompt = (string) Arr::get($config, 'system', '');
        $requestType = (string) Arr::get($config, 'request_type', 'creative_'.$domain);

        $prompt = $this->buildPrompt($domain, $userPrompt, $context);

        /** @var \App\Models\User $actor */
        $actor = $request->user();

        $result = $ai->creativeIdea(
            $actor,
            $requestType,
            $prompt,
            $systemPrompt,
            [
                'domain' => $domain,
                'context' => $context,
                'user_prompt' => $userPrompt,
            ],
            $actor,
        );

        $structured = $this->decodeStructured($result['content']);

        return response()->json([
            'idea' => $result['content'],
            'structured' => $structured,
        ]);
    }

    protected function buildPrompt(string $domain, string $userPrompt, array $context): string
    {
        return match ($domain) {
            'world' => $this->worldPrompt($userPrompt, $context),
            'region' => $this->regionPrompt($userPrompt, $context),
            'tile_template' => $this->tileTemplatePrompt($userPrompt, $context),
            'region_map' => $this->regionMapPrompt($userPrompt, $context),
            'campaign_tasks' => $this->campaignTaskPrompt($userPrompt, $context),
            'lore' => $this->lorePrompt($userPrompt, $context),
            'quest' => $this->questPrompt($userPrompt, $context),
            default => trim($userPrompt),
        };
    }

    protected function worldPrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        $group = Arr::get($context, 'group_name');
        $name = Arr::get($context, 'name');
        $summary = Arr::get($context, 'summary');
        $description = Arr::get($context, 'description');
        $turnCadence = Arr::get($context, 'turn_duration');

        $lines[] = 'Draft a collaborative world concept for a shared campaign.';
        if ($group) {
            $lines[] = "Group: $group";
        }
        if ($name) {
            $lines[] = "Working title: $name";
        }
        if ($summary) {
            $lines[] = "Existing summary: $summary";
        }
        if ($description) {
            $lines[] = "Existing notes: $description";
        }
        if ($turnCadence) {
            $lines[] = "Preferred turn cadence: $turnCadence hours";
        }
        if ($userPrompt !== '') {
            $lines[] = "Player prompt: $userPrompt";
        }
        $lines[] = 'Blend high-level lore, recurring beats, and an evocative image prompt that could be sent to a1111 for a 512x512 tile.';

        return implode("\n", $lines);
    }

    protected function regionPrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        if ($world = Arr::get($context, 'world_name')) {
            $lines[] = "Parent world: $world";
        }
        if ($region = Arr::get($context, 'name')) {
            $lines[] = "Region name: $region";
        }
        if ($summary = Arr::get($context, 'summary')) {
            $lines[] = "Existing summary: $summary";
        }
        if ($description = Arr::get($context, 'description')) {
            $lines[] = "Existing notes: $description";
        }
        if ($dm = Arr::get($context, 'dungeon_master')) {
            $lines[] = "Dungeon master focus: $dm";
        }
        if ($userPrompt !== '') {
            $lines[] = "Player or facilitator ask: $userPrompt";
        }
        $lines[] = 'Outline hazards, story threads, and an image prompt useful for stable diffusion.';

        return implode("\n", $lines);
    }

    protected function tileTemplatePrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        $group = Arr::get($context, 'group_name');
        $world = Arr::get($context, 'world_name');
        $terrain = Arr::get($context, 'terrain_type');
        $movement = Arr::get($context, 'movement_cost');
        $defense = Arr::get($context, 'defense_bonus');
        $description = Arr::get($context, 'description');

        $lines[] = 'Design a tactical map tile template for our digital library.';
        if ($group) {
            $lines[] = "Group: $group";
        }
        if ($world) {
            $lines[] = "World: $world";
        }
        if ($terrain) {
            $lines[] = "Existing terrain type: $terrain";
        }
        if ($movement !== null) {
            $lines[] = "Current movement cost: $movement";
        }
        if ($defense !== null) {
            $lines[] = "Current defense bonus: $defense";
        }
        if ($description) {
            $lines[] = "Notes: $description";
        }
        if ($userPrompt !== '') {
            $lines[] = "Facilitator request: $userPrompt";
        }
        $lines[] = 'Suggest edge connections and a 512x512 a1111 prompt to render the tile art.';

        return implode("\n", $lines);
    }

    protected function regionMapPrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        if ($title = Arr::get($context, 'title')) {
            $lines[] = "Map title: $title";
        }
        if ($base = Arr::get($context, 'base_layer')) {
            $lines[] = "Base layer: $base";
        }
        if ($orientation = Arr::get($context, 'orientation')) {
            $lines[] = "Orientation: $orientation";
        }
        if ($fog = Arr::get($context, 'fog_data')) {
            $lines[] = 'Current fog data: '.json_encode($fog);
        }
        if ($userPrompt !== '') {
            $lines[] = "Facilitator ask: $userPrompt";
        }
        $lines[] = 'Provide layout notes, exploration hooks, suggested fog defaults, and an a1111-friendly prompt.';

        return implode("\n", $lines);
    }

    protected function campaignTaskPrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        if ($campaign = Arr::get($context, 'campaign_title')) {
            $lines[] = "Campaign: $campaign";
        }
        if ($turn = Arr::get($context, 'current_turn')) {
            $lines[] = "Current turn: $turn";
        }
        if ($status = Arr::get($context, 'statuses')) {
            $lines[] = 'Available statuses: '.implode(', ', (array) $status);
        }
        if ($existing = Arr::get($context, 'existing_tasks')) {
            $lines[] = 'Existing tasks: '.json_encode($existing);
        }
        if ($userPrompt !== '') {
            $lines[] = "GM prompt: $userPrompt";
        }
        $lines[] = 'Return backlog suggestions and note how they align to turns.';

        return implode("\n", $lines);
    }

    protected function lorePrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        if ($name = Arr::get($context, 'name')) {
            $lines[] = "Lore subject: $name";
        }
        if ($type = Arr::get($context, 'type')) {
            $lines[] = "Type: $type";
        }
        if ($tags = Arr::get($context, 'tags')) {
            $lines[] = 'Existing tags: '.implode(', ', (array) $tags);
        }
        if ($summary = Arr::get($context, 'summary')) {
            $lines[] = "Existing summary: $summary";
        }
        if ($userPrompt !== '') {
            $lines[] = "GM prompt: $userPrompt";
        }
        $lines[] = 'Offer secrets that are safe for collaborative play and an image prompt for a1111.';

        return implode("\n", $lines);
    }

    protected function questPrompt(string $userPrompt, array $context): string
    {
        $lines = [];
        if ($campaign = Arr::get($context, 'campaign_title')) {
            $lines[] = "Campaign: $campaign";
        }
        if ($quest = Arr::get($context, 'title')) {
            $lines[] = "Quest title: $quest";
        }
        if ($region = Arr::get($context, 'region')) {
            $lines[] = "Region focus: $region";
        }
        if ($status = Arr::get($context, 'status')) {
            $lines[] = "Quest status: $status";
        }
        if ($userPrompt !== '') {
            $lines[] = "GM or player request: $userPrompt";
        }
        $lines[] = 'Outline objectives, complications, rewards, and an evocative a1111 prompt.';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeStructured(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Attempt to extract JSON substring if wrappers exist
        if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
            $second = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($second)) {
                return $second;
            }
        }

        return null;
    }
}
