<?php

return [
    'rate_limit' => [
        'per_map' => [
            'max_attempts' => env('CONDITION_TIMER_RATE_LIMIT_PER_MAP', 45),
            'decay_seconds' => env('CONDITION_TIMER_RATE_LIMIT_PER_MAP_DECAY', 60),
        ],
        'per_token' => [
            'max_attempts' => env('CONDITION_TIMER_RATE_LIMIT_PER_TOKEN', 12),
            'decay_seconds' => env('CONDITION_TIMER_RATE_LIMIT_PER_TOKEN_DECAY', 60),
        ],
        'lockout_decay_seconds' => env('CONDITION_TIMER_RATE_LIMIT_LOCKOUT_DECAY', 300),
    ],

    'circuit_breaker' => [
        'conflict_ratio' => env('CONDITION_TIMER_CONFLICT_RATIO', 0.6),
        'minimum_conflicts' => env('CONDITION_TIMER_MINIMUM_CONFLICTS', 3),
        'cooldown_seconds' => env('CONDITION_TIMER_CIRCUIT_COOLDOWN', 120),
    ],
];
