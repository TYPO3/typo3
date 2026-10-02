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

async function pasteImage(backend, fileName: string): Promise<void> {
  await backend.contentFrame.locator('body').evaluate((body: HTMLElement, { fileName, pngBase64 }) => {
    const bytes = Uint8Array.from(atob(pngBase64), (character) => character.charCodeAt(0));
    const clipboardData = new DataTransfer();
    clipboardData.items.add(new File([bytes], fileName, { type: 'image/png' }));
    body.dispatchEvent(new ClipboardEvent('paste', { clipboardData, bubbles: true, cancelable: true }));
  }, { fileName, pngBase64 });
}

function fileField(backend, fieldName: string) {
  return backend.contentFrame.locator(`fieldset:has(> legend:has-text("${fieldName} "))`);
}

test('pasting a file uploads it to the file field interacted with last', async ({ backend }) => {
  await backend.contentFrame.getByRole('tab', { name: 'media', exact: true }).click();
  const fileName = 'pasted' + Date.now() + '.png';

  await fileField(backend, 'file_4').locator('legend').click();
  await pasteImage(backend, fileName);

  await expect(fileField(backend, 'file_4').locator('.panel-title', { hasText: fileName })).toBeVisible({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.panel-title', { hasText: fileName })).toHaveCount(1);
});

test('pasting a file uploads it to the only file field of the active tab without interacting with it', async ({ backend }) => {
  const fileName = 'pasted' + Date.now() + '.png';

  await pasteImage(backend, fileName);

  await expect(fileField(backend, 'file_1').locator('.panel-title', { hasText: fileName })).toBeVisible({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.panel-title', { hasText: fileName })).toHaveCount(1);
});

test('pasting a file without interacting with one of several file fields asks for the field to add it to', async ({ backend, page }) => {
  await backend.contentFrame.getByRole('tab', { name: 'media', exact: true }).click();
  const fileName = 'pasted' + Date.now() + '.png';

  await pasteImage(backend, fileName);
  const fieldChoices = page.locator('typo3-backend-modal .t3js-modal-body button');
  await expect(fieldChoices).toHaveCount(4);
  await fieldChoices.filter({ hasText: 'file_4 ' }).click();

  await expect(fileField(backend, 'file_4').locator('.panel-title', { hasText: fileName })).toBeVisible({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.panel-title', { hasText: fileName })).toHaveCount(1);
});

test('cancelling the choice of a file field does not upload the pasted file', async ({ backend, page }) => {
  await backend.contentFrame.getByRole('tab', { name: 'media', exact: true }).click();
  const ignoredFileName = 'pasted-ignored' + Date.now() + '.png';
  const fileName = 'pasted' + Date.now() + '.png';

  await pasteImage(backend, ignoredFileName);
  await backend.modal.click({ name: 'cancel' });
  await expect(page.locator('typo3-backend-modal')).toHaveCount(0);
  // Paste into a field afterwards: once that upload has finished, the ignored one would be visible as well
  await fileField(backend, 'file_3').locator('legend').click();
  await pasteImage(backend, fileName);

  await expect(fileField(backend, 'file_3').locator('.panel-title', { hasText: fileName })).toBeVisible({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.upload-queue-item, .panel-title').filter({ hasText: ignoredFileName })).toHaveCount(0);
});

test('a file dropped anywhere onto a file field is uploaded to that field', async ({ backend }) => {
  await backend.contentFrame.getByRole('tab', { name: 'media', exact: true }).click();
  const fileName = 'dropped' + Date.now() + '.png';

  await fileField(backend, 'file_3').locator('.panel').first().evaluate((element: HTMLElement, { fileName, pngBase64 }) => {
    const bytes = Uint8Array.from(atob(pngBase64), (character) => character.charCodeAt(0));
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(new File([bytes], fileName, { type: 'image/png' }));
    const options = { bubbles: true, cancelable: true, dataTransfer };
    element.dispatchEvent(new DragEvent('dragover', options));
    // The drop hits whatever is shown on top of the element while dragging, e.g. the dropzone
    const rect = element.getBoundingClientRect();
    document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2).dispatchEvent(new DragEvent('drop', options));
  }, { fileName, pngBase64 });

  await expect(fileField(backend, 'file_3').locator('.panel-title', { hasText: fileName })).toBeVisible({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.panel-title', { hasText: fileName })).toHaveCount(1);
});

async function dragImage(target, eventType: 'dragover' | 'dragleave'): Promise<void> {
  await target.evaluate((element: HTMLElement, eventType) => {
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(new File([''], 'dragged.png', { type: 'image/png' }));
    element.dispatchEvent(new DragEvent(eventType, { bubbles: true, cancelable: true, dataTransfer }));
  }, eventType);
}

test('the dropzone of a file field is only shown while a file is dragged over that field', async ({ backend }) => {
  await backend.contentFrame.getByRole('tab', { name: 'media', exact: true }).click();
  const dropzone = fileField(backend, 'file_3').locator('.dropzone');
  const otherDropzone = fileField(backend, 'file_4').locator('.dropzone');

  await dragImage(fileField(backend, 'file_3').locator('.panel').first(), 'dragover');
  await expect(dropzone).toBeVisible();
  await expect(otherDropzone).toBeHidden();

  // Leaving the field closes its dropzone, the next dragover outside of it must not show it again
  await dragImage(dropzone.locator('.dropzone-mask'), 'dragleave');
  await dragImage(fileField(backend, 'file_4').locator('legend'), 'dragover');
  await expect(dropzone).toBeHidden();
  await expect(otherDropzone).toBeVisible();
});
