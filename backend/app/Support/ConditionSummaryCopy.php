<?php

namespace App\Support;

class ConditionSummaryCopy
{
    /**
     * @var array<string, array<string, string>>
     */
    protected static array $templates = [
        'blinded' => [
            'calm' => ':target blinks through drifting shadow yet follows allies\' calls.',
            'warning' => 'Darkness presses against :target; each stride risks a stumble.',
            'critical' => 'Unless light returns now, :target will be left stumbling blind.',
        ],
        'burning' => [
            'calm' => ':target dances with embers that can be snuffed with a swift action.',
            'warning' => 'Flames cling tighter to :target, the heat rising with each breath.',
            'critical' => 'Fire roars around :target, threatening to consume them if no aid arrives this round.',
        ],
        'charmed' => [
            'calm' => ':target hums along with a foreign melody, their guard softened.',
            'warning' => 'A distant voice tugs at :target\'s will; loyalty wavers like candlelight.',
            'critical' => ':target\'s eyes glaze—the command will take hold unless allies intervene immediately.',
        ],
        'cursed' => [
            'calm' => 'An uneasy sigil flickers above :target, whispering faint omens.',
            'warning' => 'The curse tightens around :target, warping luck and drawing unseen eyes.',
            'critical' => 'Fate coils to strike :target; the curse is moments from triggering its darkest demand.',
        ],
        'deafened' => [
            'calm' => 'Ringing muffles the world for :target, yet familiar rhythms keep them steady.',
            'warning' => 'Sound slips away from :target; signals blur amid the muffled din.',
            'critical' => 'If hearing doesn\'t return now, :target will be cut off from battle commands.',
        ],
        'exhaustion' => [
            'calm' => 'Weariness tugs at :target, but determination keeps them upright.',
            'warning' => ':target staggers as exhaustion erodes precision.',
            'critical' => 'Every step from :target is a battle; collapse is imminent without respite.',
        ],
        'bleeding' => [
            'calm' => 'Thin ribbons of blood trail :target, manageable with quick pressure.',
            'warning' => 'Blood flows freely from :target; each heartbeat spills more strength.',
            'critical' => ':target is losing blood fast—stop the wound now or watch life fade.',
        ],
        'frozen' => [
            'calm' => 'Hoarfrost kisses :target\'s gear, slowing movement but not resolve.',
            'warning' => 'Ice grips :target\'s limbs; each motion risks shattering strength like glass.',
            'critical' => ':target is entombed in biting ice—only decisive force will keep their heart beating.',
        ],
        'frightened' => [
            'calm' => 'Shadows loom larger for :target, but courage still steadies their grip.',
            'warning' => 'Terror tightens its claws; :target trembles toward retreat.',
            'critical' => 'Panic overtakes :target; another breath and they will flee outright.',
        ],
        'grappled' => [
            'calm' => 'Tethers slow :target\'s footing, though leverage could free them soon.',
            'warning' => 'The hold cinches tighter on :target; each motion shrinks their options.',
            'critical' => 'Seconds remain before :target is pinned unless the grip is broken now.',
        ],
        'hexed' => [
            'calm' => 'Arcane motes orbit :target, marking them for mischief.',
            'warning' => 'The hex focuses, twisting fate against :target.',
            'critical' => 'Doom converges on :target; the hex is about to unleash its cruel promise.',
        ],
        'incapacitated' => [
            'calm' => 'A numbing fog drapes over :target; focus flickers but remains within reach.',
            'warning' => ':target\'s limbs refuse to answer; consciousness wavers at the edge.',
            'critical' => 'If aid doesn\'t arrive, :target will slip fully into helpless stillness.',
        ],
        'invisible' => [
            'calm' => ':target shimmers at the edges, guided only by whispered cues.',
            'warning' => 'The veil thickens around :target; they may drift beyond friendly sight.',
            'critical' => 'Another heartbeat and :target will vanish entirely without an anchor.',
        ],
        'paralyzed' => [
            'calm' => 'Stiffness crawls along :target\'s limbs, resisted by iron focus.',
            'warning' => ':target stands locked in place; the paralysis tightens its hold.',
            'critical' => 'Without immediate aid, :target will remain helpless before the next strike.',
        ],
        'petrified' => [
            'calm' => 'Stone motes cling to :target\'s skin, slowing motion but not resolve.',
            'warning' => 'Marble creeps along :target\'s limbs; weight builds with every breath.',
            'critical' => 'Moments remain before :target becomes a statue unless the spell is broken.',
        ],
        'petrifying' => [
            'calm' => 'Stone flecks pepper :target\'s skin, hinting at a slow hardening.',
            'warning' => 'The petrification spreads; :target\'s limbs stiffen into marble.',
            'critical' => ':target is moments from becoming a statue unless the spell is broken now.',
        ],
        'poisoned' => [
            'calm' => 'A sickly sheen settles over :target, but a steady stance keeps the venom at bay for now.',
            'warning' => ':target winces as venom creeps deeper; stamina is fading with every passing moment.',
            'critical' => 'Venom floods :target\'s veins—collapse is imminent without immediate aid.',
        ],
        'prone' => [
            'calm' => ':target fights from one knee, poised to spring upright when the opening comes.',
            'warning' => ':target sprawls across the ground; stray blows loom if they cannot rise soon.',
            'critical' => 'Without a swift assist, :target will be defenseless on the floor when the next strike lands.',
        ],
        'restrained' => [
            'calm' => 'Ethereal bindings slow :target—careful effort could slip them free.',
            'warning' => 'The bonds cinch tighter, biting into :target\'s resolve.',
            'critical' => ':target can scarcely move; liberation must come this turn or not at all.',
        ],
        'stunned' => [
            'calm' => 'Stars dance before :target\'s eyes, but their grip on reality holds.',
            'warning' => 'A sharp ringing steals :target\'s focus; everything slows to a crawl.',
            'critical' => 'If the stupor lingers, :target will stand defenseless for the next assault.',
        ],
        'time_warped' => [
            'calm' => ':target drifts half a heartbeat out of sync with the world.',
            'warning' => 'Seconds slip between :target\'s fingers; allies appear to blur ahead.',
            'critical' => 'Reality frays around :target; without intervention they will vanish for a cycle.',
        ],
        'unconscious' => [
            'calm' => ':target slumps yet breathes steady; a firm shake could draw them back.',
            'warning' => ':target\'s breathing falters; they hover between worlds awaiting aid.',
            'critical' => 'Life slips from :target; urgent healing is needed this very round.',
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
