<?php

namespace Tests\Feature;

use App\Models\ConditionTimerShareConsentLog;
use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionTimerShareMaintenanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_outputs_attention_table(): void
    {
        $this->freezeTime(CarbonImmutable::parse('2025-11-21 15:00:00', 'UTC'));

        config()->set('condition-transparency.maintenance.quiet_hour_attention_ratio', 0.5);

        $group = Group::factory()->create(['name' => 'Silver Anvil']);
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

        $share = ConditionTimerSummaryShare::factory()->create([
            'group_id' => $group->id,
            'created_by' => $owner->id,
            'expires_at' => now('UTC')->addHours(4),
        ]);

        foreach ([true, true, false] as $index => $quiet) {
            ConditionTimerSummaryShareAccess::factory()
                ->for($share, 'share')
                ->create([
                    'occurred_at' => now('UTC')->subHours($index + 1),
                    'quiet_hour_suppressed' => $quiet,
                ]);
        }

        $this->artisan('condition-transparency:share-maintenance')
            ->expectsTable(
                ['Group', 'State', 'Expires', 'Quiet Hours', 'Pending Consents', 'Reasons'],
                [[
                    'Group' => 'Silver Anvil',
                    'State' => 'expiring_soon',
                    'Expires' => now('UTC')->addHours(4)->toIso8601String(),
                    'Quiet Hours' => '67%',
                    'Pending Consents' => '1/1',
                    'Reasons' => 'consent_missing, expiring_soon, excessive_quiet_hour_access',
                ]]
            )
            ->assertExitCode(0);
    }

    public function test_command_handles_unknown_group(): void
    {
        $this->artisan('condition-transparency:share-maintenance', ['groupId' => 999])
            ->expectsOutput('Group not found.')
            ->assertExitCode(1);
    }
}
