<?php

namespace App\Services;

use App\Models\ConditionTimerAcknowledgement;
use App\Models\Group;
use App\Models\MapToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class ConditionTimerAcknowledgementService
{
    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public function hydrateSummaryForUser(
        array $summary,
        Group $group,
        User $user,
        bool $canViewAggregate = false
    ): array {
        $entries = $summary['entries'] ?? [];

        if ($entries === []) {
            return $summary;
        }

        $tokenIds = [];
        $conditionKeys = [];

        foreach ($entries as $entry) {
            $tokenId = Arr::get($entry, 'token.id');

            if ($tokenId === null) {
                continue;
            }

            $tokenIds[] = $tokenId;

            foreach (Arr::get($entry, 'conditions', []) as $condition) {
                $conditionKey = Arr::get($condition, 'key');

                if ($conditionKey !== null) {
                    $conditionKeys[] = $conditionKey;
                }
            }
        }

        if ($tokenIds === [] || $conditionKeys === []) {
            return $summary;
        }

        $tokenIds = array_values(array_unique($tokenIds));
        $conditionKeys = array_values(array_unique($conditionKeys));

        $summaryTimestamp = null;

        if (! empty($summary['generated_at'])) {
            try {
                $summaryTimestamp = CarbonImmutable::parse($summary['generated_at']);
            } catch (\Throwable $exception) {
                $summaryTimestamp = null;
            }
        }

        $acknowledgementsQuery = ConditionTimerAcknowledgement::query()
            ->where('group_id', $group->id)
            ->whereIn('map_token_id', $tokenIds)
            ->whereIn('condition_key', $conditionKeys);

        if ($summaryTimestamp !== null) {
            $acknowledgementsQuery->where('summary_generated_at', $summaryTimestamp);
        }

        $acknowledgements = $acknowledgementsQuery->get();

        $viewerMap = [];
        $aggregateMap = [];

        foreach ($acknowledgements as $acknowledgement) {
            $key = $this->composeKey($acknowledgement->map_token_id, $acknowledgement->condition_key);

            if ($acknowledgement->user_id === $user->getAuthIdentifier()) {
                $viewerMap[$key] = true;
            }

            if ($canViewAggregate) {
                $aggregateMap[$key] = ($aggregateMap[$key] ?? 0) + 1;
            }
        }

        $summary['entries'] = array_map(function (array $entry) use ($viewerMap, $aggregateMap, $canViewAggregate) {
            $entry['conditions'] = array_map(function (array $condition) use ($entry, $viewerMap, $aggregateMap, $canViewAggregate) {
                $tokenId = Arr::get($entry, 'token.id');
                $conditionKey = Arr::get($condition, 'key');

                if ($tokenId === null || $conditionKey === null) {
                    return $condition;
                }

                $compositeKey = $this->composeKey($tokenId, $conditionKey);

                $condition['acknowledged_by_viewer'] = (bool) ($viewerMap[$compositeKey] ?? false);

                if ($canViewAggregate) {
                    $condition['acknowledged_count'] = (int) ($aggregateMap[$compositeKey] ?? 0);
                }

                return $condition;
            }, $entry['conditions'] ?? []);

            return $entry;
        }, $entries);

        return $summary;
    }

    public function recordAcknowledgement(
        Group $group,
        MapToken $token,
        string $conditionKey,
        CarbonImmutable $summaryGeneratedAt,
        User $user
    ): ConditionTimerAcknowledgement {
        return ConditionTimerAcknowledgement::query()->updateOrCreate(
            [
                'group_id' => $group->id,
                'map_token_id' => $token->id,
                'user_id' => $user->getAuthIdentifier(),
                'condition_key' => $conditionKey,
            ],
            [
                'summary_generated_at' => $summaryGeneratedAt,
                'acknowledged_at' => CarbonImmutable::now('UTC'),
            ],
        );
    }

    public function countForSummary(
        Group $group,
        MapToken $token,
        string $conditionKey,
        CarbonImmutable $summaryGeneratedAt
    ): int {
        return ConditionTimerAcknowledgement::query()
            ->where('group_id', $group->id)
            ->where('map_token_id', $token->id)
            ->where('condition_key', $conditionKey)
            ->where('summary_generated_at', $summaryGeneratedAt)
            ->count();
    }

    protected function composeKey(int $tokenId, string $conditionKey): string
    {
        return sprintf('%d|%s', $tokenId, $conditionKey);
    }
}
