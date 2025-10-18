<?php

namespace Tests\Unit;

use App\Models\ConditionTimerShareConsentLog;
use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use App\Services\ConditionTimerShareMaintenanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ConditionTimerShareMaintenanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_flags_attention_reasons(): void
    {
        $this->travelTo(CarbonImmutable::parse('2025-11-21 09:00:00', 'UTC'));

        config()->set('condition-transparency.maintenance.access_window_days', 7);
        config()->set('condition-transparency.maintenance.quiet_hour_attention_ratio', 0.5);
        config()->set('condition-transparency.maintenance.expiry_attention_hours', 24);

        $group = Group::factory()->create();
        $owner = User::factory()->create();

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_DUNGEON_MASTER,
        ]);

        $playerWithConsent = User::factory()->create();
        $playerWithoutConsent = User::factory()->create();

        foreach ([$playerWithConsent, $playerWithoutConsent] as $player) {
            GroupMembership::query()->create([
                'group_id' => $group->id,
                'user_id' => $player->id,
                'role' => GroupMembership::ROLE_PLAYER,
            ]);
        }

        ConditionTimerShareConsentLog::factory()->create([
            'group_id' => $group->id,
            'user_id' => $playerWithConsent->id,
            'recorded_by' => $owner->id,
            'action' => 'granted',
            'visibility' => 'details',
        ]);

        ConditionTimerShareConsentLog::factory()->create([
            'group_id' => $group->id,
            'user_id' => $playerWithoutConsent->id,
            'recorded_by' => $owner->id,
            'action' => 'revoked',
            'visibility' => 'counts',
        ]);

        $share = ConditionTimerSummaryShare::factory()->create([
            'group_id' => $group->id,
            'created_by' => $owner->id,
            'expires_at' => now('UTC')->addHours(6),
            'access_count' => 0,
            'consent_snapshot' => [
                'granted_user_ids' => [$playerWithConsent->id],
            ],
        ]);

        $timestamps = [
            now('UTC')->subHours(3),
            now('UTC')->subHours(2),
            now('UTC')->subHours(1),
            now('UTC')->subMinutes(30),
        ];

        foreach ($timestamps as $index => $occurredAt) {
            ConditionTimerSummaryShareAccess::factory()
                ->for($share, 'share')
                ->create([
                    'occurred_at' => $occurredAt,
                    'quiet_hour_suppressed' => $index !== 2,
                ]);
        }

        $service = $this->app->make(ConditionTimerShareMaintenanceService::class);

        $snapshot = $service->buildMaintenanceSnapshot($group, $share->fresh());

        $this->assertTrue(Arr::get($snapshot, 'attention.needs_attention'));
        $this->assertContains('consent_missing', Arr::get($snapshot, 'attention.reasons'));
        $this->assertSame(1, count(Arr::get($snapshot, 'consent.pending')));

        $shareAttention = Arr::get($snapshot, 'share.attention.reasons', []);
        $this->assertContains('expiring_soon', $shareAttention);
        $this->assertContains('excessive_quiet_hour_access', $shareAttention);

        $this->assertEquals(0.75, Arr::get($snapshot, 'share.quiet_hour_ratio'));
        $this->assertSame(
            now('UTC')->subMinutes(30)->toIso8601String(),
            Arr::get($snapshot, 'share.last_accessed_at')
        );

        $this->travelBack();
    }

    public function test_attention_queue_only_returns_groups_needing_attention(): void
    {
        $this->travelTo(CarbonImmutable::parse('2025-11-21 12:00:00', 'UTC'));

        config()->set('condition-transparency.maintenance.access_window_days', 7);
        config()->set('condition-transparency.maintenance.quiet_hour_attention_ratio', 0.5);
        config()->set('condition-transparency.maintenance.expiry_attention_hours', 24);

        $groupWithIssues = Group::factory()->create(['name' => 'Arcane Torch']);
        $groupHealthy = Group::factory()->create(['name' => 'Brass Lantern']);
        $owner = User::factory()->create();

        foreach ([$groupWithIssues, $groupHealthy] as $group) {
            GroupMembership::query()->create([
                'group_id' => $group->id,
                'user_id' => $owner->id,
                'role' => GroupMembership::ROLE_DUNGEON_MASTER,
            ]);
        }

        $player = User::factory()->create();
        GroupMembership::query()->create([
            'group_id' => $groupWithIssues->id,
            'user_id' => $player->id,
            'role' => GroupMembership::ROLE_PLAYER,
        ]);

        ConditionTimerShareConsentLog::factory()->create([
            'group_id' => $groupWithIssues->id,
            'user_id' => $player->id,
            'recorded_by' => $owner->id,
            'action' => 'revoked',
            'visibility' => 'counts',
        ]);

        $shareNeedingAttention = ConditionTimerSummaryShare::factory()->create([
            'group_id' => $groupWithIssues->id,
            'created_by' => $owner->id,
            'expires_at' => now('UTC')->addHours(2),
        ]);

        ConditionTimerSummaryShareAccess::factory()
            ->for($shareNeedingAttention, 'share')
            ->create([
                'occurred_at' => now('UTC')->subMinutes(10),
                'quiet_hour_suppressed' => true,
            ]);

        $shareHealthy = ConditionTimerSummaryShare::factory()->create([
            'group_id' => $groupHealthy->id,
            'created_by' => $owner->id,
            'expires_at' => now('UTC')->addDays(3),
        ]);

        ConditionTimerSummaryShareAccess::factory()
            ->for($shareHealthy, 'share')
            ->create([
                'occurred_at' => now('UTC')->subHours(2),
                'quiet_hour_suppressed' => false,
            ]);

        $service = $this->app->make(ConditionTimerShareMaintenanceService::class);

        $queue = $service->attentionQueue();

        $this->assertCount(1, $queue);
        $this->assertSame($groupWithIssues->id, Arr::get($queue[0], 'group.id'));
        $this->assertContains('consent_missing', Arr::get($queue[0], 'attention.reasons'));

        $this->travelBack();
    }
}
