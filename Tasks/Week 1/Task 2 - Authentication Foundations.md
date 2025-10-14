# Task 2 – Authentication Foundations

**Status:** Not Started  
**Owner:** Engineering  
**Dependencies:** Task 1 completion  
**Related Backlog Items:** Implement user auth with email/password and OAuth (Google, Discord)

## Objective
Provide secure authentication for the platform by configuring Laravel Sanctum for SPA token auth, preparing OAuth integration points, and exposing REST endpoints for registration, login, logout, and user profile retrieval. Align frontend with React Query mutations and persistent auth state.

## Deliverables
- Laravel auth controllers, routes, and form requests for email/password flows.
- Sanctum configuration with stateful domains and CSRF protection.
- React services/hooks for auth actions and protected route handling.
- Test coverage via Pest feature tests and Vitest/Playwright smoke tests (deferred for Week 1).

## Implementation Checklist
- [ ] Enable Sanctum and publish config.
- [ ] Create Auth controllers (RegisterController, LoginController, LogoutController, MeController).
- [ ] Write FormRequest validation rules and responses.
- [ ] Seed default admin/GM account for demos.
- [ ] Frontend: set up auth API client, React Query mutations, context/store for user session.
- [ ] Update documentation (README auth section, PROGRESS_LOG entry).

## Log
- _Pending update when task starts._
