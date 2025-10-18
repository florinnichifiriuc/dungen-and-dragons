import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:8000';

export default defineConfig({
    testDir: './specs',
    timeout: 60_000,
    expect: {
        timeout: 10_000,
    },
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    use: {
        baseURL,
        trace: 'retain-on-failure',
        extraHTTPHeaders: {
            'x-test-ai-mock': 'enabled',
        },
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
    ],
    reporter: process.env.CI ? [['line'], ['html', { open: 'never', outputFolder: './storage/app/playwright-report' }]] : 'list',
});
