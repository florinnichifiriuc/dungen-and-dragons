<?php

namespace App\Http\Controllers;

use App\Events\ConditionTimerAcknowledgementRecorded;
use App\Http\Requests\ConditionTimerAcknowledgementStoreRequest;
use App\Models\Group;
use App\Models\MapToken;
use App\Services\AnalyticsRecorder;
use App\Services\ConditionTimerAcknowledgementService;
use App\Support\ConditionTimerInsights;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ConditionTimerAcknowledgementController extends Controller
{
    public function __construct(
        private readonly ConditionTimerAcknowledgementService $acknowledgements,
        private readonly AnalyticsRecorder $analytics
    ) {
    }

    public function store(ConditionTimerAcknowledgementStoreRequest $request, Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        /** @var Authenticatable $user */
        $user = $request->user();

        $tokenId = $request->integer('map_token_id');
        $conditionKey = $request->string('condition_key')->toString();

        try {
            $summaryGeneratedAt = CarbonImmutable::parse($request->input('summary_generated_at'))->setTimezone('UTC');
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'summary_generated_at' => 'Provide a valid ISO 8601 timestamp.',
            ]);
        }

        $token = MapToken::query()
            ->whereKey($tokenId)
            ->whereHas('map', fn ($query) => $query->where('group_id', $group->id))
            ->first();

        if ($token === null) {
            throw ValidationException::withMessages([
                'map_token_id' => 'Selected token is not part of this group.',
            ]);
        }

        $activeConditions = $token->status_conditions ?? [];

        if (! in_array($conditionKey, $activeConditions, true)) {
            throw ValidationException::withMessages([
                'condition_key' => 'The condition is no longer active for this token.',
            ]);
        }

        $durations = $token->status_condition_durations ?? [];
        $rounds = Arr::get($durations, $conditionKey);

        if ($rounds !== null && (int) $rounds <= 0) {
            throw ValidationException::withMessages([
                'condition_key' => 'The condition has already expired.',
            ]);
        }

        $acknowledgement = $this->acknowledgements->recordAcknowledgement(
            $group,
            $token,
            $conditionKey,
            $summaryGeneratedAt,
            $user,
        );

        $acknowledgedCount = $this->acknowledgements->countForSummary(
            $group,
            $token,
            $conditionKey,
            $summaryGeneratedAt,
        );

        $urgency = ConditionTimerInsights::urgency($rounds !== null ? (int) $rounds : null);

        $this->analytics->record(
            'timer_summary.acknowledged',
            [
                'group_id' => $group->id,
                'map_token_id' => $token->id,
                'condition_key' => $conditionKey,
                'urgency' => $urgency,
                'summary_generated_at' => $summaryGeneratedAt->toIso8601String(),
            ],
            $user,
            $group,
        );

        event(new ConditionTimerAcknowledgementRecorded(
            $group->id,
            $token->id,
            $conditionKey,
            $summaryGeneratedAt->toIso8601String(),
            $acknowledgedCount,
            (int) $user->getAuthIdentifier(),
        ));

        return response()->json([
            'acknowledgement' => [
                'token_id' => $acknowledgement->map_token_id,
                'condition_key' => $acknowledgement->condition_key,
                'summary_generated_at' => $acknowledgement->summary_generated_at->toIso8601String(),
                'acknowledged_by_viewer' => true,
                'acknowledged_count' => $acknowledgedCount,
            ],
        ]);
    }
}
