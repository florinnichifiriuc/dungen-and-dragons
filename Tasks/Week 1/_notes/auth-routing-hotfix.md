# Auth Routing Hotfix – Ziggy Manifest

- Date (UTC): 2025-10-18 16:20
- Scope: Task 2 – Authentication Foundations

Changes
- Pass live `window.Ziggy` to `ziggy-js` at call time in `backend/resources/js/app.tsx` to avoid stale/empty route manifests during HMR.
- Add `safeRoute()` helper (`backend/resources/js/lib/route.ts`) and use it on auth pages and guest layout to fall back to static endpoints when Ziggy is unavailable.

Why
- Users hit “Ziggy error: route 'login' is not in the route list” on Register/Login due to timing issues where the client route manifest wasn’t hydrated yet.

Impact
- Account creation works reliably; guest pages no longer crash if the manifest is missing.

Follow‑ups
- Consider extending `safeRoute()` usage across all top‑level navigation links.
