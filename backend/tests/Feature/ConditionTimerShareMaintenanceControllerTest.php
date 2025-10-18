<?php

namespace Tests\Feature;

use App\Models\ConditionTimerSummaryShare;
use App\Models\ConditionTimerSummaryShareAccess;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ConditionTimerShareMaintenanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_can_view_maintenance_snapshot(): void
    {
        $this->freezeTime(CarbonImmutable::parse('2025-11-21 14:00:00', 'UTC'));

        config()->set('condition-transparency.maintenance.quiet_hour_attention_ratio', 0.5);

        $group = Group::factory()->create();
        $member = User::factory()->create();

        GroupMembership::query()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_DUNGEON_MASTER,
        ]);

        $share = ConditionTimerSummaryShare::factory()->create([
            'group_id' => $group->id,
            'created_by' => $member->id,
            'expires_at' => now('UTC')->addHours(5),
        ]);

        ConditionTimerSummaryShareAccess::factory()
            ->for($share, 'share')
            ->create([
                'occurred_at' => now('UTC')->subMinutes(45),
                'quiet_hour_suppressed' => true,
            ]);

        $response = $this
            ->actingAs($member)
            ->getJson(route('groups.condition-transparency.maintenance', [$group]));

        $response->assertOk();

        $snapshot = $response->json('snapshot');

        $this->assertSame($group->id, Arr::get($snapshot, 'group.id'));
        $this->assertTrue(Arr::get($snapshot, 'attention.needs_attention'));
        $this->assertContains('excessive_quiet_hour_access', Arr::get($snapshot, 'attention.reasons'));
    }
}
