# Task 18 – Lore Tags & Quick Reference

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 17 (Lore Codex)
**Related Backlog Items:** Lore discovery aids & dashboard summaries

## Intent
Enhance the lore codex with thematic tagging, filtering, and quick-reference surfacing. Allow managers to curate reusable tags, filter entities by type or tag, and surface recent lore on the campaign dashboard so facilitators can jump into the right context during prep and play.

## Subtasks
- [x] Add tag and pivot migrations plus Tag model relationships for campaign entities.
- [x] Extend codex controllers and requests to normalize tags, auto-create colors, and sync assignments on create/update.
- [x] Upgrade index page with search, type and tag filters, chips, and tag editing widgets in create/edit flows.
- [x] Surface a lore codex summary and recent entries on the campaign overview with quick navigation.
- [x] Expand Pest coverage for tag syncing, authorization, and filtering behaviours.

## Notes
- Tag colors derive deterministically from the label hash so palettes stay consistent without manual styling.
- Lore filters use GET params to support shareable codex views and keep Inertia state in sync with search inputs.
- The campaign dashboard now shows counts and recent lore entries to highlight prep hotspots.

## Log
- 2025-10-22 08:30 UTC – Planned tagging UX, dashboard summary, and test coverage requirements.
- 2025-10-22 10:20 UTC – Implemented tag models, filters, UI enhancements, dashboard summaries, and tests.
