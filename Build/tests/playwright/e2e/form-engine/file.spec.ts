import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'file',
    listId: 'tx_styleguide_file',
  });
});

// Scope to the active tab pane. `.tab-content` includes inactive panes whose
// children are display:none, breaking `.first()` lookups on cross-tab DOM order.
const activeTab = '.tab-pane.active';

async function firstFalPanelTitle(backend): Promise<string> {
  // panel-title contains the filename followed by the relation type
  // (e.g. "bus_lane.jpg [sys_file_reference]") - split on whitespace to
  // get just the filename.
  const raw = (await backend.contentFrame.locator(`${activeTab} .panel-title`).first().textContent()) ?? '';
  return raw.trim().split(/\s+/)[0];
}

test('FAL relation info modal shows the filename in its title', async ({ backend }) => {
  const filename = await firstFalPanelTitle(backend);

  const modalContent = await backend.modal.open(
    backend.contentFrame.locator(`${activeTab} button[data-action="infowindow"]`).first()
  );

  await expect(modalContent.locator('.card-title')).toContainText(filename);
});

test('FAL relation hide button toggles the panel hidden state', async ({ backend }) => {
  // Hide, then unhide so the DB ends in the original state. Otherwise a retry
  // against the now-hidden record toggles back to visible and the assertion
  // for `.panel-hidden` fails.
  await backend.contentFrame.locator(`${activeTab} .t3js-toggle-visibility-button`).first().click();
  await backend.formEngine.save();
  await expect(backend.contentFrame.locator(`${activeTab} .panel-hidden`).first()).toBeAttached();

  await backend.contentFrame.locator(`${activeTab} .panel-hidden .t3js-toggle-visibility-button`).first().click();
  await backend.formEngine.save();
  await expect(backend.contentFrame.locator(`${activeTab} .panel-hidden`)).toHaveCount(0);
});

test('FAL relation delete removes the inline record', async ({ backend, page }) => {
  const filename = await firstFalPanelTitle(backend);

  await backend.contentFrame.locator(`${activeTab} .t3js-editform-delete-inline-record`).first().click();

  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'yes' });
  await expect(dialog).not.toBeVisible();

  await backend.formEngine.save();

  await expect(backend.contentFrame.locator(`${activeTab} .form-section`).first()).not.toContainText(filename);
});

// 1x1 transparent PNG
const pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

test('an invalid file is reported in a notification and the valid files are uploaded', async ({ backend, page }) => {
  const validFileName = 'valid' + Date.now() + '.png';
  const invalidFileName = 'invalid' + Date.now() + '.txt';
  const field = backend.contentFrame.locator('fieldset:has(> legend:has-text("file_1 "))');

  const fileChooserPromise = page.waitForEvent('filechooser');
  await field.getByRole('button', { name: 'Select & upload files' }).click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles([
    { name: invalidFileName, mimeType: 'text/plain', buffer: Buffer.from('invalid') },
    { name: validFileName, mimeType: 'image/png', buffer: Buffer.from(pngBase64, 'base64') },
  ]);

  const notification = page.locator('#alert-container .alert-danger');
  await expect(notification.locator('.alert-title')).toHaveText('Upload failed');
  await expect(notification.locator('.alert-message')).toContainText(`"${invalidFileName}" was not uploaded. Allowed file extensions: gif, jpg, jpeg`);
  await expect(field.locator('.panel-title', { hasText: validFileName })).toBeVisible({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.upload-queue-item', { hasText: invalidFileName })).toHaveCount(0);
});

test('FAL relations can be sorted with Alt+ArrowUp/Down and moved to the ends with Alt+Shift+ArrowUp/Down', async ({ backend, page }) => {
  const field = backend.contentFrame.locator('fieldset:has(> legend:has-text("file_1 "))');
  const titles = field.locator('.panel-title');
  const focusTarget = (fileName: string) =>
    field.locator('.form-irre-object').filter({ hasText: fileName }).locator('.sortableHandle');

  const fileNames = ['sorted-a', 'sorted-b', 'sorted-c'].map((name) => name + Date.now() + '.png');
  const fileChooserPromise = page.waitForEvent('filechooser');
  await field.getByRole('button', { name: 'Select & upload files' }).click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(fileNames.map((name) => ({ name, mimeType: 'image/png', buffer: Buffer.from(pngBase64, 'base64') })));
  for (const fileName of fileNames) {
    await expect(titles.filter({ hasText: fileName })).toBeVisible({ timeout: 12000 });
  }
  const hiddenField = field.locator('input[type="hidden"][name$="[file_1]"]');
  const domOrder = () => field.locator('.form-irre-object').evaluateAll((records: HTMLElement[]) => records.map((record) => record.dataset.objectUid));

  const orderBefore = (await titles.allTextContents()).map((title) => title.trim().split(/\s+/)[0]);
  const index = orderBefore.indexOf(fileNames[0]);

  await field.locator('.form-irre-object').filter({ hasText: fileNames[0] }).locator('.panel-heading button:has(.panel-title)').focus();
  await page.keyboard.press('Alt+ArrowDown');
  expect((await titles.allTextContents()).map((title) => title.trim().split(/\s+/)[0])).toEqual(orderBefore);

  await focusTarget(fileNames[0]).focus();
  await page.keyboard.press('Alt+ArrowDown');

  await expect(titles.nth(index + 1)).toContainText(fileNames[0]);
  await expect(focusTarget(fileNames[0])).toBeFocused();
  await expect(field.locator('typo3-formengine-container-inline > [role="status"]')).toHaveText(`Moved to position ${index + 2} of ${orderBefore.length}`);

  expect((await hiddenField.inputValue()).split(',')).toEqual(await domOrder());

  await focusTarget(fileNames[0]).focus();
  await page.keyboard.press('Alt+ArrowUp');

  await expect(titles.nth(index)).toContainText(fileNames[0]);
  await expect(focusTarget(fileNames[0])).toBeFocused();
  await expect(field.locator('typo3-formengine-container-inline > [role="status"]')).toHaveText(`Moved to position ${index + 1} of ${orderBefore.length}`);
  expect((await hiddenField.inputValue()).split(',')).toEqual(await domOrder());

  await page.keyboard.press('Alt+Shift+ArrowUp');

  await expect(titles.first()).toContainText(fileNames[0]);
  await expect(focusTarget(fileNames[0])).toBeFocused();
  await expect(field.locator('typo3-formengine-container-inline > [role="status"]')).toHaveText(`Moved to position 1 of ${orderBefore.length}`);
  expect((await hiddenField.inputValue()).split(',')).toEqual(await domOrder());

  await page.keyboard.press('Alt+Shift+ArrowDown');

  await expect(titles.last()).toContainText(fileNames[0]);
  await expect(focusTarget(fileNames[0])).toBeFocused();
  await expect(field.locator('typo3-formengine-container-inline > [role="status"]')).toHaveText(`Moved to position ${orderBefore.length} of ${orderBefore.length}`);
  expect((await hiddenField.inputValue()).split(',')).toEqual(await domOrder());
});
