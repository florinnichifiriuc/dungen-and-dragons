<?php

return [
    'watchers' => array_filter(array_map('trim', explode(',', (string) env('BUG_REPORT_WATCHERS', '')))),
    'slack_webhooks' => array_filter(array_map('trim', explode(',', (string) env('BUG_REPORT_SLACK_WEBHOOKS', '')))),
    'pagerduty' => [
        'routing_key' => env('BUG_REPORT_PAGERDUTY_ROUTING_KEY'),
        'severity_overrides' => [
            'critical' => 'critical',
            'high' => 'error',
        ],
    ],
    'quiet_hours' => [
        'start' => env('BUG_REPORT_QUIET_HOURS_START', '02:00'),
        'end' => env('BUG_REPORT_QUIET_HOURS_END', '07:00'),
        'timezone' => env('BUG_REPORT_QUIET_HOURS_TIMEZONE', 'UTC'),
    ],
    'digest_time' => env('BUG_REPORT_DIGEST_TIME', '08:00'),
    'digest_timezone' => env('BUG_REPORT_DIGEST_TIMEZONE', 'UTC'),
    'digest_channels' => array_filter(array_map('trim', explode(',', (string) env('BUG_REPORT_DIGEST_CHANNELS', 'mail')))),
];
