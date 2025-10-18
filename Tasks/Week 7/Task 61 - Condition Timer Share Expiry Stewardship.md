# Task 61 – Condition Timer Share Expiry Stewardship

**Status:** Completed
**Owner:** Product & Engineering
**Dependencies:** Tasks 49, 57

## Intent
Give facilitators finer control over share link lifetimes so risky outlooks expire predictably without manual database edits. Build configurable expiry presets, lifecycle warnings, and gentle extension prompts directly into the manager UI while honoring consent policies.

## Subtasks
- [x] Surface configurable expiry inputs when minting or editing a share link, including recommended presets and custom UTC durations.
- [x] Introduce preset bundles that pair expiry, visibility, and consent defaults (e.g., "one-shot preview", "extended ally access") with preview confirmation modal.
- [x] Display upcoming expiry state (active, expiring soon, expired) with color-coded messaging in both the manager controls and guest share summaries.
- [x] Provide a manager action to extend the existing share without issuing a new token, logging the change in access trails and preset usage metrics.
- [x] Ensure expired links older than 48 hours automatically redact timer payloads until re-enabled or cloned.

## Notes
- Continue using UTC for all lifecycle calculations and align with quiet-hour suppression logic from Task 50.
- Treat extensions as idempotent updates—only adjust expiry timestamps when explicitly requested.
- Coordinate with localization to mirror new status copy in all supported locales.
- Bundle definitions should be shareable with QA (Task 59) for scenario scripts and analytics teams (Task 62) for preset adoption tracking.

## Log
- 2025-11-05 09:10 UTC – Scoped expiry stewardship objectives while addressing focus group requests for longer-lived expedition briefings.
- 2025-11-05 13:45 UTC – Logged need for extension affordances after QA flagged manual DB edits during Task 49 testing.
- 2025-11-07 10:55 UTC – Delivered share extension controls, evergreen preset, and 48-hour redaction guardrails on expired links.
- 2025-11-12 16:15 UTC – Expanded scope per PO & focus group sync to add preset bundles, confirmation preview, and preset telemetry hooks.
- 2025-11-15 17:15 UTC – Finalized preset bundle catalog, localization strings, and expiry stewardship metrics for facilitator dashboards and exports.
