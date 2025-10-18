# Task 60 – Condition Timer Share Access Trails

**Status:** Completed
**Owner:** Engineering & Data
**Dependencies:** Tasks 47, 49, 57

## Intent
Give facilitators visibility into how condition outlook links are opened so they can monitor engagement and rotate credentials if suspicious activity appears. Extend the existing summary share links with privacy-safe access trails that surface in exports and future insights dashboards.

## Subtasks
- [x] Design and migrate the `condition_timer_summary_share_accesses` table with masked IP hashes, user agent fingerprints, and access timestamps linked to share tokens.
- [x] Instrument the share controller/service so every view records an immutable access entry, capturing optional signed-in user context and quiet-hour suppression state.
- [x] Emit structured logs/metrics for share views that integrate with current telemetry channels and feed Tasks 62 and 56.
- [x] Update facilitator exports to include access trails, respecting consent toggles and masking rules from Tasks 57 and 55.
- [x] Capture extension/revocation actor metadata in access events so Task 62 can correlate insights with preset bundle usage.

## Notes
- Reuse the existing projection cache—only store share metadata required for auditing to avoid leaking timer payloads.
- Access trails should exclude full IP storage; rely on salted hashes and region inference aligned with privacy commitments.
- Coordinate with security to ensure masked data retention aligns with broader governance policies.
- Surface actor metadata in a dedicated audit projection to keep facilitator dashboards fast while enabling deep dives in Task 62.

## Log
- 2025-11-05 09:10 UTC – Scoped access trail modeling alongside Task 49 rollout to answer focus group concerns about stale link rotation.
- 2025-11-05 16:30 UTC – Captured requirement to surface access trails in exports after facilitator council review.
- 2025-11-07 10:50 UTC – Implemented hashed access log table, trend aggregation, and export payloads with masked identifiers.
- 2025-11-12 16:05 UTC – Added actor metadata requirement post focus group sync so extension telemetry can flow into the insights dashboard workstream.
- 2025-11-15 17:05 UTC – Verified access trail exports include preset metadata, quiet-hour flags, and masked identifiers for beta readiness handoff.
