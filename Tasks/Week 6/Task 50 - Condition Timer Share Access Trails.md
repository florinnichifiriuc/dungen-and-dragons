# Task 50 – Condition Timer Share Access Trails

## Intent
Give facilitators visibility into how often condition outlook links are opened so they can monitor engagement and rotate credentials if unexpected traffic appears.

## Background & Alignment
- Builds on Task 49’s sharing mechanics by layering basic telemetry into the transparency workflow.
- Supports research goals from Task 44 by giving DMs a lightweight proxy for player trust and participation.
- Keeps sensitive details masked (no full IP storage) while surfacing enough context to trigger operational follow-ups.

## Success Criteria
- Every share link view writes an immutable access record with timestamp, IP mask, and user agent details.
- Managers can review access totals, last-opened timestamp, and a short list of recent guests in the share controls UI.
- Markdown/PDF exports include the access overview alongside the share link metadata.
- Regression coverage confirms logging, aggregation, masking, and presentation across manager dashboards and exports.

## Implementation Notes
1. Add `condition_timer_summary_share_accesses` table, model, and relation on shares.
2. Extend the share service with record + presentation helpers that mask IP data and aggregate access statistics.
3. Hook access logging into the public share controller and surface stats in Inertia manager views plus exports.
4. Update tests to assert access tracking, masked metadata, and export output.

## Status
Completed

## Log
- 2025-11-04 16:20 UTC – Logged access records, surfaced share analytics in UI/exports, and added coverage for statistics + masking.
