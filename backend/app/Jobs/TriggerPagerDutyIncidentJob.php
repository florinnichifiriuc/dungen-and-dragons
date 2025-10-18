<?php

namespace App\Jobs;

use App\Models\BugReport;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriggerPagerDutyIncidentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int|string $bugReportId)
    {
    }

    public function handle(): void
    {
        $routingKey = (string) Config::get('bug-reporting.pagerduty.routing_key');

        if ($routingKey === '') {
            Log::info('pagerduty_incident_skipped', ['reason' => 'missing-routing-key']);

            return;
        }

        $report = BugReport::query()->find($this->bugReportId);

        if (! $report) {
            Log::warning('pagerduty_incident_skipped', ['reason' => 'missing-report', 'report_id' => $this->bugReportId]);

            return;
        }

        $severity = Config::get("bug-reporting.pagerduty.severity_overrides.{$report->priority}", 'error');

        $payload = [
            'routing_key' => $routingKey,
            'event_action' => 'trigger',
            'payload' => [
                'summary' => sprintf('Bug %s: %s', $report->reference, $report->summary),
                'severity' => $severity,
                'source' => config('app.url', 'dungen-and-dragons'),
                'timestamp' => CarbonImmutable::now('UTC')->toIso8601String(),
                'component' => 'bug-reporting',
                'custom_details' => [
                    'priority' => $report->priority,
                    'status' => $report->status,
                    'context' => $report->context_type,
                    'reference' => $report->reference,
                ],
            ],
            'links' => [
                [
                    'href' => route('admin.bug-reports.show', $report),
                    'text' => 'Open bug triage dashboard',
                ],
            ],
        ];

        Http::retry(3, 250)
            ->asJson()
            ->post('https://events.pagerduty.com/v2/enqueue', $payload);

        Log::info('pagerduty_incident_triggered', [
            'report_id' => $report->id,
            'reference' => $report->reference,
            'severity' => $severity,
        ]);
    }
}
