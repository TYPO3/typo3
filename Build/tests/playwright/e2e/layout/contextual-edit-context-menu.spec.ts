import type { Page } from '@playwright/test';
import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import type { BackendPage } from '../../fixtures/backend-page';

// The contextual record edit sheet is a modal <dialog>, which makes everything
// outside of it inert. The context menu of records referenced in the sheet must
// still be usable, and context menu actions that navigate the content frame must
// close the sheet instead of loading the target behind it.

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

  test('"Edit" opens the referenced record in a nested sheet', async ({ backend, page }) => {
    const sheet = await openPagePropertiesSheet(backend, page);
    const sheets = page.locator('typo3-backend-modal[data-contextual-record-edit]');

    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Edit', exact: true }).click();
    await expect(sheets).toHaveCount(2);

    const nestedSheet = sheets.last().frameLocator('iframe[name="modal_frame"]').locator('html');
    await expect(nestedSheet.locator('.t3js-contextual-close')).toBeVisible();
    await nestedSheet.locator('.t3js-contextual-close').click();
    await expect(sheets).toHaveCount(1);
  });

  test('a sheet is updated and can be closed after a nested sheet was saved and closed', async ({ backend, page }) => {
    const sheet = await openPagePropertiesSheet(backend, page);
    const sheets = page.locator('typo3-backend-modal[data-contextual-record-edit]');
    const referencedTitle = sheet.locator(`.form-section:has(${contentFromPidField}) .col-title [data-contextmenu-trigger]`);

    for (const title of ['elements group renamed', 'elements group']) {
      await (await contextMenuTriggerInSheet(sheet)).click();
      await page.locator('.context-menu').getByRole('menuitem', { name: 'Edit', exact: true }).click();
      await expect(sheets).toHaveCount(2);

      const nestedSheet = sheets.last().frameLocator('iframe[name="modal_frame"]').locator('html');
      await nestedSheet.getByRole('tab', { name: 'General' }).click();
      await nestedSheet.getByLabel('Page Title').fill(title);
      await nestedSheet.getByLabel('Page Title').press('Tab');
      await nestedSheet.locator('button[name="_savedok"]').click();
      await expect(nestedSheet.locator('.contextual-record-edit-saved-indicator')).toBeVisible();
      await nestedSheet.locator('.t3js-contextual-close').click();
      await expect(sheets).toHaveCount(1);

      await sheet.getByRole('tab', { name: 'Appearance' }).click();
      await expect(referencedTitle).toHaveText(title);
    }

    await sheet.locator('.t3js-contextual-close').click();
    await expect(sheets).toHaveCount(0);
  });

  test('the sheet can be closed with Escape after a nested sheet was closed with Escape', async ({ backend, page }) => {
    const sheet = await openPagePropertiesSheet(backend, page);
    const sheets = page.locator('typo3-backend-modal[data-contextual-record-edit]');

    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Edit', exact: true }).click();
    await expect(sheets).toHaveCount(2);
    const nestedSheet = sheets.last().frameLocator('iframe[name="modal_frame"]').locator('html');
    // The sheet focuses its fullscreen button once it is initialized
    await expect(nestedSheet.locator('.t3js-contextual-fullscreen')).toBeFocused();
    await nestedSheet.locator('.t3js-contextual-close').focus();
    await page.keyboard.press('Escape');
    await expect(sheets).toHaveCount(1);

    await sheets.first().frameLocator('iframe[name="modal_frame"]').locator('.t3js-contextual-close').focus();
    await page.keyboard.press('Escape');
    await expect(sheets).toHaveCount(0);
  });

  test('"Disable" commits the record, keeps the sheet open and reloads it', async ({ backend, page }) => {
    const sheet = await openPagePropertiesSheet(backend, page);
    const sheets = page.locator('typo3-backend-modal[data-contextual-record-edit]');
    const hiddenOverlay = sheet.locator(`.form-section:has(${contentFromPidField}) .col-icon .icon-overlay-hidden`);

    await expect(hiddenOverlay).toHaveCount(0);
    for (const [action, overlayCount] of [['Disable', 1], ['Enable', 0]] as const) {
      await (await contextMenuTriggerInSheet(sheet)).click();
      await page.locator('.context-menu').getByRole('menuitem', { name: action }).click();
      await expect(hiddenOverlay).toHaveCount(overlayCount);
      await expect(sheets).toHaveCount(1);
      await sheet.getByRole('tab', { name: 'Appearance' }).click();
    }
  });

  test('"Disable" with unsaved changes in the sheet asks first', async ({ backend, page }) => {
    const sheet = await openPagePropertiesSheet(backend, page);
    const hiddenOverlay = sheet.locator(`.form-section:has(${contentFromPidField}) .col-icon .icon-overlay-hidden`);
    const confirm = page.getByRole('dialog', { name: 'Unsaved changes' });

    await sheet.getByRole('tab', { name: 'General' }).click();
    await sheet.getByLabel('Page Title').fill('elements group (changed)');
    await sheet.getByRole('tab', { name: 'Appearance' }).click();

    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Disable' }).click();
    await confirm.getByRole('button', { name: 'Keep editing' }).click();
    await expect(confirm).not.toBeAttached();
    await sheet.getByRole('tab', { name: 'General' }).click();
    await expect(sheet.getByLabel('Page Title')).toHaveValue('elements group (changed)');

    await sheet.getByRole('tab', { name: 'Appearance' }).click();
    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Disable' }).click();
    await confirm.getByRole('button', { name: 'Discard changes' }).click();
    await sheet.getByRole('tab', { name: 'Appearance' }).click();
    await expect(hiddenOverlay).toHaveCount(1);

    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Enable' }).click();
    await expect(hiddenOverlay).toHaveCount(0);
  });

  test('unsaved changes of the same record can only be discarded after a nested sheet saved it', async ({ backend, page }) => {
    const sheet = await openPagePropertiesSheet(backend, page);
    const sheets = page.locator('typo3-backend-modal[data-contextual-record-edit]');
    const confirm = page.getByRole('dialog', { name: 'Unsaved changes' });

    await sheet.getByRole('tab', { name: 'General' }).click();
    await sheet.getByLabel('Subtitle').fill('changed below');
    await sheet.getByRole('tab', { name: 'Appearance' }).click();

    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Edit', exact: true }).click();
    const nestedSheet = sheets.last().frameLocator('iframe[name="modal_frame"]').locator('html');
    await nestedSheet.getByRole('tab', { name: 'General' }).click();
    await nestedSheet.getByLabel('Subtitle').fill('saved on top');
    await nestedSheet.getByLabel('Subtitle').press('Tab');
    await nestedSheet.locator('button[name="_savedok"]').click();
    await expect(nestedSheet.locator('.contextual-record-edit-saved-indicator')).toBeVisible();
    await nestedSheet.locator('.t3js-contextual-close').click();

    await expect(confirm).toBeVisible();
    await expect(confirm.getByRole('button', { name: 'Save', exact: true })).toHaveCount(0);
    await confirm.getByRole('button', { name: 'Discard changes' }).click();
    await sheet.getByRole('tab', { name: 'General' }).click();
    await expect(sheet.getByLabel('Subtitle')).toHaveValue('saved on top');
    await sheet.locator('.t3js-contextual-close').click();
    await expect(sheets).toHaveCount(0);
  });

  test('unsaved changes of another record can be saved after a nested sheet saved', async ({ backend, page }) => {
    const parentPageId = (await page.locator('#typo3-pagetree-tree [aria-level="2"]', {
      has: page.locator('.node-contentlabel', { hasText: /^styleguide TCA demo$/ }),
    }).first().getAttribute('data-id')).match(/(\d+)$/)[1];
    let sheet = await openPagePropertiesSheet(backend, page);
    const sheets = page.locator('typo3-backend-modal[data-contextual-record-edit]');
    const confirm = page.getByRole('dialog', { name: 'Unsaved changes' });

    await test.step('reference the parent page instead', async () => {
      const fieldName = await sheet.locator(contentFromPidField).getAttribute('data-formengine-input-name');
      await sheet.evaluate((_el, { fieldName, parentPageId }) => {
        window.postMessage({
          actionName: 'typo3:elementBrowser:elementAdded',
          fieldName,
          value: 'pages_' + parentPageId,
          label: 'styleguide TCA demo',
        }, window.location.origin);
      }, { fieldName, parentPageId });
      await sheet.locator('button[name="_savedok"]').click();
      sheet = await backend.modal.getModalContent();
      await expect(sheet.locator('.contextual-record-edit-saved-indicator')).toBeVisible();
      await sheet.getByRole('tab', { name: 'Appearance' }).click();
      await expect(sheet.locator(`.form-section:has(${contentFromPidField}) .col-title`)).toContainText('styleguide TCA demo');
    });

    await sheet.getByRole('tab', { name: 'General' }).click();
    await sheet.getByLabel('Subtitle').fill('saved from the prompt');
    await sheet.getByLabel('Subtitle').press('Tab');
    await expect(sheet.locator('.has-change')).not.toHaveCount(0);
    await sheet.getByRole('tab', { name: 'Appearance' }).click();

    await (await contextMenuTriggerInSheet(sheet)).click();
    await page.locator('.context-menu').getByRole('menuitem', { name: 'Edit', exact: true }).click();
    const nestedSheet = sheets.last().frameLocator('iframe[name="modal_frame"]').locator('html');
    await nestedSheet.getByRole('tab', { name: 'General' }).click();
    await nestedSheet.getByLabel('Subtitle').fill('saved on top');
    await nestedSheet.getByLabel('Subtitle').press('Tab');
    await nestedSheet.locator('button[name="_savedok"]').click();
    await expect(nestedSheet.locator('.contextual-record-edit-saved-indicator')).toBeVisible();
    await nestedSheet.locator('.t3js-contextual-close').click();

    await confirm.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(sheet.locator('.has-change')).toHaveCount(0);
    await expect(sheet.locator('.contextual-record-edit-saved-indicator')).toBeVisible();
    await sheet.getByRole('tab', { name: 'General' }).click();
    await expect(sheet.getByLabel('Subtitle')).toHaveValue('saved from the prompt');
    await sheet.locator('.t3js-contextual-close').click();
    await expect(sheets).toHaveCount(0);
  });

  test('a navigating context menu action closes the sheet', async ({ backend, page }) => {
    let sheet = await openPagePropertiesSheet(backend, page);
    const sheetFrame = page.locator('typo3-backend-modal iframe[name="modal_frame"]');
    const historyAction = page.locator('.context-menu').getByRole('menuitem', { name: 'History/Undo' });

    await test.step('unsaved changes are confirmed first, keep editing leaves the sheet open', async () => {
      await sheet.getByRole('tab', { name: 'General' }).click();
      await sheet.getByLabel('Page Title').fill('elements group (changed)');
      await sheet.getByRole('tab', { name: 'Appearance' }).click();

      await (await contextMenuTriggerInSheet(sheet)).click();
      await historyAction.click();
      const confirm = page.getByRole('dialog', { name: 'Unsaved changes' });
      await expect(confirm).toBeVisible();
      await confirm.getByRole('button', { name: 'Keep editing' }).click();
      await expect(confirm).not.toBeAttached();
      await expect(sheetFrame).toBeAttached();
    });

    await test.step('discarding the changes closes the sheet and navigates', async () => {
      await (await contextMenuTriggerInSheet(sheet)).click();
      await historyAction.click();
      const confirm = page.getByRole('dialog', { name: 'Unsaved changes' });
      await confirm.getByRole('button', { name: 'Discard changes' }).click();
      await expect(page.locator('typo3-backend-modal')).toHaveCount(0);
      await expect.poll(() => page.frame({ name: 'list_frame' })?.url()).toContain('/record/history');
    });

    await test.step('clean up the reference', async () => {
      await backend.gotoModule('web_layout');
      await backend.pageTree.open('styleguide TCA demo', 'elements group');
      sheet = await openPagePropertiesSheet(backend, page);
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
});
