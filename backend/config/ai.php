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
        'world_brief' => [
            'system' => 'You are a collaborative worldbuilding assistant. Respond with JSON containing name, summary, description, default_turn_duration_hours, tips (array), and image_prompt. Keep tone hopeful and gameable.',
        ],
        'region_brief' => [
            'system' => 'You are a region planner for a turn-based campaign. Reply with JSON containing name, summary, description, turn_duration_hours, tips (array), and image_prompt.',
        ],
        'tile_template_brief' => [
            'system' => 'You craft reusable terrain tiles. Return JSON with name, terrain_type, movement_cost, defense_bonus, edge_profile (JSON string), summary, tips (array), and image_prompt.',
        ],
        'map_plan' => [
            'system' => 'You design tactical map plans. Reply with JSON containing summary, width, height, orientation, fog_data (JSON string), tips (array), and image_prompt.',
        ],
        'campaign_task_brief' => [
            'system' => 'You are an optimistic project steward for a tabletop campaign. Return JSON with summary, tasks (array with title and description), tips (array). Keep tasks collaborative.',
        ],
        'lore_brief' => [
            'system' => 'You are a lore keeper. Provide JSON with name, alias, entity_type, description, summary, tags (array), tips (array), and image_prompt.',
        ],
        'quest_brief' => [
            'system' => 'You script cooperative quests. Respond with JSON containing title, summary, description, objectives (array), tips (array), and image_prompt.',
        ],
    ],

    'mocks' => [
        'enabled' => env('AI_MOCKS_ENABLED', false),
        'path' => env('AI_MOCK_FIXTURE_PATH', 'tests/Fixtures/ai'),
        'fixtures' => [
            'summary' => 'The chronicler notes that the party keeps watch while the poisoned grove recovers. Expect a fresh briefing soon.',
            'dm_takeover' => 'The delegate recommends focusing on three beats: stabilize the grove, parley with the warden, and chart a retreat.',
            'npc_dialogue' => '"The grove whispers of balance," the warden murmurs. "Bring the antidote and we shall bargain."',
            'mentor_briefing' => [
                'response' => "Fresh word from the Mentor: rally your healers, cleanse the grove, and celebrate the resilience you have shown.",
                'payload' => [
                    'fixture' => 'default',
                ],
            ],
        ],
    ],
];
