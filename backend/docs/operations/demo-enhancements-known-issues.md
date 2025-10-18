# Demo Enhancements & Known Issues

This brief captures the current risks and improvement opportunities the team must monitor before the stakeholder demo.

## Known Issues
### High Priority
- **CI coverage gate offline:** Task 84 was reopened because the GitHub Actions workflow enforcing `php artisan test --coverage --min=80` was removed. Until a replacement runner is available, coverage regressions depend on manual execution. 【F:Tasks/Week 10/Task 84 - Unit Test Hardening Sprint.md†L3-L15】
- **Playwright automation manual:** Task 85 now relies on engineers running `npm run test:e2e` locally; nightly dashboards are unavailable while CI automation is disabled. 【F:Tasks/Week 10/Task 85 - End-to-End Regression Scenarios.md†L3-L17】【F:backend/package.json†L6-L20】

### Medium Priority
- **Demo seed refresh required:** The `E2EBugReportingSeeder` must be run before rehearsals so facilitator/player/support demo accounts and seeded bug reports exist; without it, several Playwright specs and demo talking points lack data. 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】
- **Progress log dependency:** The `demo:milestones` narration command reads directly from `PROGRESS_LOG.md`. Missing or stale entries will surface as runtime errors or outdated talking points during the walkthrough. 【F:backend/routes/console.php†L9-L93】

## Enhancement Opportunities
- **Automated coverage alternative:** Evaluate self-hosted runners or pre-push Git hooks to restore automated enforcement for Task 84 without violating the no–GitHub Actions directive. 【F:Tasks/Week 10/Task 84 - Unit Test Hardening Sprint.md†L3-L15】
- **Scheduled Playwright dry runs:** Capture manual rehearsal results in the backlog checklist and explore lightweight cron alternatives (e.g., on the QA host) until CI returns. 【F:Tasks/Week 10/Task 85 - End-to-End Regression Scenarios.md†L3-L17】
- **Demo data snapshot script:** Consider wrapping `E2EBugReportingSeeder` plus condition transparency fixtures in a single artisan task so facilitators can repopulate demo data quickly. 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】

## Demo Monitoring Checklist
1. Run `php artisan migrate --seed` (or `php artisan db:seed --class=E2EBugReportingSeeder`) before each rehearsal to restore accounts and bug references. 【F:backend/database/seeders/E2EBugReportingSeeder.php†L7-L82】
2. Execute `npm run test:e2e` with the seeded data and record results in the backlog rehearsal checklist. 【F:backend/package.json†L6-L20】
3. Use `php artisan demo:milestones --all --delay=0.8` to verify narration output stays in sync with the updated progress log. 【F:backend/routes/console.php†L9-L93】
4. Log coverage percentages after each manual `php artisan test --coverage --min=80` run so Task 84 has traceability despite the missing automation. 【F:Tasks/Week 10/Task 84 - Unit Test Hardening Sprint.md†L3-L15】
