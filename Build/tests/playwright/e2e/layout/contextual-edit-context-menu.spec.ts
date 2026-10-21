import type { Page } from '@playwright/test';
import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import type { BackendPage } from '../../fixtures/backend-page';

// The contextual record edit sheet is a modal <dialog>, which makes everything
// outside of it inert. The context menu of records referenced in the sheet must
// still be usable.

const contentFromPidField = 'select[data-formengine-input-name$="[content_from_pid]"]';

async function openPagePropertiesSheet(backend: BackendPage, page: Page): Promise<Locator> {
  await backend.contentFrame.locator('typo3-backend-contextual-record-edit-trigger[context="pages"]').click();
  await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();
  const sheet = await backend.modal.getModalContent();
  await sheet.getByRole('tab', { name: 'Appearance' }).click();
  return sheet;
}

async function contextMenuTriggerInSheet(sheet: Locator): Promise<Locator> {
  const trigger = sheet.locator(`.form-section:has(${contentFromPidField}) .col-title [data-contextmenu-trigger]`);
  await expect(trigger).toBeVisible();
  return trigger;
}

test.describe.serial('context menu in the contextual record edit sheet', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('web_layout');
    await backend.pageTree.open('styleguide TCA demo', 'elements group');
  });

  test('a record referenced in the sheet gets a usable context menu', async ({ backend, page }) => {
    let sheet = await openPagePropertiesSheet(backend, page);

    await test.step('reference the page itself in "Show content from this page instead"', async () => {
      const fieldName = await sheet.locator(contentFromPidField).getAttribute('data-formengine-input-name');
      const pageUid = fieldName.match(/\[pages]\[(\d+)]/)[1];
      // Same message the element browser sends when a record is picked
      await sheet.evaluate((_el, { fieldName, pageUid }) => {
        window.postMessage({
          actionName: 'typo3:elementBrowser:elementAdded',
          fieldName,
          value: 'pages_' + pageUid,
          label: 'elements group',
        }, window.location.origin);
      }, { fieldName, pageUid });
      await sheet.locator('button[name="_savedok"]').click();
      await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();
      sheet = await backend.modal.getModalContent();
      await sheet.getByRole('tab', { name: 'Appearance' }).click();
    });

    await test.step('the context menu opens above the sheet and can be used', async () => {
      await (await contextMenuTriggerInSheet(sheet)).click();
      const menu = page.locator('.context-menu');
      await expect(menu).toBeVisible();
      await menu.getByRole('menuitem', { name: 'Info' }).click();
      const infoDialog = page.locator('typo3-backend-modal').last();
      await expect(infoDialog.locator('.modal-title, .t3js-modal-title')).toContainText('elements group');
      await infoDialog.locator('.t3js-modal-close').click();
      await expect(page.locator('typo3-backend-modal')).toHaveCount(1);
    });

    await test.step('Escape closes the context menu but keeps the sheet open', async () => {
      await (await contextMenuTriggerInSheet(sheet)).click();
      const menu = page.locator('.context-menu');
      await expect(menu.getByRole('menuitem').first()).toBeFocused();
      await page.keyboard.press('Escape');
      await expect(menu).not.toBeAttached();
      await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();
    });
  });

  test('the reference can be removed again', async ({ backend, page }) => {
    let sheet = await openPagePropertiesSheet(backend, page);
    await sheet.locator(contentFromPidField).selectOption({ index: 0 });
    await sheet.locator(`.form-section:has(${contentFromPidField}) .t3js-btn-removeoption`).click();
    await sheet.locator('button[name="_savedok"]').click();
    await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();
    sheet = await backend.modal.getModalContent();
    await expect(sheet.locator(`.form-section:has(${contentFromPidField}) .col-title`)).toHaveCount(0);
    await sheet.locator('.t3js-contextual-close').click();
    await expect(page.locator('typo3-backend-modal')).toHaveCount(0);
  });
});
