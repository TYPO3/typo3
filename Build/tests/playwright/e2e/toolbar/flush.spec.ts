import { test, expect } from '../../fixtures/setup-fixtures';

const moduleSelector = '#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem';

test('Flush caches buttons appear in topbar dropdown', async ({ page }) => {
  await page.goto('module/web/layout');
  const module = page.locator(moduleSelector);
  await expect(module).toBeVisible();
  await module.locator('.dropdown-toggle').click();
  await expect(module.getByRole('button', { name: 'Flush frontend caches' })).toBeVisible();
  await expect(module.getByRole('button', { name: 'Flush all caches' })).toBeVisible();
});
