<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Services\ConditionTimerShareMaintenanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class ConditionTimerShareMaintenanceCommand extends Command
{
    protected $signature = 'condition-transparency:share-maintenance {groupId? : Limit the report to a specific group id}';

    protected $description = 'Summarize condition timer share maintenance attention items.';

    public function __construct(
        private readonly ConditionTimerShareMaintenanceService $maintenance
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $groupId = $this->argument('groupId');

        if ($groupId) {
            $group = Group::query()->find($groupId);

            if (! $group) {
                $this->error('Group not found.');

                return self::FAILURE;
            }

            $snapshots = [$this->maintenance->buildMaintenanceSnapshot($group)];
        } else {
            $snapshots = $this->maintenance->attentionQueue();
        }

        $snapshots = array_values(array_filter(
            $snapshots,
            fn (array $snapshot) => Arr::get($snapshot, 'attention.needs_attention') === true
        ));

        if (empty($snapshots)) {
            $this->info('No share maintenance issues detected.');

            return self::SUCCESS;
        }

        $rows = array_map(function (array $snapshot) {
            $share = Arr::get($snapshot, 'share');
            $state = Arr::get($share, 'state.state', 'none');
            $expiresAt = Arr::get($share, 'expires_at') ?? '—';
            $ratio = Arr::get($share, 'quiet_hour_ratio');
            $quietPercent = $ratio !== null ? sprintf('%.0f%%', $ratio * 100) : '0%';
            $reasons = Arr::get($snapshot, 'attention.reasons', []);
            $pending = Arr::get($snapshot, 'consent.pending', []);
            $totalPlayers = Arr::get($snapshot, 'consent.total_players', 0);

            return [
                'Group' => Arr::get($snapshot, 'group.name'),
                'State' => $state,
                'Expires' => $expiresAt,
                'Quiet Hours' => $quietPercent,
                'Pending Consents' => sprintf('%d/%d', count($pending), $totalPlayers),
                'Reasons' => empty($reasons) ? '—' : implode(', ', $reasons),
            ];
        }, $snapshots);

        $this->table(
            ['Group', 'State', 'Expires', 'Quiet Hours', 'Pending Consents', 'Reasons'],
            $rows
        );

        return self::SUCCESS;
    }
}
