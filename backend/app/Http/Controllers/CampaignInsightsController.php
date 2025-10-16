<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\FacilitatorInsightsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignInsightsController extends Controller
{
    public function __construct(private readonly FacilitatorInsightsService $insights)
    {
    }

    public function show(Request $request, Campaign $campaign): Response
    {
        $this->authorize('viewInsights', $campaign);

        $filters = [
            'urgency' => $request->query('urgency'),
            'faction' => $request->query('faction'),
        ];

        $insights = $this->insights->build($campaign, $request->user(), $filters);

        return Inertia::render('Campaigns/Insights', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'filters' => [
                'urgency' => $insights['filters']['urgency'],
                'faction' => $insights['filters']['faction'],
                'options' => [
                    'urgency' => [
                        ['value' => null, 'label' => 'All urgencies'],
                        ['value' => 'critical', 'label' => 'Critical'],
                        ['value' => 'warning', 'label' => 'Warning'],
                        ['value' => 'calm', 'label' => 'Calm'],
                    ],
                    'faction' => [
                        ['value' => null, 'label' => 'All dispositions'],
                        ['value' => 'ally', 'label' => 'Allies'],
                        ['value' => 'adversary', 'label' => 'Adversaries'],
                        ['value' => 'neutral', 'label' => 'Neutrals'],
                        ['value' => 'hazard', 'label' => 'Hazards'],
                        ['value' => 'unknown', 'label' => 'Unknown'],
                    ],
                ],
            ],
            'insights' => $insights,
        ]);
    }
}
