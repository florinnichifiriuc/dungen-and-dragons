# Task 95 – Region Map Canvas and AI Design

**Status:** Planned
**Owner:** Engineering & UX Design
**Dependencies:** Task 9, Task 24, Task 25

## Intent
Transform the region map editor into a visual canvas with drag-and-drop tooling, layered drawing aids, and AI-guided layout suggestions so dungeon masters can rapidly prototype playable regions without deciphering abstract controls.

## Subtasks
- [ ] Replace the current configuration-first view with a canvas-based editor that supports drawing tools, tile placement, and zoom/pan interactions.
- [ ] Provide contextual tips and a walkthrough of fog-of-war controls, including responsive orientation adjustments when the base layer changes.
- [ ] Embed an AI assistant that converts simple text briefs into draft region layouts, optionally triggering a1111 tile generations where available.
- [ ] Run moderated usability sessions and iterate on the onboarding overlay before wide release.

## Notes
- Ensure canvas rendering is accessible and performant on mid-range hardware by leveraging existing tile virtualization patterns.
- Document how fog configuration interacts with the new canvas workflow to demystify the feature for facilitators.
- Coordinate with QA to script regression coverage for drawing, fog toggles, and AI-generated layouts.

## Log
- 2025-11-26 09:45 UTC – Logged requirement to revamp the region map UX and layer in AI-assisted layout drafting.
