<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\ConditionTimerShareMaintenanceService;
use Illuminate\Http\JsonResponse;

class ConditionTimerShareMaintenanceController extends Controller
{
    public function __construct(
        private readonly ConditionTimerShareMaintenanceService $maintenance
    ) {
    }

    public function show(Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        return response()->json([
            'snapshot' => $this->maintenance->buildMaintenanceSnapshot($group),
        ]);
    }
}
