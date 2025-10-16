# Task 57 – Share Link & Consent Controls

**Status:** Planned
**Owner:** Product & Engineering
**Dependencies:** Tasks 49, 50, 56

## Intent
Enhance shareable condition outlooks with configurable expiry policies, consent tracking, and guest visibility controls so facilitators can tailor transparency while honoring player privacy.

## Subtasks
- [ ] Add configurable expiry presets (24h, 72h, custom) with default recommendations and admin overrides.
- [ ] Provide guest acknowledgement visibility toggles, including anonymized counts or full detail modes.
- [ ] Track explicit consent logs for players opting into shareable summaries and expose audit trails.
- [ ] Extend settings UI for facilitators to manage active links, revoke access, and view activity history.
- [ ] Update policies and middleware to enforce consent checks on every share link request.
- [ ] Cover new flows with tests, including stale cache scenarios flagged in Task 59.

## Notes
- Align with notification/digest preferences to prevent conflicting privacy states.
- Provide in-world copy that clarifies sharing etiquette (e.g., "Only share this scroll with trusted allies").
- Coordinate with legal/compliance to ensure consent language meets requirements.

## Log
- 2025-11-05 17:05 UTC – Captured after facilitators reported aggressive expiry defaults during focus group review.
