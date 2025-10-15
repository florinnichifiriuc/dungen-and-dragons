# Task 7 – Group Management

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 3
**Related Backlog Items:** Create group management (create/join/roles) and policies

## Intent
Elevate the existing group feature set so parties can invite, join, and manage roles without database tinkering. Ship intuitive UI flows and guardrails that preserve at least one owner per group while empowering dungeon masters to run their teams.

## Subtasks
- [x] Add join codes to groups and expose join flows (enter code, join as player by default).
- [x] Implement membership administration endpoints (invite existing users, change roles, remove/leave) with robust policies.
- [x] Update Inertia pages to surface roster management tools, join code display, and leave-group action.
- [x] Cover membership flows with feature tests.
- [x] Refresh docs (task plan, progress log) and ensure UX copy aligns with D&D tone.

## Notes
- Join codes should be shareable but revocable in future tasks; generate uppercase alphanumeric codes for now.
- Only owners can promote someone to owner or remove the final owner. Dungeon masters can otherwise manage memberships.
- Members should be able to leave groups themselves when at least one other owner remains.

## Log
- 2025-10-15 17:45 UTC – Implemented join codes, membership policies, Inertia roster tools, and Pest coverage for the full management workflow.
