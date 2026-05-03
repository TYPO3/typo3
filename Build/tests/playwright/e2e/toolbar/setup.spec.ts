import { test, expect } from '../../fixtures/setup-fixtures';

const moduleSelector = '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem';

test('User Settings is reachable from user toolbar dropdown', async ({ page, backend }) => {
  await page.goto('module/web/layout');
  const module = page.locator(moduleSelector);
  await expect(module).toBeVisible();
  await module.locator('.dropdown-toggle').click();
  await module.getByText('User Settings').click();
  await expect(backend.contentFrame.locator('h1')).toContainText('User Settings');
});
