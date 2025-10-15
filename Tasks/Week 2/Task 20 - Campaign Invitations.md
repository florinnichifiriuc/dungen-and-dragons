# Task 20 – Campaign Invitation Acceptance Flow

**Status:** Completed
**Owner:** Engineering
**Dependencies:** Task 4 (Campaign Management), Task 7 (Group Management), Task 19 (Quest Log)

## Objective
Let campaign managers share invitation links that targeted users or allied group leaders can review and accept. Acceptance should validate eligibility, activate the requested role assignment, and onboard individuals into the owning group when necessary.

## Deliverables
- Policy-backed controller endpoints for viewing and accepting invitation tokens.
- Inertia acceptance page with campaign context and single-click confirmation.
- Campaign dashboard updates surfacing copyable invitation links only to managers.
- Automated Pest coverage verifying email invites, group invites, and unauthorized attempts.

## Implementation Checklist
- [x] Register a `CampaignInvitationPolicy` and secure acceptance endpoints.
- [x] Build `CampaignInvitationAcceptController` with transactional assignment logic.
- [x] Add Inertia UI for invitation acceptance plus dashboard copy-link controls.
- [x] Cover email/group acceptance paths and rejections with feature tests.
- [x] Refresh documentation, task plan, and progress log entries.

## Log
- 2025-10-23 13:15 UTC – Scoped acceptance flow requirements and policy surface.
- 2025-10-23 15:10 UTC – Shipped acceptance endpoints, Inertia UI, copy-link tooling, and Pest coverage.
