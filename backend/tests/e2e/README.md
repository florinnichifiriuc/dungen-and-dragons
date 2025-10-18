# End-to-End Bug Reporting Scenarios

These Playwright specs exercise the facilitator intake form, share link guest form, and the admin triage dashboard so we can validate the full launch flow (Task 85).

## Preparing the Environment

1. Install PHP and Node dependencies:

   ```bash
   composer install
   npm install
   ```

2. Create the testing database, run migrations, and seed the deterministic data set:

   ```bash
   php artisan migrate:fresh --seed --class=Database\\Seeders\\E2EBugReportingSeeder
   ```

3. Start the Laravel application with AI mocks enabled so no live Ollama calls occur:

   ```bash
   AI_MOCKS_ENABLED=true APP_ENV=testing php artisan serve --host=0.0.0.0 --port=8000
   ```

   The Playwright config defaults to `http://localhost:8000` but you can override this via `E2E_BASE_URL`.

## Running the Suite

In a second terminal run:

```bash
AI_MOCKS_ENABLED=true npm run test:e2e
```

The suite uses Chromium and WebKit profiles. Traces are retained on failure under `storage/app/playwright-report`.

## Seeded Accounts & Data

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Facilitator | `facilitator@example.com` | `password` | Owns the E2E Bug Hunters group and files facilitator reports. |
| Player | `player@example.com` | `password` | Used for guest share submissions (not required for sign-in). |
| Support Admin | `support@example.com` | `password` | Has admin access for triage dashboards. |

- Share token for guest flows: `bug-e2e-share-token-0f1d2c3b4a5e6f7890abcd1234567890`
- Seeded bug reference: `BR-SEED01`

## Extending Scenarios

- Update `tests/e2e/support/constants.ts` when adding new seeded fixtures.
- Keep specs deterministic—prefer seeder updates over dynamic UI setup.
- If you add new AI interactions, ensure the mock harness (`docs/testing/ai-mocking.md`) includes fixtures so the suite continues to run offline.
