import { test, expect } from '../../fixtures/setup-fixtures';
import * as path from 'node:path';
import * as fs from 'node:fs';

test.describe('File Operations', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('media_management');
  });

  test('File CRUD operations', async ({ backend, page }) => {
    const fileName = 'typo3-test-' + Date.now() + '.txt';
    const codeMirrorSelector = 'typo3-t3editor-codemirror[name="data[editfile][0][data]"]';
    const fileContent = 'Some Text';

    await test.step('Create a file with content', async () => {
      // Click "New File" button
      const newFileButton = backend.contentFrame.locator('.module-docheader .btn[title="New File"]');
      await backend.modal.open(newFileButton);

      // Get modal iframe
      const modalFrame = page.frameLocator('typo3-backend-modal iframe[name="modal_frame"]');

      // Verify dialog title
      await expect(modalFrame.getByRole('heading', { name: 'Create new textfile' })).toBeVisible();

      const fileNameInput = modalFrame.locator('input[name="data[newfile][0][data]"]');
      await fileNameInput.fill(fileName);
      await modalFrame.getByRole('button', { name: 'Create file' }).click();

      // await expect(modalFrame.locator(flashMessageSelector)).toContainText('File created:');

      // Wait for CodeMirror to be visible
      const codeMirror = modalFrame.locator(codeMirrorSelector);
      await expect(codeMirror).toBeVisible();

      await expect(modalFrame.locator('.cm-activeLineGutter').first()).toBeVisible();

      // Set content using CodeMirror API
      await codeMirror.locator('.cm-activeLine').fill(fileContent);

      await expect(codeMirror.locator('.cm-activeLine')).toContainText(fileContent);
    });

    await test.step('Save the file', async () => {
      const modalFrame = page.frameLocator('typo3-backend-modal iframe[name="modal_frame"]');

      // Click save button
      const saveButton = modalFrame.locator('.module-docheader button[name="_savedok"]');
      await saveButton.click();

      // Wait for CodeMirror to be visible again after save
      await expect(modalFrame.locator(codeMirrorSelector)).toBeVisible();

      // Verify content is preserved
      await modalFrame.locator(codeMirrorSelector).evaluate((editor: any) => {
        const content = editor.getContent();
        if (content !== 'Some Text') {
          throw new Error(`Expected content 'Some Text' but got '${content}'`);
        }
      });
    });

    await test.step('Close the file and return to list view', async () => {
      const modalFrame = page.frameLocator('typo3-backend-modal iframe[name="modal_frame"]');

      // Click close button
      const closeButton = modalFrame.locator('.module-docheader .btn[title="Close"]');
      await closeButton.click();

      // Close the modal by clicking the close button in the dialog
      const modal = page.locator('typo3-backend-modal > dialog');
      await expect(modal).toBeVisible();
      await page.locator('typo3-backend-modal .t3js-modal-close').click();
      await expect(modal).not.toBeVisible();

      // Verify file appears in list
      await expect(backend.contentFrame.locator('[data-multi-record-selection-element="true"]').getByText(fileName)).toBeVisible();
    });

    await test.step('Delete the file', async () => {
      // Right click on the file
      const fileRow = backend.contentFrame.locator(`[data-filelist-identifier="1:/${fileName}"] [data-filelist-action="primary"]`);
      await fileRow.click({ button: 'right' });

      // Click delete from context menu
      await page.locator('button[data-contextmenu-id="root_delete"]').click();

      // Confirm deletion in modal
      const modal = page.locator('typo3-backend-modal > dialog');
      await expect(modal).toBeVisible();
      await backend.modal.click({ text: 'Yes, delete this file' });

      // Wait for file to be removed from list
      await expect(backend.contentFrame.locator(`[data-filelist-identifier="1:/${fileName}"]`)).not.toBeVisible();

      // Verify file is no longer in the list
      await expect(backend.contentFrame.locator('[data-multi-record-selection-element="true"]').getByRole('button').getByText(fileName)).not.toBeVisible();
    });
  });

  test('Upload file', async ({ backend, page }) => {
    // Prepare file for upload with a timestamp attached to it
    const randomUploadFileName = 'blue_mountains' + Date.now() + '.jpg';
    const filePath = path.join(__dirname, '../../../../../typo3/sysext/core/Tests/Acceptance/Fixtures/Images', 'blue_mountains.jpg');
    const randomUploadFile = path.join(__dirname, '../../../../../typo3temp/var/tests/playwright-composer/public/fileadmin/_temp_', randomUploadFileName);
    fs.copyFileSync(filePath, randomUploadFile);

    const alertContainer = '#alert-container';

    await test.step('Upload file', async () => {
      // Set file input and upload file
      const fileInput = backend.contentFrame.locator('input.upload-file-picker');
      await fileInput.setInputFiles(randomUploadFile);

      // Wait for upload progress to disappear
      await expect(backend.contentFrame.locator('.upload-queue-item .upload-queue-progress')).not.toBeVisible({ timeout: 30000 });
    });

    await test.step('Verify upload notification', async () => {
      // Wait for filename in alert container
      const alert = page.locator(alertContainer);
      await expect(alert.getByText(randomUploadFileName)).toBeVisible({ timeout: 12000 });

      // Close alert
      await alert.locator('[role="alertdialog"]', { hasText: 'Uploading file' }).locator('.close').click();

      // Wait for reload notification
      await expect(alert.getByText('Reload filelist')).toBeVisible({ timeout: 15000 });

      // Dismiss reload notification
      await alert.locator('a[title="Dismiss"]').click();
    });

    await test.step('Verify file in upload queue and list', async () => {
      // Verify file appears in upload queue
      await expect(backend.contentFrame.locator('.upload-queue-item').getByText(randomUploadFileName)).toBeVisible();

      // Reload the file list
      await backend.contentFrame.locator('a[title="Reload"]').click();
      await backend.waitForModuleResponse();

      // Verify file appears in the file list
      await expect(backend.contentFrame.locator('[data-multi-record-selection-element="true"]').getByText(randomUploadFileName).first()).toBeVisible();
    });
  });
});
