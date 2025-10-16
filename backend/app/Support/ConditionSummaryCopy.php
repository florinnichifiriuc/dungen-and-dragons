<?php

namespace App\Support;

class ConditionSummaryCopy
{
    /**
     * @var array<string, array<string, string>>
     */
    protected static array $templates = [
        'restrained' => [
            'calm' => ':target remains bound, breathing steady within the restraints.',
            'warning' => 'Bindings around :target strain and creak; escape feels imminent.',
            'critical' => 'The bonds fray around :target—only heartbeats remain before freedom.',
        ],
        'poisoned' => [
            'calm' => 'Toxic veins trouble :target, though focus keeps the venom at bay.',
            'warning' => ':target sways as the poison seeps deeper—remedies must come soon.',
            'critical' => 'A sharp chill races through :target; the poison is moments from overwhelming them.',
        ],
        'frightened' => [
            'calm' => ':target steels themselves against the dread whispering at the edge of vision.',
            'warning' => 'Shadows clutch at :target; courage wavers as fear sinks talons in.',
            'critical' => ':target trembles, breath shallow—the fear is about to swallow them whole.',
        ],
        'default' => [
            'calm' => ':target carries the effect with measured resolve.',
            'warning' => ':target struggles as the effect tightens its hold.',
            'critical' => ':target teeters on the brink; the effect is seconds from snapping.',
        ],
    ];

    /**
     * @param  array<string, string>  $replacements
     */
    public static function for(string $condition, string $tone, array $replacements = []): string
    {
        $conditionKey = strtolower($condition);
        $templates = self::$templates[$conditionKey] ?? self::$templates['default'];
        $toneKey = array_key_exists($tone, $templates) ? $tone : 'calm';
        $template = $templates[$toneKey];

        foreach ($replacements as $search => $value) {
            $template = str_replace($search, $value, $template);
        }

        return $template;
    }
}
