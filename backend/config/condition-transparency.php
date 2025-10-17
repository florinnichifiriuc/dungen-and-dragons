<?php

return [
    'exports' => [
        'storage_disk' => env('CONDITION_TRANSPARENCY_EXPORT_DISK', 'local'),
        'storage_path' => env('CONDITION_TRANSPARENCY_EXPORT_PATH', 'exports/condition-transparency'),
        'default_visibility' => env('CONDITION_TRANSPARENCY_EXPORT_VISIBILITY', 'counts'),
        'default_format' => env('CONDITION_TRANSPARENCY_EXPORT_FORMAT', 'csv'),
        'webhook_min_interval_seconds' => (int) env('CONDITION_TRANSPARENCY_WEBHOOK_MIN_INTERVAL', 60),
        'slack_webhook' => env('CONDITION_TRANSPARENCY_SLACK_WEBHOOK'),
    ],
    'webhooks' => [
        'signature_header' => 'X-Condition-Transparency-Signature',
    ],
    'mentor_briefings' => [
        'cache_ttl_minutes' => (int) env('CONDITION_MENTOR_BRIEFING_CACHE_MINUTES', 30),
        'max_entries' => (int) env('CONDITION_MENTOR_BRIEFING_MAX_ENTRIES', 4),
    ],
];
