import { test, expect } from '../../fixtures/setup-fixtures';

test('Username is visible in user toolbar item', async ({ page }) => {
  await page.goto('module/web/layout');
  await expect(page.locator('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem')).toContainText('admin', { ignoreCase: true });
});
