# Task 49 – Condition Timer Summary Share Links

## Intent
Let facilitators generate time-bound, shareable condition outlook links so party members can review the latest timers without logging into the workspace, while keeping exports and session surfaces aligned.

## Background & Alignment
- Extends transparency initiative by pairing projection privacy rules with secure distribution mechanics.
- Builds on Task 48 by ensuring the exported outlook references the same shareable channel players receive.
- Keeps share access scoped to facilitators while allowing players to open the shared view even if they are not authenticated.

## Success Criteria
- Group managers can mint and revoke signed share links for the condition timer summary.
- Public share route renders the player-safe outlook (no acknowledgements, no privileged timeline details) using the existing summary components.
- Session and group condition summary views surface share link state plus controls for facilitators.
- Markdown/PDF exports list the share link (and expiry when present) alongside the Active Condition Outlook.
- Feature coverage validates share lifecycle (create, revoke, expire) and export output includes the link.

## Implementation Notes
1. Add `condition_timer_summary_shares` table and Eloquent model with soft deletes + expiry support.
2. Create a share service/controller handling signed token creation, revocation, and guest rendering with projector hydration.
3. Layer share management UI into session + group condition panels and expose share metadata to Markdown/PDF generators.
4. Ensure acknowledgement buttons disable on shared pages to avoid CSRF failures and unauthorized writes.
5. Cover share happy path, authorization, and expiry logic with Pest specs, plus update Session export assertions.

## Status
Completed

## Log
- 2025-11-04 09:05 UTC – Hardened share revocation routing and Inertia guest headers so condition outlook links stay reliable in tests.
