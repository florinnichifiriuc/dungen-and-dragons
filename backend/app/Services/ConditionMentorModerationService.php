<?php

namespace App\Services;

use App\Models\AiRequest;
use App\Models\Group;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class ConditionMentorModerationService
{
    /**
     * @return array{status: string, notes: string|null, request: AiRequest}
     */
    public function evaluate(AiRequest $request, string $responseText): array
    {
        $phrases = (array) config('condition-transparency.mentor_briefings.flagged_phrases', []);
        $lower = Str::lower($responseText);
        $flaggedPhrase = collect($phrases)
            ->map(fn ($phrase) => (string) $phrase)
            ->first(fn ($phrase) => $phrase !== '' && Str::contains($lower, Str::lower($phrase)));

        if ($flaggedPhrase) {
            $notes = Lang::get('Mentor briefing flagged for review (phrase: :phrase).', ['phrase' => $flaggedPhrase]);
            $request->markModerationPending($notes);

            return [
                'status' => AiRequest::MODERATION_PENDING,
                'notes' => $notes,
                'request' => $request,
            ];
        }

        $request->markModerationApproved(null, null);

        return [
            'status' => AiRequest::MODERATION_APPROVED,
            'notes' => null,
            'request' => $request,
        ];
    }

    public function approve(AiRequest $request, int $moderatorId, ?string $notes = null): void
    {
        $request->markModerationApproved($moderatorId, $notes);
    }

    public function reject(AiRequest $request, int $moderatorId, ?string $notes = null): void
    {
        $request->markModerationRejected($moderatorId, $notes);
    }

    public function pendingBriefings(Group $group, int $limit = 10): Collection
    {
        return AiRequest::query()
            ->where('request_type', 'mentor_briefing')
            ->where('context_type', Group::class)
            ->where('context_id', $group->id)
            ->where('moderation_status', AiRequest::MODERATION_PENDING)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (AiRequest $request) {
                return [
                    'id' => $request->id,
                    'submitted_at' => $request->created_at?->toIso8601String(),
                    'excerpt' => Str::limit($request->response_text ?? '', 280),
                    'notes' => $request->moderation_notes,
                ];
            });
    }

    public function latestApproved(Group $group): ?AiRequest
    {
        return AiRequest::query()
            ->where('request_type', 'mentor_briefing')
            ->where('context_type', Group::class)
            ->where('context_id', $group->id)
            ->where('moderation_status', AiRequest::MODERATION_APPROVED)
            ->latest('completed_at')
            ->latest('created_at')
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function playbackDigest(Group $group, int $limit = 20): array
    {
        return AiRequest::query()
            ->where('request_type', 'mentor_briefing')
            ->where('context_type', Group::class)
            ->where('context_id', $group->id)
            ->where('moderation_status', AiRequest::MODERATION_REJECTED)
            ->latest('moderated_at')
            ->limit($limit)
            ->get()
            ->map(function (AiRequest $request) {
                return [
                    'id' => $request->id,
                    'submitted_at' => $request->created_at?->toIso8601String(),
                    'moderated_at' => $request->moderated_at?->toIso8601String(),
                    'moderation_notes' => $request->moderation_notes,
                    'excerpt' => Str::limit($request->response_text ?? '', 200),
                ];
            })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function approvedDigest(Group $group, ?CarbonImmutable $since = null, int $limit = 5): array
    {
        return AiRequest::query()
            ->where('request_type', 'mentor_briefing')
            ->where('context_type', Group::class)
            ->where('context_id', $group->id)
            ->where('moderation_status', AiRequest::MODERATION_APPROVED)
            ->when($since, function ($query, CarbonImmutable $threshold): void {
                $query->where(function ($window) use ($threshold): void {
                    $window->whereNotNull('completed_at')
                        ->where('completed_at', '>=', $threshold)
                        ->orWhere(function ($created) use ($threshold): void {
                            $created->whereNull('completed_at')
                                ->where('created_at', '>=', $threshold);
                        });
                });
            })
            ->latest('completed_at')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (AiRequest $request) {
                $focus = (array) ($request->meta['focus'] ?? []);

                return [
                    'id' => $request->id,
                    'completed_at' => $request->completed_at?->toIso8601String(),
                    'submitted_at' => $request->created_at?->toIso8601String(),
                    'excerpt' => Str::limit($request->response_text ?? '', 240),
                    'focus_summary' => $this->focusSummary($focus),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $focus
     */
    public function focusSummary(array $focus): string
    {
        $critical = Arr::get($focus, 'critical_conditions', []);
        $unacknowledged = Arr::get($focus, 'unacknowledged_tokens', []);
        $recurring = Arr::get($focus, 'recurring_conditions', []);

        $parts = [];

        if (! empty($critical)) {
            $parts[] = 'Critical: '.implode('; ', $critical);
        }

        if (! empty($unacknowledged)) {
            $parts[] = 'Unacknowledged: '.implode('; ', $unacknowledged);
        }

        if (! empty($recurring)) {
            $parts[] = 'Recurring: '.implode('; ', $recurring);
        }

        return implode(' | ', $parts);
    }
}
