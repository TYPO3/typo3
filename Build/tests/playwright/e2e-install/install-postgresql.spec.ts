/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { test, expect } from '@playwright/test';

const dbHost = process.env.typo3InstallPostgresqlDatabaseHost;
const dbUsername = process.env.typo3InstallPostgresqlDatabaseUsername;
const dbPassword = process.env.typo3InstallPostgresqlDatabasePassword;
const dbName = process.env.typo3InstallPostgresqlDatabaseName;
if (!dbHost || !dbUsername || !dbPassword || !dbName) {
  throw new Error('typo3InstallPostgresqlDatabase{Host,Username,Password,Name} env vars must all be set for the PostgreSQL installer spec.');
}

test.describe('TYPO3 installer - PostgreSQL', () => {
  test('install TYPO3 on PostgreSQL', async ({ page }) => {
    // Calling frontend redirects to installer
    await page.goto('/');

    // EnvironmentAndFolders step
    await expect(page.getByText('Installing TYPO3')).toBeVisible();
    await expect(page.getByText('No problems detected, continue with installation')).toBeVisible();
    await page.getByText('No problems detected, continue with installation').click();

    // DatabaseConnection step
    await expect(page.getByText('Connect to database')).toBeVisible();
    await page.locator('#t3js-connect-database-driver').selectOption({ label: 'Manually configured PostgreSQL connection' });
    await page.locator('#t3-install-step-postgresManualConfiguration-username').fill(dbUsername);
    await page.locator('#t3-install-step-postgresManualConfiguration-password').fill(dbPassword);
    await page.locator('#t3-install-step-postgresManualConfiguration-database').fill(dbName);
    await page.locator('#t3-install-step-postgresManualConfiguration-host').fill(dbHost);
    await page.getByRole('button', { name: 'Continue' }).click();

    // DatabaseData step
    await expect(page.getByText('Create administrative user and specify site name')).toBeVisible();
    await page.locator('#username').fill('admin');
    await page.locator('#password').fill('Policy-Compliant_Password.1');
    await page.getByRole('button', { name: 'Continue' }).click();

    // DefaultConfiguration step - load distributions
    await expect(page.getByText('Installation complete')).toBeVisible({ timeout: 60000 });
    await page.locator('#create-site').click();
    await page.getByText('Finish installation').click();

    // Verify backend login successful. Wait for login.js to wire its submit
    // handler that copies #t3-password into hidden input[name=userident].
    // Without it the form posts an empty password and the login is rejected.
    await expect(page.locator('#t3-username')).toBeVisible({ timeout: 30000 });
    await expect(page.locator('body[data-typo3-login-ready="true"]')).toBeAttached();
    await page.locator('#t3-username').fill('admin');
    await page.locator('#t3-password').fill('Policy-Compliant_Password.1');
    await page.locator('#t3-login-submit-section > button').click();
    await expect(page.locator('.modulemenu')).toBeVisible({ timeout: 30000 });
    await expect(page.locator('.scaffold-content iframe')).toBeVisible();
    const cookies = await page.context().cookies();
    expect(cookies.find(c => c.name === 'be_typo_user')).toBeDefined();

    // Verify default frontend is rendered
    await page.goto('/');
    await expect(page.getByText('Welcome to your default website')).toBeVisible();
  });
});
