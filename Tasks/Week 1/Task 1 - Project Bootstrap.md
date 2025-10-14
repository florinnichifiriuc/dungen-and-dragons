# Task 1 – Project Bootstrap

**Status:** In Progress  
**Owner:** Engineering  
**Dependencies:** None  
**Related Backlog Items:** Establish Laravel + React monorepo with Sail, Sanctum, and SPA scaffolding

## Objective
Set up the monorepo structure combining Laravel 12 API backend and React 18 + TypeScript + Vite frontend with Tailwind and shadcn/ui foundations. Ensure developer tooling (Sail, Docker, linting, formatting) and baseline CI scripts are in place. Document turn-based world assumptions and initialize configuration for UTC handling.

## Deliverables
- Laravel backend application configured with Sanctum, Sail, Pest, and initial modules (users, groups namespaces).
- React frontend scaffolded with Vite, Tailwind CSS, shadcn/ui, React Query, and routing setup.
- Shared `.editorconfig`, linting configs, and package scripts.
- Updated documentation: README quickstart, TASK_PLAN status, PROGRESS_LOG entry.

## Implementation Checklist
- [x] Create `Tasks/Week 1` structure and task files.
- [x] Initialize Laravel application within `/backend` with Sail, Sanctum, Pest.
- [ ] Configure database connection (MySQL via Sail) and timezone defaults (UTC).
- [ ] Install and configure Laravel Breeze API scaffolding? (defer). For now ensure Sanctum ready.
- [x] Initialize React frontend `/frontend` with Vite + TypeScript template.
- [x] Add Tailwind CSS + shadcn/ui configuration (dark mode default to class).
- [x] Install React Router, React Query, Zustand, Axios, ESLint/Prettier.
- [x] Configure workspace-level tooling (root README quickstart update, scripts, husky optional).
- [ ] Verify dev servers start (Sail, Vite) – document commands.

## Implementation Notes
- Use Composer 2 and PHP 8.3 base image via Sail.
- Sanctum SPA guard will secure API endpoints for React app served from same domain (via Vite proxy in dev).
- Keep backend/frontend separated but plan for shared DTOs later.

## Log
- **2025-10-14 04:20 UTC** – Created `Tasks/Week 1` structure and drafted bootstrap checklist.
- **2025-10-14 04:55 UTC** – Generated Laravel 12 backend with Sail, Sanctum, Pest 4; updated timezone config to read `APP_TIMEZONE`.
- **2025-10-14 05:40 UTC** – Scaffolded React 18 + Vite app, installed Tailwind, shadcn-ready theme tokens, Query Client provider, routing shell.
- **2025-10-14 06:05 UTC** – Added shared tooling (.editorconfig, .gitignore) and updated README/TASK_PLAN; pending container start verification.
- **2025-10-14 06:25 UTC** – Validated frontend TypeScript build via `pnpm run build`; Sail startup validation still outstanding.
