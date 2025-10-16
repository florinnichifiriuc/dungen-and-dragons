<?php

namespace App\Services;

use App\Models\ConditionTimerSummaryShare;
use App\Models\Group;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ConditionTimerSummaryShareService
{
    public function __construct(private readonly int $defaultTtlDays = 14)
    {
    }

    public function activeShareForGroup(Group $group): ?ConditionTimerSummaryShare
    {
        $now = CarbonImmutable::now('UTC');

        return ConditionTimerSummaryShare::query()
            ->where('group_id', $group->id)
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    public function createShareForGroup(Group $group, User $creator, ?CarbonImmutable $expiresAt = null): ConditionTimerSummaryShare
    {
        $now = CarbonImmutable::now('UTC');

        ConditionTimerSummaryShare::query()
            ->where('group_id', $group->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $now]);

        if ($expiresAt === null) {
            $expiresAt = $now->addDays($this->defaultTtlDays);
        }

        return ConditionTimerSummaryShare::create([
            'group_id' => $group->id,
            'created_by' => $creator->getAuthIdentifier(),
            'token' => Str::random(48),
            'expires_at' => $expiresAt,
        ]);
    }

    public function revokeShare(ConditionTimerSummaryShare $share): void
    {
        if ($share->deleted_at !== null) {
            return;
        }

        $share->deleted_at = CarbonImmutable::now('UTC');
        $share->save();
    }
}
