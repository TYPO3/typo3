import { test, expect } from '../../fixtures/setup-fixtures';

test('New page button opens the page wizard modal', async ({ page, backend }) => {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo');

  await backend.contentFrame.locator('.module-docheader .btn[title="Create new record"]').click();
  await expect(backend.contentFrame.locator('h1')).toContainText('New record');

  await backend.contentFrame.locator('typo3-backend-new-page-wizard-button[data-page-create="inside"]').click();

  await expect(page.locator('typo3-backend-modal > dialog')).toBeVisible();
  const modalContent = await backend.modal.getModalContent();
  await expect(modalContent.locator('typo3-backend-page-wizard')).toHaveCount(1);
});
