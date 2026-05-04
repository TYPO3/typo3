import { test, expect } from '@playwright/test';
import config from '../../config';

// The shared login.setup.ts authenticates as admin and persists cookies
// to .auth/login.json which the e2e project automatically loads. Opt out
// of that storage state so each test below starts on the login form.
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Backend login', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(config.baseUrl);
    await expect(page.getByLabel('Username')).toBeVisible();
  });

  test('bad credentials are rejected and an error is rendered', async ({ page }) => {
    const username = page.getByLabel('Username');
    const password = page.getByLabel('Password');

    await expect(username).toHaveAttribute('required', 'required');
    await expect(password).toHaveAttribute('required', 'required');

    await username.fill('testify');
    await password.fill('123456');
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page.locator('#t3-login-error')).toBeVisible();
    await expect(page.locator('#t3-login-error')).toContainText('Your login attempt did not succeed');
  });

  test('editor user sees no System modules and a restricted toolbar', async ({ page }) => {
    await page.getByLabel('Username').fill('editor');
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: 'Login' }).click();

    // Editor has no modules in this fixture, so the sidebar toggle is the
    // earliest stable signal the backend has loaded.
    await expect(page.locator('typo3-backend-sidebar-toggle')).toBeVisible();

    // The module menu may not render at all for a user without modules,
    // so chain a text locator and assert it resolves to zero matches
    // rather than asserting "not containing" on a possibly-missing element.
    await expect(page.locator('#modulemenu').getByText('System', { exact: true })).toHaveCount(0);
    await expect(page.locator('#typo3-cms-backend-backend-toolbaritems-bookmarktoolbaritem')).toBeVisible();
    await expect(page.locator('#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem')).toHaveCount(0);
  });

  test('admin can log in and out again', async ({ page }) => {
    await page.getByLabel('Username').fill(config.login.admin.username);
    await page.getByLabel('Password').fill(config.login.admin.password);
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page.locator('typo3-backend-sidebar-toggle')).toBeVisible();
    await expect(page.locator('#modulemenu')).toContainText('System');

    await page.locator('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem > button').click();
    await page.getByRole('link', { name: 'Logout' }).click();

    await expect(page.getByLabel('Username')).toBeVisible();
  });
});
