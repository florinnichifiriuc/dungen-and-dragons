# Task 2 – Authentication Foundations

**Status:** Completed
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
- [x] Enable Sanctum and publish config.
- [x] Create Auth controllers (RegisterController, LoginController, LogoutController, MeController).
- [x] Write FormRequest validation rules and responses.
- [x] Seed default admin/GM account for demos.
- [x] Inertia pages: set up auth forms, validation error rendering, and shared auth props.
- [x] Update documentation (README auth section, PROGRESS_LOG entry).

## Log
- **2025-10-14 13:05 UTC** – Began implementing Sanctum auth controllers, form requests, and Inertia auth pages; documenting Sail session flow.
- **2025-10-14 13:45 UTC** – Added Sanctum stateful middleware, Axios credentials, and `/api/v1/auth/me` endpoint.
- **2025-10-14 14:10 UTC** – Built Inertia login/register pages, dashboard shell, and shadcn form primitives; seeded demo GM/Player accounts.
- **2025-10-14 14:25 UTC** – Documented auth workflow in README, verified Vite build and Pest suite after generating app key.
- **2025-10-18 15:05 UTC** – Hardened welcome CTA routing with a Ziggy fallback so registration links stay functional even if client route manifests omit auth endpoints.
