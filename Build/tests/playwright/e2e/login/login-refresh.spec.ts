import { test, expect } from '@playwright/test';
import config from '../../config';

// The dialog logs the session out first, so use an own session instead of the shared storage state
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Login refresh dialog', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(config.baseUrl);
    await page.getByLabel('Username').fill(config.login.admin.username);
    await page.getByLabel('Password').fill(config.login.admin.password);
    await page.getByRole('button', { name: 'Login' }).click();
    await expect(page.locator('typo3-backend-sidebar-toggle')).toBeVisible();

    await page.waitForFunction(() => (window as any).TYPO3?.LoginRefresh !== undefined);
    await page.evaluate(() => (window as any).TYPO3.LoginRefresh.showLoginForm());
    await expect(page.locator('typo3-backend-modal input[name="p_field"]')).toBeFocused();
  });

  test('pressing Enter in the password field submits the login', async ({ page }) => {
    const dialog = page.locator('typo3-backend-modal > dialog');
    const loginRequest = page.waitForRequest(request => request.method() === 'POST' && new URL(request.url()).pathname.endsWith('/ajax/login'));

    await page.keyboard.type(config.login.admin.password);
    await page.keyboard.press('Enter');

    await loginRequest;
    await expect(dialog).not.toBeVisible();
  });

  test('a wrong password keeps the dialog open', async ({ page }) => {
    const dialog = page.locator('typo3-backend-modal > dialog');

    await page.keyboard.type('wrong-password');
    await page.keyboard.press('Enter');

    await expect(page.locator('typo3-notification-message')).toContainText('Login failed');
    await expect(dialog).toBeVisible();
    await expect(page.locator('typo3-backend-modal input[name="p_field"]')).toBeFocused();
  });

  test('a wrong password submitted with the login button keeps the dialog open', async ({ page }) => {
    const dialog = page.locator('typo3-backend-modal > dialog');

    await page.keyboard.type('wrong-password');
    await dialog.getByRole('button', { name: 'Login' }).click();

    await expect(page.locator('typo3-notification-message')).toContainText('Login failed');
    await expect(dialog).toBeVisible();
  });
});
