# Task 76 – Maintenance Threshold Configuration

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 72

## Intent
Centralize maintenance thresholds in configuration so operations can tweak quiet-hour ratios and expiry warnings without redeploying code.

## Subtasks
- [x] Add `maintenance` block to `config/condition-transparency.php` with window, ratio, and expiry controls.
- [x] Wire snapshot and command logic to respect the configurable thresholds.
- [x] Document default values alongside override guidance.

## Notes
- Thresholds default to 7-day windows, 40% quiet-hour ratio, and 24-hour expiry warnings; environment variables support overrides.

## Log
- 2025-11-21 17:05 UTC – Landed maintenance configuration knobs and updated service references.
