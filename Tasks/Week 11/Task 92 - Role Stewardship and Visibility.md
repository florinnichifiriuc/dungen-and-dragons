# Task 92 – Role Stewardship and Visibility

**Status:** Planned
**Owner:** Engineering & Product
**Dependencies:** Task 4, Task 7

## Intent
Clarify how administrators manage campaign roles by shipping a seeded super-admin, an intuitive management console, and visible role indicators so facilitators can delegate without database access and players know their current permissions.

## Subtasks
- [ ] Extend database seeders to provision an administrative facilitator account with secure defaults and onboarding copy that directs operators to role tooling.
- [ ] Deliver an admin-only Inertia interface for assigning and revoking roles across groups, campaigns, and system scopes, including audit logging.
- [ ] Surface role badges in the global navigation and profile menu so users can quickly confirm their access level.
- [ ] Document the seeding workflow, admin capabilities, and guardrails within the operations handbook.

## Notes
- Coordinate with security to ensure seeded credentials rotate via environment configuration and are excluded from production builds.
- Leverage existing policy classes to authorize role mutations and avoid duplicating checks.
- Capture UX copy that reassures players about how role changes affect their abilities during play.

## Log
- 2025-11-26 09:15 UTC – Logged stakeholder feedback about confusing role setup and kicked off admin stewardship planning.
