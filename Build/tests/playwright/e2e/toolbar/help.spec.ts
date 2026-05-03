import { test, expect } from '../../fixtures/setup-fixtures';

const moduleSelector = '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem';

test('About TYPO3 CMS opens system information module', async ({ page, backend }) => {
  await page.goto('module/web/layout');
  const module = page.locator(moduleSelector);
  await module.locator('.dropdown-toggle').click();
  await expect(module).toContainText('About TYPO3 CMS');
  await module.getByText('About TYPO3 CMS').click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Web Content Management System');
});

test('Online Documentation link is rendered in user toolbar dropdown', async ({ page }) => {
  await page.goto('module/web/layout');
  const module = page.locator(moduleSelector);
  await module.locator('.dropdown-toggle').click();
  await expect(module).toContainText('TYPO3 Online Documentation');
  await expect(module.locator('a[href="https://docs.typo3.org/"]')).toBeVisible();
});
