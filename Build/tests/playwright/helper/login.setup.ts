import { test as setup, expect } from '@playwright/test';
import config from '../config';

setup('authenticate as admin', async ({ page }) => {
  await page.goto(`${config.baseUrl}`);
  await page.getByLabel('Username').fill(config.login.admin.username);
  await page.getByLabel('Password').fill(config.login.admin.password);
  await page.getByRole('button', { name: 'Login' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('.t3js-topbar-button-modulemenu')).toBeVisible();
  await page.context().storageState({ path: './.auth/login.json' });
});
