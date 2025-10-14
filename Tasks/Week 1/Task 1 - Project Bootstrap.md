# Task 1 – Project Bootstrap

**Status:** In Progress  
**Owner:** Engineering  
**Dependencies:** None  
**Related Backlog Items:** Establish Laravel + Inertia React workspace with Sail, Sanctum, and asset pipeline

## Objective
Set up the Laravel 12 application to serve both the API and the React 18 UI via Inertia.js and Vite. Ensure developer tooling (Sail, Docker, Tailwind, shadcn/ui tokens) and baseline CI scripts are in place. Document turn-based world assumptions and initialize configuration for UTC handling.

## Deliverables
- Laravel backend application configured with Sanctum, Sail, Pest, and initial modules (users, groups namespaces).
- Inertia-powered React frontend scaffolded within Laravel resources with Vite, Tailwind CSS, and shadcn/ui-ready tokens.
- Shared `.editorconfig`, linting configs, and package scripts.
- Updated documentation: README quickstart, TASK_PLAN status, PROGRESS_LOG entry.

## Implementation Checklist
- [x] Create `Tasks/Week 1` structure and task files.
- [x] Initialize Laravel application within `/backend` with Sail, Sanctum, Pest.
- [ ] Configure database connection (MySQL via Sail) and timezone defaults (UTC).
- [ ] Install and configure Laravel Breeze API scaffolding? (defer). For now ensure Sanctum ready.
- [x] Integrate React + Inertia scaffolding within Laravel resources using Vite + TypeScript.
- [x] Add Tailwind CSS + shadcn/ui configuration (dark mode default to class).
- [x] Install React, Zustand, Axios, shadcn/ui utilities, and supporting dependencies.
- [x] Configure workspace-level tooling (root README quickstart update, scripts, husky optional).
- [ ] Verify dev servers start (Sail, Vite via `npm run dev`) – document commands.

## Implementation Notes
- Use Composer 2 and PHP 8.3 base image via Sail.
- Sanctum SPA guard will secure API endpoints and Inertia responses served from the same Laravel app.
- Keep API routes and Inertia pages co-located while still exposing `/routes/api.php` for future decoupled clients.

## Log
- **2025-10-14 04:20 UTC** – Created `Tasks/Week 1` structure and drafted bootstrap checklist.
- **2025-10-14 04:55 UTC** – Generated Laravel 12 backend with Sail, Sanctum, Pest 4; updated timezone config to read `APP_TIMEZONE`.
- **2025-10-14 05:40 UTC** – Scaffolded React 18 + Vite shell (prior iteration). Will be superseded by Inertia integration.
- **2025-10-14 06:05 UTC** – Added shared tooling (.editorconfig, .gitignore) and updated README/TASK_PLAN; pending container start verification.
- **2025-10-14 06:25 UTC** – Validated standalone frontend build (deprecated).
- **2025-10-14 11:45 UTC** – Replaced standalone SPA with Inertia-driven React inside Laravel; installed dependencies and updated middleware/routes.
