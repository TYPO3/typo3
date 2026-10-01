import { test, expect } from '../../fixtures/setup-fixtures';
import { setUserTsConfig } from '../../helper/user-tsconfig';

test.describe.serial('Record download with preset configured via user TSconfig', () => {
  test('records can be exported with preset', async ({ page, backend }) => {
    await setUserTsConfig(
      page,
      backend,
      2,
      'page.mod.web_list.downloadPresets.pages.10.label = Test-Preset\n'
        + 'page.mod.web_list.downloadPresets.pages.10.columns = uid,title,slug\n'
        + 'page.mod.web_list.downloadPresets.pages.10.identifier = download-preset',
    );
    try {
      await backend.gotoModule('records');
      await backend.pageTree.open('styleguide TCA demo');

      await backend.contentFrame.locator('typo3-recordlist-record-download-button').first().click();

      const dialog = page.locator('typo3-backend-modal > dialog');
      await expect(dialog).toBeVisible();
      await expect(dialog.locator('.t3js-modal-title')).toContainText('Download Page:');

      const modalContent = await backend.modal.getModalContent();
      await expect(modalContent.locator('label', { hasText: 'Preset' })).toHaveCount(1);
      await modalContent.locator('input[name="filename"]').fill('test-download');
      await modalContent.locator('select[name="preset"]').selectOption({ label: 'Test-Preset' });

      await backend.modal.click({ name: 'download' });
      await expect(dialog).not.toBeVisible({ timeout: 30000 });
    } finally {
      await setUserTsConfig(page, backend, 2, '');
    }
  });

  test('records can be exported when no preset is configured', async ({ page, backend }) => {
    await backend.gotoModule('records');
    await backend.pageTree.open('styleguide TCA demo');

    await backend.contentFrame.locator('typo3-recordlist-record-download-button').first().click();

    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog.locator('.t3js-modal-title')).toContainText('Download Page:');

    const modalContent = await backend.modal.getModalContent();
    await expect(modalContent.locator('label', { hasText: 'Preset' })).toHaveCount(0);
    await modalContent.locator('input[name="filename"]').fill('test-download');

    await backend.modal.click({ name: 'download' });
    await expect(dialog).not.toBeVisible({ timeout: 30000 });
  });
});
