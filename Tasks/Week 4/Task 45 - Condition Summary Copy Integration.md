# Task 45 – Condition Summary Copy Integration

**Status:** Completed
**Owner:** Narrative & Engineering
**Dependencies:** Task 39, Task 43

## Intent
Bring the narrative copy deck into parity with the condition timer projection so every supported status effect renders immersive, spoiler-safe text for players while respecting faction redactions.

## Subtasks
- [x] Audit `MapToken::CONDITIONS` against the deck and note gaps.
- [x] Expand `ConditionSummaryCopy` templates to cover each supported condition with calm/warning/critical tones.
- [x] Update the narrative copy deck documentation to mirror the finalized templates.
- [x] Add regression tests confirming all conditions map to non-empty copy variants.
- [x] Log the delivery in `PROGRESS_LOG.md` and mark the task complete.

## Notes
- Keep copy within 160 characters to avoid truncation on mobile recap widgets.
- Avoid referencing hidden antagonists directly—use neutral phrases like “unseen force” when faction visibility is obscured.
- Default template should remain lore-neutral for future custom conditions.

## Log
- 2025-11-01 09:00 UTC – Identified mismatch between documented copy deck and runtime templates; queued integration task.
- 2025-11-01 10:45 UTC – Synced code, docs, and regression coverage so every supported condition renders immersive copy.
