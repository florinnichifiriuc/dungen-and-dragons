# Task 2 – Authentication Foundations

**Status:** Not Started  
**Owner:** Engineering  
**Dependencies:** Task 1 completion  
**Related Backlog Items:** Implement user auth with email/password and OAuth (Google, Discord)

## Objective
Provide secure authentication for the platform by configuring Laravel Sanctum for session/SPA auth, preparing OAuth integration points, and exposing controllers for registration, login, logout, and user profile retrieval. Align the Inertia frontend with shared props for the authenticated user and reusable form components.

## Deliverables
- Laravel auth controllers, routes, and form requests for email/password flows.
- Sanctum configuration with stateful domains and CSRF protection.
- Inertia pages/components for auth forms with shared props for the authenticated user.
- Test coverage via Pest feature tests and Vitest/Playwright smoke tests (deferred for Week 1).

## Implementation Checklist
- [ ] Enable Sanctum and publish config.
- [ ] Create Auth controllers (RegisterController, LoginController, LogoutController, MeController).
- [ ] Write FormRequest validation rules and responses.
- [ ] Seed default admin/GM account for demos.
- [ ] Inertia pages: set up auth forms, validation error rendering, and shared auth props.
- [ ] Update documentation (README auth section, PROGRESS_LOG entry).

## Log
- _Pending update when task starts._
