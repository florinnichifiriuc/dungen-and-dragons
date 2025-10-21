# Demo Enhancements & Known Issues

This brief captures the current risks and improvement opportunities the team must monitor before the stakeholder demo.

## Known Issues
### High Priority
- **Local coverage gate opt-in required:** GitHub Actions remains disabled, so Task 84 now relies on the `.githooks/pre-push` gate and `backend/bin/coverage-gate.sh`. Engineers must run `git config core.hooksPath .githooks` (once per clone) or invoke the script manually to keep enforcement active. 【F:.githooks/pre-push†L1-L23】【F:backend/bin/coverage-gate.sh†L1-L74】
- **Playwright automation via local runner:** Task 85 ships with `npm run test:e2e:report`, which captures Chromium/WebKit runs and logs history under `storage/qa/e2e`. Nightly cadence still depends on engineers or a cron-able host executing the script. 【F:backend/package.json†L6-L12】【F:backend/scripts/run-playwright.mjs†L1-L156】

### Medium Priority
- **Demo seed refresh required:** The `E2EBugReportingSeeder` must be run before rehearsals so facilitator/player/support demo accounts and seeded bug reports exist; without it, several Playwright specs and demo talking points lack data. 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】
- **Progress log dependency:** The `demo:milestones` narration command reads directly from `PROGRESS_LOG.md`. Missing or stale entries will surface as runtime errors or outdated talking points during the walkthrough. 【F:backend/routes/console.php†L9-L93】

- **Scheduled Playwright dry runs:** Capture manual rehearsal results in the backlog checklist and explore lightweight cron alternatives (e.g., on the QA host) until CI returns. The new JSONL logs make dashboards trivial to publish if a scheduler is added. 【F:backend/scripts/run-playwright.mjs†L1-L156】
- **Demo data snapshot script:** Consider wrapping `E2EBugReportingSeeder` plus condition transparency fixtures in a single artisan task so facilitators can repopulate demo data quickly. 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】

## Demo Monitoring Checklist
1. Run `php artisan migrate --seed` (or `php artisan db:seed --class=E2EBugReportingSeeder`) before each rehearsal to restore accounts and bug references. 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】
2. Execute `npm run test:e2e:report` with the seeded data and record results in the backlog rehearsal checklist (both the HTML report and `storage/qa/e2e/latest.json`). 【F:backend/package.json†L6-L12】
3. Use `php artisan demo:milestones --all --delay=0.8` to verify narration output stays in sync with the updated progress log. 【F:backend/routes/console.php†L9-L93】
4. Ensure the coverage gate hook is configured (`git config core.hooksPath .githooks`) or run `backend/bin/coverage-gate.sh` so Task 84 maintains traceability with JSONL history. 【F:.githooks/pre-push†L1-L23】【F:backend/bin/coverage-gate.sh†L1-L74】
