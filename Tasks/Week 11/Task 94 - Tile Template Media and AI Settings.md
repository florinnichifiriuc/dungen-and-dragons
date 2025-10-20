# Task 94 – Tile Template Media and AI Settings

**Status:** Planned
**Owner:** Engineering & Art Pipeline
**Dependencies:** Task 9, Task 13

## Intent
Expand the tile template library so facilitators can upload bespoke imagery, tweak tile classifications, and leverage AI-assisted configuration suggestions that align with target regions and encounter types.

## Subtasks
- [ ] Add file upload support for tile template thumbnails and 512×512 map tiles, including validation, storage, and CDN caching notes.
- [ ] Extend the template editor to adjust tile categories, terrain traits, and encounter tags with immediate preview feedback.
- [ ] Integrate an AI helper that proposes tile metadata from short text prompts and can optionally request image variations via the a1111 pipeline when enabled.
- [ ] Update documentation to cover supported formats, size limits, and AI-assisted workflows for tile curation.

## Notes
- Coordinate with infrastructure on storage quotas and optional Stable Diffusion (a1111) integration toggles per environment.
- Ensure uploaded art respects existing color palette guidance and includes alt text for accessibility.
- Provide rollback controls so facilitators can revert AI-suggested settings before saving.

## Log
- 2025-11-26 09:35 UTC – Captured feedback calling for richer tile media management and AI-powered configuration support.
