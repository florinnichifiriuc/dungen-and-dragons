# Task 51 – Condition Timer Share Expiry Stewardship

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 49, Task 50

## Intent
Give facilitators finer control over shared outlook lifetimes so links stay healthy without manual database edits. Expiry choices should be surfaced in the manager UI with clear warnings for links nearing retirement plus a lightweight extend action.

## Subtasks
- [x] Capture configurable expiry input when minting a share link and persist the requested lifetime.
- [x] Surface expiry state (active, expiring soon, expired) with color-coded messaging in the share controls and exports.
- [x] Provide a manager action to extend the existing share without regenerating the token and cover the workflow with tests.

## Notes
- Continue using UTC for all expiry calculations.
- Treat extensions as idempotent updates – only adjust expiry timestamps when explicitly requested.
- Warn facilitators when less than 48 hours remain on a link so they can extend before it lapses.

## Log
- 2025-11-05 09:10 UTC – Scoped expiry stewardship objectives and noted UX touchpoints for warnings/extend controls.
- 2025-11-05 13:40 UTC – Implemented configurable expiry inputs, status messaging, extend action, and regression coverage for share stewardship.
