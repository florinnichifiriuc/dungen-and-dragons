# Task 93 – Region Map Onboarding & AI Planning

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Tasks 9, 24

## Intent
Reduce friction when configuring region maps by making orientation/fog settings intuitive, adding a visual grid preview to creation flows, and introducing an AI helper that can draft map dimensions plus Automatic1111 prompts before a map exists.

## Subtasks
- [x] Expand map validation/orientation handling so base layer changes automatically unlock the correct orientation set.
- [x] Mirror the edit-time grid preview and fog guidance on the create form with responsive canvas rendering.
- [x] Add an AI planning endpoint usable before map creation and wire it into the create form with apply actions.
- [x] Document how the AI prompt can generate 512×512 tiles or region renders via Automatic1111.

## Notes
- Ensure new controller respects existing authorization—only group owners/DMs should invoke map planning.
- Keep the preview lightweight (no heavy libraries) so it works offline and during demos.
- When applying AI suggestions, never overwrite existing user input without confirmation logic.

## Log
- 2025-11-26 10:10 UTC – Captured feedback that map setup felt opaque; queued orientation and AI preview upgrades for implementation.
- 2025-11-26 14:35 UTC – Extended map validation/orientation handling, mirrored the preview canvas on creation, added a pre-create AI planning endpoint, and documented the Automatic1111 prompt workflow.
