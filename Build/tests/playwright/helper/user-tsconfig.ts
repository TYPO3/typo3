import { expect, type Page } from '@playwright/test';
import type { BackendPage } from '../fixtures/backend-page';

export async function setUserTsConfig(page: Page, backend: BackendPage, userId: number, tsConfig: string): Promise<void> {
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
