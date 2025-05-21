import { test, expect, type BrowserContext } from '@playwright/test';
import config from '../../config';

test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Backend login provider cookie', () => {
  const lastLoginProvider = async (context: BrowserContext): Promise<string | undefined> => {
    const cookies = await context.cookies();
    return cookies.find(cookie => cookie.name === 'be_lastLoginProvider')?.value;
  };

  test('no cookie is set while the primary provider is used', async ({ page, context }) => {
    await page.goto(config.baseUrl);
    await expect(page.getByLabel('Username')).toBeVisible();

    expect(await lastLoginProvider(context)).toBeUndefined();
  });

  test('cookie follows switching between providers', async ({ page, context }) => {
    await page.goto(config.baseUrl);
    await expect(page.getByLabel('Username')).toBeVisible();

    await page.getByRole('link', { name: 'Secondary login' }).click();
    await expect(page.getByLabel('Username')).toBeVisible();
    expect(await lastLoginProvider(context)).toBe('playwright_secondary');

    // Switching back to the primary provider removes the stored one
    await page.getByRole('link', { name: 'Login with username and password' }).click();
    await expect(page.getByLabel('Username')).toBeVisible();
    expect(await lastLoginProvider(context)).toBeUndefined();
  });

  test('last used provider is preselected on the next visit', async ({ page }) => {
    await page.goto(config.baseUrl);
    await page.getByRole('link', { name: 'Secondary login' }).click();
    await expect(page.getByLabel('Username')).toBeVisible();

    await page.goto(config.baseUrl);
    await expect(page.getByLabel('Username')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Secondary login' })).toHaveCount(0);
  });

  test('primary provider is shown again after switching back', async ({ page }) => {
    await page.goto(config.baseUrl);
    await page.getByRole('link', { name: 'Secondary login' }).click();
    await expect(page.getByLabel('Username')).toBeVisible();
    await page.getByRole('link', { name: 'Login with username and password' }).click();
    await expect(page.getByLabel('Username')).toBeVisible();

    await page.goto(config.baseUrl);
    await expect(page.getByRole('link', { name: 'Secondary login' })).toBeVisible();
  });
});
