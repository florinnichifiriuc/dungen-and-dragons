# Site Structure Overview

This document maps the primary navigation paths, Inertia surfaces, and Laravel routes that shape the transparency demo experience.

## Global Shell
- `AppLayout` provides the authenticated chrome (navigation, theming, alerts) that wraps most application pages. 【F:backend/resources/js/Layouts/AppLayout.tsx†L1-L120】
- The dashboard landing page highlights campaign, group, and scheduler entry points surfaced throughout the demo. 【F:backend/resources/js/Pages/Dashboard.tsx†L1-L48】

## Authentication & Welcome
- Guests land on `Welcome.tsx` and can register or log in via the dedicated authentication pages using the `GuestLayout`. 【F:backend/routes/web.php†L53-L63】【F:backend/resources/js/Pages/Auth/Login.tsx†L1-L72】

## Groups & Worlds Domain
- `Route::resource('groups', ...)` powers the party management area with join/create flows and role-aware dashboards. 【F:backend/routes/web.php†L73-L155】
- The groups index page surfaces party roster counts and entry points into detailed world/region management. 【F:backend/resources/js/Pages/Groups/Index.tsx†L1-L52】
- Nested routes expose worlds, regions, maps, tile templates, and turn processing screens under a selected group. 【F:backend/routes/web.php†L136-L162】

## Campaigns & Sessions
- Campaign CRUD, insights, and invitations hang off `Route::resource('campaigns', ...)` with nested entities, quests, tasks, and sessions. 【F:backend/routes/web.php†L163-L199】
- `Campaigns/Index.tsx` showcases the compendium view used in the demo to navigate between sagas. 【F:backend/resources/js/Pages/Campaigns/Index.tsx†L1-L56】
- Session exports, notes, rewards, and initiative flows live under the campaign session subroutes, aligning with the collaborative workspace storyline. 【F:backend/routes/web.php†L181-L199】

## Condition Transparency Surfaces
- Condition timer summaries, share links, exports, mentor briefings, and maintenance dashboards live under the group condition transparency routes. 【F:backend/routes/web.php†L77-L135】
- The `Groups/ConditionTimerSummary.tsx` page stitches together acknowledgement, share management, exports, and mentor briefings in a single facilitator console. 【F:backend/resources/js/Pages/Groups/ConditionTimerSummary.tsx†L1-L80】
- Public share links (`share/condition-timers/{token}`) provide player-facing transparency views and bug report intake. 【F:backend/routes/web.php†L215-L226】

## Bug Reporting & Admin Triage
- Facilitators and players submit bug reports through the authenticated routes, while support admins access the `/admin/bug-reports` dashboard gated by policies. 【F:backend/routes/web.php†L201-L211】
- The admin bug report index provides filtering, analytics, and triage controls showcased during launch rehearsals. 【F:backend/resources/js/Pages/Admin/BugReports/Index.tsx†L1-L96】

## Settings, Notifications & Search
- Notification center and preference pages give users control over alerting, localization, and accessibility knobs. 【F:backend/routes/web.php†L67-L72】【F:backend/resources/js/Pages/Notifications/Index.tsx†L1-L80】【F:backend/resources/js/Pages/Settings/Preferences.tsx†L1-L64】
- Global search is wired via `Route::get('search', ...)`, surfacing cross-entity results in the Inertia search view. 【F:backend/routes/web.php†L195-L195】【F:backend/resources/js/Pages/Search/Index.tsx†L1-L60】

## Demo Flow Highlights
- For demo narration, pair `php artisan demo:milestones` with the dashboard and condition transparency pages to tell the transparency story end-to-end. 【F:backend/routes/console.php†L9-L93】【F:backend/resources/js/Pages/Dashboard.tsx†L1-L48】
