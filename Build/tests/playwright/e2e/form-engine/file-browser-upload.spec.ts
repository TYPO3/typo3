import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

// 1x1 transparent PNG
const pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'file',
    listId: 'tx_styleguide_file',
  });
});

async function openFileBrowser(backend): Promise<Locator> {
  const fileBrowser = await backend.modal.open(
    backend.contentFrame.locator('.tab-pane.active .t3js-element-browser').first()
  );
  // Show two folders one after the other: the upload has to go to the folder shown last
  await fileBrowser.locator('.nodes-container .nodes-list [role="treeitem"][title="styleguide"]').click();
  await expect(fileBrowser.getByText('fileadmin: /styleguide/')).toBeVisible();
  await fileBrowser.locator('.nodes-container .nodes-list [role="treeitem"][title="fileadmin"]').click();
  await expect(fileBrowser.getByText('fileadmin: /', { exact: true })).toBeVisible();
  return fileBrowser;
}

async function transferImage(target: Locator, eventType: 'paste' | 'drop', fileName: string): Promise<void> {
  await target.evaluate((element: HTMLElement, { eventType, fileName, pngBase64 }) => {
    const bytes = Uint8Array.from(atob(pngBase64), (character) => character.charCodeAt(0));
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(new File([bytes], fileName, { type: 'image/png' }));
    const options = { bubbles: true, cancelable: true };
    if (eventType === 'paste') {
      element.dispatchEvent(new ClipboardEvent('paste', { ...options, clipboardData: dataTransfer }));
      return;
    }
    element.dispatchEvent(new DragEvent('dragover', { ...options, dataTransfer }));
    // The drop hits whatever is shown on top of the element while dragging, e.g. the dropzone
    const rect = element.getBoundingClientRect();
    const dropTarget = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
    dropTarget.dispatchEvent(new DragEvent('drop', { ...options, dataTransfer }));
  }, { eventType, fileName, pngBase64 });
}

async function dragImage(target: Locator, eventType: 'dragover' | 'dragleave'): Promise<void> {
  await target.evaluate((element: HTMLElement, eventType) => {
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(new File([''], 'dragged.png', { type: 'image/png' }));
    element.dispatchEvent(new DragEvent(eventType, { bubbles: true, cancelable: true, dataTransfer }));
  }, eventType);
}

async function selectFile(backend, fileBrowser: Locator, fileName: string): Promise<void> {
  await expect(fileBrowser.getByText('fileadmin: /', { exact: true })).toBeVisible();
  await fileBrowser.locator('[data-filelist-element]').getByText(fileName, { exact: true }).first().click({ timeout: 12000 });
  await expect(backend.contentFrame.locator('.tab-pane.active .panel-title', { hasText: fileName })).toBeVisible();
}

test('a file pasted into the file browser can be selected afterwards', async ({ backend }) => {
  const fileName = 'pasted' + Date.now() + '.png';
  const fileBrowser = await openFileBrowser(backend);

  await transferImage(fileBrowser.locator('body'), 'paste', fileName);

  await selectFile(backend, fileBrowser, fileName);
});

test('a file dropped anywhere into the content of the file browser is uploaded to the folder shown', async ({ backend }) => {
  const ignoredFileName = 'dropped-on-tree' + Date.now() + '.png';
  const fileName = 'dropped' + Date.now() + '.png';
  const fileBrowser = await openFileBrowser(backend);

  await transferImage(fileBrowser.locator('.element-browser-main-sidebar'), 'drop', ignoredFileName);
  // Drop into the content afterwards: once that upload has finished, the ignored one would be listed as well
  await transferImage(fileBrowser.locator('[data-filelist-element]').first(), 'drop', fileName);

  await expect(fileBrowser.locator('[data-filelist-element]').getByText(fileName, { exact: true }).first()).toBeVisible({ timeout: 12000 });
  await expect(fileBrowser.locator('[data-filelist-element]').getByText(ignoredFileName, { exact: true })).toHaveCount(0);
  // The uploader of the folder shown before must not have uploaded the file as well
  await fileBrowser.locator('.nodes-container .nodes-list [role="treeitem"][title="styleguide"]').click();
  await expect(fileBrowser.getByText('fileadmin: /styleguide/')).toBeVisible();
  await expect(fileBrowser.locator('[data-filelist-element]').getByText(fileName, { exact: true })).toHaveCount(0);
});

test('the dropzone is only shown while a file is dragged over the content of the file browser', async ({ backend }) => {
  const fileBrowser = await openFileBrowser(backend);
  const dropzone = fileBrowser.locator('.file-browser-upload .dropzone');

  await dragImage(fileBrowser.locator('.element-browser-main-sidebar'), 'dragover');
  await expect(dropzone).toBeHidden();

  await dragImage(fileBrowser.locator('[data-filelist-element]').first(), 'dragover');
  await expect(dropzone).toBeVisible();

  // Leaving the content for the folder tree closes the dropzone, the next dragover must not show it again
  await dragImage(dropzone.locator('.dropzone-mask'), 'dragleave');
  await dragImage(fileBrowser.locator('.element-browser-main-sidebar'), 'dragover');
  await expect(dropzone).toBeHidden();
});
