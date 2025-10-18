import { expect, test } from '@playwright/test';

import { E2E_ACCOUNTS, E2E_SHARE } from '../support/constants';

test.describe.configure({ mode: 'serial' });

let facilitatorReference = '';
const facilitatorSummary = `Facilitator bug ${Date.now()}`;
const shareSummary = `Share bug ${Date.now()}`;

test('facilitator can submit a bug report from the dashboard', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/login');

    await page.getByLabel('Email').fill(E2E_ACCOUNTS.facilitator.email);
    await page.getByLabel('Password').fill(E2E_ACCOUNTS.facilitator.password);

    await Promise.all([
        page.waitForURL('**/dashboard'),
        page.getByRole('button', { name: 'Sign in' }).click(),
    ]);

    await page.goto('/bug-reports/create');

    await page.getByLabel('Summary').fill(facilitatorSummary);
    await page.getByLabel('Priority').selectOption('high');
    await page.getByLabel('Description').fill('Condition timer widget froze during turn processing.');
    await page.getByLabel('Steps to reproduce').fill('1. Open the dashboard\n2. Refresh the timer\n3. Observe frozen state');
    await page.getByPlaceholder('What should have happened?').fill('Timer should clear after refresh.');
    await page.getByPlaceholder('What actually happened?').fill('Timer remained active without countdown.');
    await page.getByLabel('Tags').fill('playwright,launch');
    await page.getByLabel('Console or error logs').fill('Error: Countdown worker timed out');

    await Promise.all([
        page.waitForResponse((response) => response.url().includes('/bug-reports') && response.status() === 303),
        page.getByRole('button', { name: 'Submit bug report' }).click(),
    ]);

    const banner = page.getByRole('status');
    await expect(banner).toContainText('Thanks! We logged bug report');

    const text = await banner.textContent();
    const match = text?.match(/(BR-[A-Z0-9]+)/i);
    facilitatorReference = match?.[1] ?? '';

    await expect(facilitatorReference.length).toBeGreaterThan(0);
    await expect(page.getByRole('heading', { level: 1 })).toContainText(facilitatorSummary);
});

test('guest share link accepts bug reports with contact details', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto(`/share/condition-timers/${E2E_SHARE.token}/report`);

    await page.getByLabel('Summary').fill(shareSummary);
    await page.getByLabel('Description').fill('Share link data looked stale after countdown.');
    await page.getByLabel('Steps to reproduce').fill('1. Follow the share link\n2. Wait for timer refresh');
    await page.getByPlaceholder('What should have happened?').fill('Share should reflect the new timer state.');
    await page.getByPlaceholder('What actually happened?').fill('Outlook still showed the old urgency.');
    await page.getByLabel('Tags').fill('guest,bug');
    await page.getByLabel('Your name').fill(E2E_ACCOUNTS.player.name);
    await page.getByLabel('Contact email').fill(E2E_ACCOUNTS.player.email);

    await Promise.all([
        page.waitForURL(`**/share/condition-timers/${E2E_SHARE.token}`),
        page.getByRole('button', { name: 'Submit bug report' }).click(),
    ]);

    await expect(page.getByRole('heading', { level: 1 })).toContainText('Shared Condition Outlook');
    await expect(page.getByRole('link', { name: 'Spot an issue? Report it' })).toBeVisible();
});

test('support admin triages the latest reports', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto('/login');
    await page.getByLabel('Email').fill(E2E_ACCOUNTS.support.email);
    await page.getByLabel('Password').fill(E2E_ACCOUNTS.support.password);

    await Promise.all([
        page.waitForURL('**/dashboard'),
        page.getByRole('button', { name: 'Sign in' }).click(),
    ]);

    await page.goto('/admin/bug-reports');

    await page.getByLabel('Search').fill(shareSummary);
    await Promise.all([
        page.waitForResponse((response) => response.url().includes('/admin/bug-reports') && response.status() === 200),
        page.getByRole('button', { name: 'Filter' }).click(),
    ]);

    const row = page.getByRole('row', { name: new RegExp(shareSummary, 'i') });
    await expect(row).toBeVisible();
    await row.getByRole('link', { name: 'Review' }).click();

    await expect(page.getByRole('heading', { level: 1 })).toContainText(shareSummary);

    await page.getByLabel('Status').selectOption('in_progress');
    await page.getByLabel('Priority').selectOption('high');
    await page.getByLabel('Assign to').selectOption({ label: E2E_ACCOUNTS.support.name });
    await page.getByLabel('Tags').fill('guest,launch');
    await page.getByLabel('Status note').fill('Investigating share freshness.');

    await Promise.all([
        page.waitForResponse((response) => response.url().includes('/admin/bug-reports') && response.status() === 303),
        page.getByRole('button', { name: 'Save changes' }).click(),
    ]);

    await expect(page.getByRole('status')).toContainText('Bug report updated.');
    await expect(page.getByText('in progress', { exact: false })).toBeVisible();

    // ensure facilitator bug is accessible by reference search as well
    await page.goto('/admin/bug-reports');
    await page.getByLabel('Search').fill(facilitatorReference);
    await Promise.all([
        page.waitForResponse((response) => response.url().includes('/admin/bug-reports') && response.status() === 200),
        page.getByRole('button', { name: 'Filter' }).click(),
    ]);

    await expect(page.getByRole('cell', { name: facilitatorReference })).toBeVisible();
});
