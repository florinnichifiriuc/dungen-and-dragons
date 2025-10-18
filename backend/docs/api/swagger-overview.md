# API Swagger Overview

This outline captures the structure needed to generate an OpenAPI 3.1 document that reflects the transparency platform’s HTTP surface area.

## Specification Header
- **Title:** Dungen & Dragons Transparency API
- **Version:** 1.0.0 (initial demo release)
- **Servers:**
  - `https://demo.dungen-and-dragons.test` – demo environment
  - `http://localhost` – local development (Laravel + Vite stack)

## Security
- **Scheme:** `sanctumToken`
  - Type: `apiKey`
  - In: `header`
  - Name: `Authorization`
  - Description: Bearer token issued via Laravel Sanctum (`auth:sanctum` middleware). 【F:backend/routes/api.php†L7-L14】

## Tags & Paths
- **Auth** – `/login`, `/register`, `/logout`, `/api/auth/me` for authenticating users and fetching identity. 【F:backend/routes/web.php†L57-L63】【F:backend/routes/api.php†L7-L14】
- **Dashboard & Search** – Authenticated `/dashboard` and `/search` endpoints rendering high-level summaries and search results via Inertia. 【F:backend/routes/web.php†L65-L66】【F:backend/routes/web.php†L195-L195】
- **Groups** – CRUD plus join codes, memberships, worlds, regions, maps, tile templates, and turn automation endpoints scoped under `/groups/{group}`. 【F:backend/routes/web.php†L73-L162】
- **Campaigns** – `/campaigns` resource including insights, invitations, tasks, quests, entities, and session subresources (notes, recaps, rewards, dice, initiative, exports). 【F:backend/routes/web.php†L163-L199】
- **Condition Transparency** – Summary, acknowledgements, share links, consent, exports, webhooks, mentor briefings, maintenance dashboards, and public share endpoints (`/share/condition-timers/{token}`). 【F:backend/routes/web.php†L77-L226】
- **Bug Reports** – Facilitator/player intake plus `/admin/bug-reports` triage endpoints gated by authorization policies. 【F:backend/routes/web.php†L201-L211】
- **NPC Dialogue** – AI helper endpoint `/api/campaigns/{campaign}/sessions/{session}/npc-dialogue` for generating in-session prompts. 【F:backend/routes/api.php†L7-L14】

## Component Schemas (Draft)
- **Group** – Fields: `id`, `name`, `slug`, `join_code`, `telemetry_opt_out`, `mentor_briefings_enabled`, timestamps; relationships to memberships, worlds, regions, campaigns, tiles, maps, shares, exports, and consent logs. 【F:backend/app/Models/Group.php†L5-L92】
- **BugReport** – UUID identifier, `reference`, submitter metadata, `status`, `priority`, `environment`, AI context, tags, assignment, and updates collection. 【F:backend/app/Models/BugReport.php†L5-L76】
- **ConditionTimerSummaryShare** – `token`, `expires_at`, `visibility_mode`, consent snapshot, access counters, soft-delete timestamps, and relationships to group/user/access log. 【F:backend/app/Models/ConditionTimerSummaryShare.php†L5-L87】
- **SessionNpcDialogueRequest** – Body parameters describing campaign/session context and prompt seed (derive from `SessionNpcDialogueController` when exporting the OpenAPI). 【F:backend/routes/api.php†L7-L14】

## Example Workflow Scenarios
1. **Facilitator Transparency Management**
   - Authenticate, fetch `/groups/{group}/condition-timers/player-summary`, create share link, grant consents, trigger export. 【F:backend/routes/web.php†L77-L116】
2. **Player Share Acknowledgement**
   - Access public share, submit acknowledgement via `/groups/{group}/condition-timers/acknowledgements`, optionally file bug report from the share link. 【F:backend/routes/web.php†L81-L83】【F:backend/routes/web.php†L215-L226】
3. **Bug Triage Loop**
   - Admin lists `/admin/bug-reports`, updates status/priority, posts comments, exports CSV for reporting. 【F:backend/routes/web.php†L205-L211】

## Next Steps
- Translate each controller method into `paths` entries with request/response schemas referencing the components above.
- Include error responses (401, 403, 422) aligned with Laravel validation patterns.
- Publish the generated Swagger JSON and host Swagger UI in the developer portal ahead of the demo.
