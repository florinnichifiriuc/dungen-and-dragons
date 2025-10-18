<?php

namespace Tests\Unit;

use App\Jobs\SendConditionTimerShareMaintenanceDigestJob;
use App\Models\ConditionTimerShareConsentLog;
use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use App\Services\ConditionTimerShareMaintenanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendConditionTimerShareMaintenanceDigestJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_logs_attention_when_snapshot_requires_action(): void
    {
        $this->freezeTime(CarbonImmutable::parse('2025-11-21 16:00:00', 'UTC'));

        config()->set('condition-transparency.maintenance.quiet_hour_attention_ratio', 0.25);

        $group = Group::factory()->create(['name' => 'Verdant Wardens']);
        $owner = User::factory()->create();
        $player = User::factory()->create();

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_DUNGEON_MASTER,
        ]);

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $player->id,
            'role' => GroupMembership::ROLE_PLAYER,
        ]);

        ConditionTimerShareConsentLog::factory()->create([
            'group_id' => $group->id,
            'user_id' => $player->id,
            'recorded_by' => $owner->id,
            'action' => 'revoked',
            'visibility' => 'counts',
        ]);

        ConditionTimerSummaryShare::factory()->create([
            'group_id' => $group->id,
            'created_by' => $owner->id,
            'expires_at' => now('UTC')->addHours(1),
        ]);

        Log::spy();

        $job = new SendConditionTimerShareMaintenanceDigestJob($group->id);
        $job->handle(app(ConditionTimerShareMaintenanceService::class));

        Log::shouldHaveReceived('notice')
            ->once()
            ->withArgs(function (string $message, array $context) use ($group) {
                return $message === 'condition_timer_share_maintenance_attention'
                    && $context['group_id'] === $group->id
                    && in_array('consent_missing', $context['reasons'], true);
            });
    }

    public function test_job_skips_when_no_attention_needed(): void
    {
        $group = Group::factory()->create();

        Log::spy();

        $job = new SendConditionTimerShareMaintenanceDigestJob($group->id);
        $job->handle(app(ConditionTimerShareMaintenanceService::class));

        Log::shouldNotHaveReceived('notice');
    }
}
