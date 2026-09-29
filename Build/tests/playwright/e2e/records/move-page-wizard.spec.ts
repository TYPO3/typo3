import { test, expect } from '../../fixtures/setup-fixtures';

test('The notification confirming a move with the wizard stays when the wizard closes', async ({ backend, page }) => {
  const notifications = page.locator('#alert-container typo3-notification-message');

  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo');
  const row = backend.contentFrame.locator('tr[data-table="pages"]', { hasText: 'elements t3editor' }).first();
  await row.getByRole('button', { name: 'More options...' }).click();
  await row.getByRole('button', { name: 'Move page' }).click();
  await page.frameLocator('typo3-backend-modal iframe').locator('[data-action="paste"]').first().click();

  // The notification is shown while the wizard closes
  await expect(page.locator('typo3-backend-modal')).toHaveCount(0);
  await expect(notifications.filter({ hasText: 'Page successfully moved' })).toBeVisible();
  await expect(notifications.filter({ hasText: 'Page successfully moved' }).getByRole('link', { name: 'Open "elements t3editor"' })).toBeVisible();
});
