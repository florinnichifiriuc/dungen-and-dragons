# Demo End-to-End Rehearsal Checklist

Use this checklist to run the manual Playwright regression suite and supporting smoke checks while CI automation is paused. Complete the flow each morning before lunch and again ahead of stakeholder demos so the release stays green.

## Pre-Run Setup
- Ensure the application is running locally via `composer run dev` in one terminal. The command boots the Laravel server, queue worker, log tail, and Vite watcher together.
- Confirm `.env` is configured for the seeded demo database credentials described in the [Solution Setup & Command Catalog](../onboarding/solution-setup-and-cli.md).
- Run `php artisan migrate:fresh --seed` to reset fixtures. This seeds the `E2EBugReportingSeeder` accounts and demo transparency data required by the Playwright scenarios.
- Clear cached config just before launching the suite: `php artisan config:clear`.

## Required Playwright Runs
1. `npm run test:e2e:report` – Executes the full Chromium/WebKit matrix, saves HTML artifacts to `backend/playwright-report/`, and records structured results under `storage/qa/e2e`. Attach the HTML report and the generated `storage/qa/e2e/latest.json` summary to the daily release thread.
2. If a single project needs re-running, target it directly (for example `npm run test:e2e -- --project=chromium`) and then re-run `npm run test:e2e:report` so history logs stay accurate.

> **Tip:** To debug a failing step interactively, run `npx playwright test path/to/spec.ts --debug` and re-run the full project matrix afterward.

## Post-Run Validation
- Visit `/shares/condition-transparency` as the facilitator account and confirm condition timers render with live countdowns.
- Review the notification center for escalation pings triggered during the run to ensure queue workers processed jobs.
- Confirm no unexpected entries were logged in `storage/logs/laravel.log` during execution.

## Coverage & Lint Spot Checks
- Execute `backend/bin/coverage-gate.sh` (or push with the pre-configured hook) to regenerate the unit coverage report and enforce the 80% threshold.
- Run `npm run lint` to validate accessibility and TypeScript rules on transparency surfaces touched during the demo.
- Review `php artisan qa:dashboard` for the combined coverage and Playwright history snapshot before sign-off.

## Reporting & Escalation
- Record outcomes in the launch channel: include pass/fail for each Playwright project, unit coverage percentage, and lint status.
- File GitHub issues for any regressions discovered and tag them with the "demo-blocker" label. Notify QA leadership immediately if failures block the run.
- Update [Task 85 – End-to-End Regression Scenarios](../../../Tasks/Week%2010/Task%2085%20-%20End-to-End%20Regression%20Scenarios.md) with findings so the log remains authoritative until CI automation returns.

## Exit Criteria
- Do not start the demo until both browser projects, unit coverage, and lint checks succeed.
- If failures persist for longer than one hour, escalate to engineering leadership and consider rescheduling the demo.
