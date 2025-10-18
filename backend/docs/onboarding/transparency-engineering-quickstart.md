# Transparency Engineering Quickstart

This quickstart accelerates onboarding for engineers joining the transparency initiative.

## 1. Environment Preparation
1. Install PHP 8.3, Composer, Node.js 20, and pnpm or npm 10.
2. Run `composer install` from `backend/` to populate the Laravel vendor directory.
3. Run `npm install` within `backend/` to install frontend dependencies.
4. Copy `.env.example` to `.env` and configure the following keys:
   - `APP_URL`, `FRONTEND_URL`
   - `AI_MENTOR_MODEL_ENDPOINT`
   - `DB_CONNECTION` credentials (MySQL 8)
5. Generate an app key: `php artisan key:generate`.
6. Run migrations and seed transparency fixtures: `php artisan migrate --seed`.

> **Tip:** Task 57 introduces new migrations (`add_moderation_columns`, `add_preset_key`). Re-run migrations after pulling latest changes.

## 2. Essential Commands
- `php artisan test --testsuite=Feature --filter=ConditionTimer` – Validate transparency feature endpoints.
- `php artisan transparency:demo` – Populate facilitator/player demo data with share insights.
- `npm run dev` – Start Vite dev server for Inertia UI work.
- `npm run lint` – Ensure TypeScript and accessibility rules pass.
- `npm run build` – Verify production build succeeds before shipping UI changes.

## 3. Core Services Overview
- `backend/app/Services/ConditionTimerSummaryShareService.php` – Share insights, freshness metrics, and export hooks.
- `backend/app/Services/ConditionMentorPromptManifest.php` – Loads localized mentor prompts; update alongside `resources/lang/*/transparency-ai.json`.
- `backend/app/Services/ConditionMentorModerationService.php` – Handles AI moderation workflows and queue decisions.
- `backend/resources/js/components/transparency/` – Insight UI components reused across facilitator dashboards.

## 4. Documentation Map
- Transparency dossier: `backend/docs/transparency-completion-dossier.md`
- Knowledge transfer sessions: `backend/docs/knowledge-transfer/`
- QA suite & load testing: `backend/docs/operations/transparency-qa-suite.md`
- Consent analytics runbooks: `backend/docs/operations/consent-audit-kpi-dashboard.md`
- Maintenance plan: `backend/docs/operations/transparency-maintenance-transition.md`

## 5. First Week Checklist
- [ ] Watch Session 1 recording (`Meetings/2025-11-18-knowledge-transfer-architecture.md`).
- [ ] Watch Session 2 recording (`Meetings/2025-11-19-knowledge-transfer-governance.md`).
- [ ] Run feature tests and confirm they fail gracefully without vendor directory (documented TODO).
- [ ] Pair with a mentor on implementing a share insight UI tweak.
- [ ] Submit feedback via `backend/docs/knowledge-transfer/knowledge-transfer-feedback-template.md`.

## 6. Support Contacts
- Engineering Lead – Architecture questions, service ownership.
- Data & Telemetry Lead – KPI dashboards, alerting.
- QA Lead – Test harness, regression coverage.
- Product Owner – Consent governance, facilitator feedback.

## 7. Next Steps
- Join `#transparency` Slack channel for updates.
- Subscribe to Looker dashboard alerts (request access from Data & Telemetry Lead).
- Add upcoming maintenance cadence meetings from the transition plan to your calendar.
