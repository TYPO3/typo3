import { test, expect } from '@playwright/test';
import config from '../../config';

test.describe('Backend login route redirect', () => {

  test('route redirect works', async ({ page }) => {
    await page.goto(config.baseUrl + 'module/file/list');

    // Expect the Media URL to be active
    await expect(page.locator('.modulemenu-action-active')).toHaveText('Media');
  });

  test('route redirect works cross domain', async ({ page }) => {
    await page.route('http://cross-site.test/', async route => {
      await route.fulfill({
        status: 200,
        body: `<a href="${config.baseUrl}module/file/list">Test link</a>`,
        headers: {
          'content-type': 'text/html',
        },
      });
    });

    await page.goto('http://cross-site.test/');
    await page.getByRole('link', { name: 'Test link' }).click();

    // Expect the Media URL to be active
    await expect(page.locator('.modulemenu-action-active')).toHaveText('Media');
  });

});
