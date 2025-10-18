<?php

use App\Jobs\TriggerPagerDutyIncidentJob;
use App\Models\BugReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('pagerduty incident job posts payload to events api', function () {
    Config::set('bug-reporting.pagerduty.routing_key', 'routing-key');
    Http::fake();

    $report = BugReport::factory()->create([
        'priority' => BugReport::PRIORITY_CRITICAL,
        'status' => BugReport::STATUS_OPEN,
        'summary' => 'Condition timer stalled',
    ]);

    $job = new TriggerPagerDutyIncidentJob($report->id);

    $job->handle();

    Http::assertSent(function ($request) use ($report) {
        $data = $request->data();

        return $request->url() === 'https://events.pagerduty.com/v2/enqueue'
            && data_get($data, 'routing_key') === 'routing-key'
            && data_get($data, 'payload.summary') === sprintf('Bug %s: %s', $report->reference, $report->summary)
            && filled(data_get($data, 'links.0.href'));
    });
});
