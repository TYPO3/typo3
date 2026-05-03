import { test, expect } from '../../fixtures/setup-fixtures';
import { Page } from '@playwright/test';
import { BackendPage } from '../../fixtures/backend-page';

test.describe.serial('Record download with preset configured via user TSconfig', () => {
  test('records can be exported with preset', async ({ page, backend }) => {
    await setUserTsConfig(
      page,
      backend,
      1,
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
      await setUserTsConfig(page, backend, 1, '');
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

async function setUserTsConfig(page: Page, backend: BackendPage, userId: number, tsConfig: string): Promise<void> {
  await backend.gotoModule('backend_user_management');
  const contentFrame = backend.contentFrame;

  await contentFrame.locator('.t3js-module-docheader-buttons').locator('.dropdown-toggle', { hasText: 'Module Menu:' }).click();
  const moduleResponse = backend.waitForModuleResponse();
  await contentFrame.locator('.t3js-module-docheader-buttons .dropdown-menu').getByRole('link', { name: 'Backend users' }).click();
  await moduleResponse;

  await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();
  await contentFrame
    .locator(`#typo3-backend-user-list tbody tr:has(button[data-contextmenu-uid="${userId}"]) a[title="Edit"]`)
    .first()
    .click();
  await expect(contentFrame.locator('#EditDocumentController')).toBeVisible();

  // TSconfig field lives in the "Options" tab on be_users edit
  await contentFrame.getByRole('tab', { name: 'Options' }).click();

  const codeMirrorSelector = `typo3-t3editor-codemirror[name="data[be_users][${userId}][TSconfig]"]`;
  await expect(contentFrame.locator(codeMirrorSelector)).toBeVisible();
  await contentFrame.locator(codeMirrorSelector).evaluate((el, content) => {
    (el as HTMLElement & { setContent: (s: string) => void }).setContent(content);
  }, tsConfig);

  const formEngineLoaded = await backend.formEngine.formEngineLoaded();
  await backend.formEngine.saveButton.click();

  // Editing other users' TSconfig may require sudo-mode verification
  const sudoModal = page.locator('typo3-backend-modal > dialog');
  try {
    await expect(sudoModal).toBeVisible({ timeout: 5000 });
    await expect(sudoModal).toContainText('Verify with user password');
    await sudoModal.locator('input[type="password"]').fill('password');
    await backend.modal.click({ name: 'verify' });
    await expect(sudoModal).not.toBeVisible();
  } catch {
    // No sudo verification required
  }
  await formEngineLoaded();

  await backend.formEngine.close();
  await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();
}
