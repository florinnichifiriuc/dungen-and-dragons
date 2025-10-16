# Condition Timer Narrative Copy Deck

The snippets below power player-facing condition summaries. Each entry is organized by urgency tier and mirrors the runtime templates in `App\\Support\\ConditionSummaryCopy`. Keep copy immersive, spoiler-safe, and short enough for mobile layouts.

## Placeholder Key
- `:target` – Player-visible name or codename for the affected token.

## Copy Templates
### Blinded
- **Calm:** `:target blinks through drifting shadow yet follows allies' calls.`
- **Warning:** `Darkness presses against :target; each stride risks a stumble.`
- **Critical:** `Unless light returns now, :target will be left stumbling blind.`

### Burning
- **Calm:** `:target dances with embers that can be snuffed with a swift action.`
- **Warning:** `Flames cling tighter to :target, the heat rising with each breath.`
- **Critical:** `Fire roars around :target, threatening to consume them if no aid arrives this round.`

### Charmed
- **Calm:** `:target hums along with a foreign melody, their guard softened.`
- **Warning:** `A distant voice tugs at :target's will; loyalty wavers like candlelight.`
- **Critical:** `:target's eyes glaze—the command will take hold unless allies intervene immediately.`

### Cursed
- **Calm:** `An uneasy sigil flickers above :target, whispering faint omens.`
- **Warning:** `The curse tightens around :target, warping luck and drawing unseen eyes.`
- **Critical:** `Fate coils to strike :target; the curse is moments from triggering its darkest demand.`

### Deafened
- **Calm:** `Ringing muffles the world for :target, yet familiar rhythms keep them steady.`
- **Warning:** `Sound slips away from :target; signals blur amid the muffled din.`
- **Critical:** `If hearing doesn't return now, :target will be cut off from battle commands.`

### Exhaustion
- **Calm:** `Weariness tugs at :target, but determination keeps them upright.`
- **Warning:** `:target staggers as exhaustion erodes precision.`
- **Critical:** `Every step from :target is a battle; collapse is imminent without respite.`

### Bleeding
- **Calm:** `Thin ribbons of blood trail :target, manageable with quick pressure.`
- **Warning:** `Blood flows freely from :target; each heartbeat spills more strength.`
- **Critical:** `:target is losing blood fast—stop the wound now or watch life fade.`

### Frozen
- **Calm:** `Hoarfrost kisses :target's gear, slowing movement but not resolve.`
- **Warning:** `Ice grips :target's limbs; each motion risks shattering strength like glass.`
- **Critical:** `:target is entombed in biting ice—only decisive force will keep their heart beating.`

### Frightened
- **Calm:** `Shadows loom larger for :target, but courage still steadies their grip.`
- **Warning:** `Terror tightens its claws; :target trembles toward retreat.`
- **Critical:** `Panic overtakes :target; another breath and they will flee outright.`

### Grappled
- **Calm:** `Tethers slow :target's footing, though leverage could free them soon.`
- **Warning:** `The hold cinches tighter on :target; each motion shrinks their options.`
- **Critical:** `Seconds remain before :target is pinned unless the grip is broken now.`

### Hexed
- **Calm:** `Arcane motes orbit :target, marking them for mischief.`
- **Warning:** `The hex focuses, twisting fate against :target.`
- **Critical:** `Doom converges on :target; the hex is about to unleash its cruel promise.`

### Incapacitated
- **Calm:** `A numbing fog drapes over :target; focus flickers but remains within reach.`
- **Warning:** `:target's limbs refuse to answer; consciousness wavers at the edge.`
- **Critical:** `If aid doesn't arrive, :target will slip fully into helpless stillness.`

### Invisible
- **Calm:** `:target shimmers at the edges, guided only by whispered cues.`
- **Warning:** `The veil thickens around :target; they may drift beyond friendly sight.`
- **Critical:** `Another heartbeat and :target will vanish entirely without an anchor.`

### Paralyzed
- **Calm:** `Stiffness crawls along :target's limbs, resisted by iron focus.`
- **Warning:** `:target stands locked in place; the paralysis tightens its hold.`
- **Critical:** `Without immediate aid, :target will remain helpless before the next strike.`

### Petrified
- **Calm:** `Stone motes cling to :target's skin, slowing motion but not resolve.`
- **Warning:** `Marble creeps along :target's limbs; weight builds with every breath.`
- **Critical:** `Moments remain before :target becomes a statue unless the spell is broken.`

### Petrifying
- **Calm:** `Stone flecks pepper :target's skin, hinting at a slow hardening.`
- **Warning:** `The petrification spreads; :target's limbs stiffen into marble.`
- **Critical:** `:target is moments from becoming a statue unless the spell is broken now.`

### Poisoned
- **Calm:** `A sickly sheen settles over :target, but a steady stance keeps the venom at bay for now.`
- **Warning:** `:target winces as venom creeps deeper; stamina is fading with every passing moment.`
- **Critical:** `Venom floods :target's veins—collapse is imminent without immediate aid.`

### Prone
- **Calm:** `:target fights from one knee, poised to spring upright when the opening comes.`
- **Warning:** `:target sprawls across the ground; stray blows loom if they cannot rise soon.`
- **Critical:** `Without a swift assist, :target will be defenseless on the floor when the next strike lands.`

### Restrained
- **Calm:** `Ethereal bindings slow :target—careful effort could slip them free.`
- **Warning:** `The bonds cinch tighter, biting into :target's resolve.`
- **Critical:** `:target can scarcely move; liberation must come this turn or not at all.`

### Stunned
- **Calm:** `Stars dance before :target's eyes, but their grip on reality holds.`
- **Warning:** `A sharp ringing steals :target's focus; everything slows to a crawl.`
- **Critical:** `If the stupor lingers, :target will stand defenseless for the next assault.`

### Time Warped
- **Calm:** `:target drifts half a heartbeat out of sync with the world.`
- **Warning:** `Seconds slip between :target's fingers; allies appear to blur ahead.`
- **Critical:** `Reality frays around :target; without intervention they will vanish for a cycle.`

### Unconscious
- **Calm:** `:target slumps yet breathes steady; a firm shake could draw them back.`
- **Warning:** `:target's breathing falters; they hover between worlds awaiting aid.`
- **Critical:** `Life slips from :target; urgent healing is needed this very round.`

## Redaction Guidance
- Avoid naming hidden antagonists or revealing secret triggers; lean on phrases such as "unseen force" or "veiled presence."
- Keep environmental cues broad unless the source is visible to the player audience.
- Maintain gender-neutral language and limit sentences to 160 characters or fewer.

## Localization & Tone Notes
- Provide translator context for condition names, urgency tiers, and placeholder meanings when exporting to Lokalise.
- Preserve em dashes and commas to match runtime typography and keep pacing consistent with in-app narration.

## QA Checklist
- Proofread for spelling (en-US) and consistent Oxford commas.
- Verify each condition maps to `ConditionSummaryCopy` and update tests when new keys are added.
- Ensure no copy leaks GM-only intel; cross-check with projection redaction rules before release.
- Spot-check localized variants once available; confirm placeholders remain intact.

## Integration Steps
1. Sync this deck with `ConditionSummaryCopy` constants.
2. Expose urgency tiers to the analytics pipeline (Task 44) to enable A/B testing of variant phrasing.
3. When AI summaries are enabled, allow Gemma3 to append two-sentence flourishes seeded by the copy above.
