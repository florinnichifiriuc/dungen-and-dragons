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
    'share_links' => [
        'bundles' => [
            'one_shot_preview' => [
                'label' => 'One-shot preview',
                'description' => '24-hour glimpse for quick check-ins with limited detail.',
                'expiry_preset' => '24h',
                'visibility_mode' => 'counts',
            ],
            'extended_allies' => [
                'label' => 'Extended ally access',
                'description' => 'Three-day access with detailed condition context for trusted allies.',
                'expiry_preset' => '72h',
                'visibility_mode' => 'details',
            ],
            'evergreen_scouting' => [
                'label' => 'Evergreen scouting party',
                'description' => 'Never-expiring read-only link for persistent scouting companions.',
                'expiry_preset' => 'never',
                'visibility_mode' => 'counts',
            ],
        ],
    ],
    'webhooks' => [
        'signature_header' => 'X-Condition-Transparency-Signature',
    ],
    'mentor_briefings' => [
        'cache_ttl_minutes' => (int) env('CONDITION_MENTOR_BRIEFING_CACHE_MINUTES', 30),
        'max_entries' => (int) env('CONDITION_MENTOR_BRIEFING_MAX_ENTRIES', 4),
    ],
    'maintenance' => [
        'access_window_days' => (int) env('CONDITION_TRANSPARENCY_MAINTENANCE_ACCESS_DAYS', 7),
        'quiet_hour_attention_ratio' => (float) env('CONDITION_TRANSPARENCY_MAINTENANCE_QUIET_RATIO', 0.4),
        'expiry_attention_hours' => (int) env('CONDITION_TRANSPARENCY_MAINTENANCE_EXPIRY_HOURS', 24),
    ],
];
