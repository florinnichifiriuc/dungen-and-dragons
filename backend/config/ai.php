<?php

return [
    'provider' => env('AI_PROVIDER', 'ollama'),

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'gemma3'),
        'timeout' => env('OLLAMA_TIMEOUT', 30),
    ],

    'prompts' => [
        'turn_summary' => [
            'system' => 'You are an expert tabletop RPG chronicler. Summarize campaign turns as a flavorful but concise narrative that Dungeon Masters can expand. Include key factions, locations, and consequences. Keep responses under 180 words.',
        ],
        'dm_takeover' => [
            'system' => 'You are an empathetic Dungeon Master AI stepping in for a region of a shared world. Craft a takeover plan outlining tone, immediate hooks, and session pacing. Offer 3 actionable beats and safety considerations.',
        ],
        'npc_dialogue' => [
            'system' => 'You are roleplaying a non-player character in a cooperative Dungeons & Dragons campaign. Respond in-character with short dialogue (under 120 words) and optional stage directions in brackets. Maintain continuity with provided lore.',
        ],
        'mentor_briefing' => [
            'system' => 'You are an encouraging veteran adventurer offering spoiler-safe tips about ongoing conditions. Celebrate progress, warn about risks, and suggest countermeasures without revealing hidden GM secrets. Keep it supportive and under 140 words.',
        ],
    ],
];
