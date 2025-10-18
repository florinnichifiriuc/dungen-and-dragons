<?php

namespace Database\Seeders;

use App\Models\BugReport;
use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2EBugReportingSeeder extends Seeder
{
    public const SHARE_TOKEN = 'bug-e2e-share-token-0f1d2c3b4a5e6f7890abcd1234567890';

    public function run(): void
    {
        $facilitator = User::updateOrCreate(
            ['email' => 'facilitator@example.com'],
            [
                'name' => 'E2E Facilitator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'locale' => 'en',
                'timezone' => 'UTC',
            ]
        );

        $player = User::updateOrCreate(
            ['email' => 'player@example.com'],
            [
                'name' => 'E2E Player',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'locale' => 'en',
                'timezone' => 'UTC',
            ]
        );

        $support = User::updateOrCreate(
            ['email' => 'support@example.com'],
            [
                'name' => 'E2E Support Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'locale' => 'en',
                'timezone' => 'UTC',
                'is_support_admin' => true,
            ]
        );

        $group = Group::updateOrCreate(
            ['slug' => 'e2e-bug-hunters'],
            [
                'name' => 'E2E Bug Hunters',
                'join_code' => 'BUGE2E',
                'description' => 'Release candidate validation cohort.',
                'created_by' => $facilitator->id,
                'telemetry_opt_out' => false,
            ]
        );

        GroupMembership::updateOrCreate(
            ['group_id' => $group->id, 'user_id' => $facilitator->id],
            ['role' => GroupMembership::ROLE_DUNGEON_MASTER]
        );

        GroupMembership::updateOrCreate(
            ['group_id' => $group->id, 'user_id' => $player->id],
            ['role' => GroupMembership::ROLE_PLAYER]
        );

        ConditionTimerSummaryShare::updateOrCreate(
            ['token' => self::SHARE_TOKEN],
            [
                'group_id' => $group->id,
                'created_by' => $facilitator->id,
                'expires_at' => now('UTC')->addDays(5),
                'visibility_mode' => 'counts',
                'consent_snapshot' => ['granted_user_ids' => []],
                'access_count' => 0,
            ]
        );

        BugReport::updateOrCreate(
            ['reference' => 'BR-SEED01'],
            [
                'submitted_by' => $facilitator->id,
                'submitted_email' => $facilitator->email,
                'group_id' => $group->id,
                'context_type' => 'facilitator',
                'status' => BugReport::STATUS_OPEN,
                'priority' => BugReport::PRIORITY_NORMAL,
                'summary' => 'Seeded launch rehearsal bug',
                'description' => 'Baseline issue seeded for admin dashboard regression coverage.',
                'environment' => ['seeded' => true],
                'tags' => ['seeded'],
            ]
        );
    }
}
