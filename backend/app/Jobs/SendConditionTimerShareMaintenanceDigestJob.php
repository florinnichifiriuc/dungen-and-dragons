<?php

namespace App\Jobs;

use App\Models\Group;
use App\Services\ConditionTimerShareMaintenanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SendConditionTimerShareMaintenanceDigestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $groupId)
    {
    }

    public function handle(ConditionTimerShareMaintenanceService $maintenance): void
    {
        $group = Group::query()->find($this->groupId);

        if (! $group) {
            return;
        }

        $snapshot = $maintenance->buildMaintenanceSnapshot($group);

        if (! Arr::get($snapshot, 'attention.needs_attention')) {
            return;
        }

        Log::notice('condition_timer_share_maintenance_attention', [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'reasons' => Arr::get($snapshot, 'attention.reasons', []),
            'share' => Arr::get($snapshot, 'share'),
        ]);
    }
}
