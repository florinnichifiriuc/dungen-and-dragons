<?php

namespace Database\Factories;

use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConditionTimerSummaryShareAccess>
 */
class ConditionTimerSummaryShareAccessFactory extends Factory
{
    protected $model = ConditionTimerSummaryShareAccess::class;

    public function definition(): array
    {
        return [
            'condition_timer_summary_share_id' => ConditionTimerSummaryShare::factory(),
            'event_type' => 'access',
            'occurred_at' => CarbonImmutable::now('UTC'),
            'ip_hash' => hash('sha256', $this->faker->ipv4()),
            'user_agent_hash' => hash('sha256', $this->faker->userAgent()),
            'user_id' => null,
            'quiet_hour_suppressed' => false,
            'metadata' => [],
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function extensionEvent(?CarbonImmutable $expiresAt = null): self
    {
        return $this->state(fn () => [
            'event_type' => 'extension',
            'metadata' => [
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        ]);
    }
}
