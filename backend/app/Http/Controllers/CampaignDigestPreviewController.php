<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\User;
use App\Services\PlayerDigestService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignDigestPreviewController extends Controller
{
    public function __construct(private readonly PlayerDigestService $digests)
    {
    }

    public function show(Request $request, Campaign $campaign): Response
    {
        $this->authorize('previewDigest', $campaign);

        $since = CarbonImmutable::now('UTC')->subDays(2);
        $until = CarbonImmutable::now('UTC');

        $players = $campaign->roleAssignments()
            ->where('role', CampaignRoleAssignment::ROLE_PLAYER)
            ->where('status', 'active')
            ->where('assignee_type', User::class)
            ->with('assignee')
            ->get()
            ->map(function (CampaignRoleAssignment $assignment) use ($campaign, $since) {
                $assignee = $assignment->assignee;

                if (! $assignee instanceof User) {
                    return null;
                }

                $digest = $this->digests->build($assignee, $since, 'full', [
                    'campaign_id' => $campaign->id,
                ]);

                return [
                    'id' => $assignee->id,
                    'name' => $assignee->name,
                    'email' => $assignee->email,
                    'digest' => $digest,
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Campaigns/DigestPreview', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'players' => $players,
            'window' => [
                'since' => $since->toIso8601String(),
                'until' => $until->toIso8601String(),
            ],
        ]);
    }
}
