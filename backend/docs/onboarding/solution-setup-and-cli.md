# Solution Setup & Command Catalog

Use this guide to bootstrap the transparency solution locally and understand the commands available for development, testing, and demo support.

## Prerequisites
- PHP 8.2+, Composer, and Node.js 20 (matches framework/tooling requirements). 【F:backend/composer.json†L9-L36】
- MySQL 8 (or SQLite for quick starts) and a modern browser for Inertia-powered UI flows.

## Environment Setup
1. `cd backend`
2. Install PHP dependencies: `composer install`
3. Install Node dependencies: `npm install`
4. Copy environment template: `cp .env.example .env`
5. Generate app key: `php artisan key:generate`
6. Configure database/queue credentials inside `.env`
7. Run migrations and seed demo data: `php artisan migrate --seed` (includes the `E2EBugReportingSeeder` accounts and the idempotent Edgewatch Steward admin seeded via `SEED_ADMIN_*` variables). 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】【F:backend/database/seeders/AdminUserSeeder.php†L5-L62】
   - Update `SEED_ADMIN_EMAIL`/`SEED_ADMIN_PASSWORD` (and optional GM/player overrides) in `.env` before seeding to align with your environment. The dashboard and admin console reference these values directly. 【F:backend/.env.example†L51-L64】【F:backend/resources/js/Pages/Dashboard.tsx†L27-L63】
8. Start the Laravel server: `php artisan serve`
9. In a second terminal, start Vite: `npm run dev` (or use the Composer `dev` script to launch server, queue, logs, and Vite together—on Windows it now falls back to a PowerShell log follower automatically when the `pcntl` extension is unavailable). 【F:backend/package.json†L6-L20】【F:backend/composer.json†L37-L57】

> **Tip:** `composer run setup` bootstraps install, env copy, key generation, migration, npm install, and production build in one command. 【F:backend/composer.json†L37-L57】

## Testing & Quality Commands
- `php artisan test --coverage --min=80` – Manual coverage gate for Task 84 while CI automation is offline. 【F:Tasks/Week 10/Task 84 - Unit Test Hardening Sprint.md†L3-L15】
- `npm run lint` – Accessibility and TypeScript linting for transparency UI surfaces. 【F:backend/package.json†L6-L20】
- `npm run test:e2e` – Playwright regression suite covering facilitator, player, and admin flows; run after seeding demo data. 【F:backend/package.json†L6-L20】【F:Tasks/Week 10/Task 85 - End-to-End Regression Scenarios.md†L3-L17】
- `php artisan dusk` – Browser-based regression checks (requires `php artisan serve`).

## Demo & Operations Commands
- `php artisan demo:milestones {milestone?} [--all] [--delay=seconds]` – Narrated walkthrough sourced from `PROGRESS_LOG.md`; useful for stakeholder rehearsals. 【F:backend/routes/console.php†L9-L93】
- `php artisan condition-transparency:share-maintenance {groupId?}` – Summarizes share maintenance attention items for facilitators. 【F:backend/app/Console/Commands/ConditionTimerShareMaintenanceCommand.php†L7-L68】
- `php artisan condition-transparency:ping [--group=id]` – Synthetic uptime checks for condition transparency share links. 【F:backend/app/Console/Commands/ConditionTransparencyPingCommand.php†L7-L63】
- `php artisan queue:listen --tries=1` – Recommended during demos to process notifications and transparency jobs live. 【F:backend/composer.json†L37-L57】
- `php artisan dev:logs` – Streams logs with Laravel Pail when supported, or gracefully tails `storage/logs/laravel.log` on platforms without the `pcntl` extension (e.g., Windows). 【F:backend/app/Console/Commands/DevLogsCommand.php†L7-L56】

## Frontend Structure Quick Reference
- Inertia pages live in `resources/js/Pages/` (e.g., the group index page) with shared layouts under `resources/js/Layouts`. 【F:backend/resources/js/Pages/Groups/Index.tsx†L1-L120】【F:backend/resources/js/Layouts/AppLayout.tsx†L1-L120】
- Tailwind configuration resides in `tailwind.config.js`; Vite loads from `resources/js/app.tsx`. 【F:backend/tailwind.config.js†L1-L20】【F:backend/resources/js/app.tsx†L1-L40】
- AI assistants appear on the campaign overview, lore codex, quest log, and map creation flows to expand short prompts into structured data plus Automatic1111-ready 512×512 art cues. 【F:backend/resources/js/Pages/Campaigns/Show.tsx†L1-L120】【F:backend/resources/js/Pages/CampaignEntities/Index.tsx†L1-L200】【F:backend/resources/js/Pages/CampaignQuests/Index.tsx†L1-L200】【F:backend/resources/js/Pages/Maps/Create.tsx†L1-L200】

## Next Steps
- Link this guide from the Transparency Engineering Quickstart for continuity. 【F:backend/docs/onboarding/transparency-engineering-quickstart.md†L1-L38】
- Update the checklist after adopting a CI alternative so setup instructions stay authoritative.
