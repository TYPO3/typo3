import { test, expect } from '../../fixtures/setup-fixtures';

test('Records can be exported with format switch between CSV and JSON', async ({ page, backend }) => {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo');

  await backend.contentFrame.locator('typo3-recordlist-record-download-button').first().click();

  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('.t3js-modal-title')).toContainText('Download Page:');

  const modalContent = await backend.modal.getModalContent();
  const csvHeading = modalContent.locator('h2', { hasText: 'CSV options' });
  const jsonHeading = modalContent.locator('h2', { hasText: 'JSON options' });
  await modalContent.locator('input[name="filename"]').fill('test-download');
  await expect(csvHeading).toBeVisible();

  await modalContent.locator('select[name="format"]').selectOption('json');
  await expect(csvHeading).not.toBeVisible();
  await expect(jsonHeading).toBeVisible();
  await modalContent.locator('select[name="json[meta]"]').selectOption('full');

  await backend.modal.click({ name: 'download' });
  await expect(dialog).not.toBeVisible({ timeout: 30000 });
});
