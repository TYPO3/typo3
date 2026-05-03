import { test, expect } from '../../fixtures/setup-fixtures';

test('TYPO3 logo in topbar links to backend root', async ({ page }) => {
  await page.goto('module/web/layout');
  await expect(page.locator('a.topbar-site[href="./"]')).toBeVisible();
});
