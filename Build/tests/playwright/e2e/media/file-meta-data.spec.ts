import { test, expect } from '../../fixtures/setup-fixtures';
import * as path from 'node:path';
import * as fs from 'node:fs';

test.describe('File Metadata', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('media_management');
  });

  test('Metadata can be edited', async ({ backend }) => {
    await test.step('Search for file', async () => {
      // Fill search field
      const searchField = backend.contentFrame.locator('input[name="searchTerm"]');
      await searchField.fill('bus');

      // Submit search and wait for results
      const searchButton = backend.contentFrame.locator('button[type="submit"]');
      const moduleLoaded = backend.waitForModuleResponse();
      await searchButton.click();
      await moduleLoaded;

      // Wait for file list to be visible
      await expect(backend.contentFrame.locator('.t3-filelist-container')).toBeVisible();
    });

    await test.step('Open file metadata editor', async () => {
      // Click on the file name to open metadata editor
      // There may be multiple results, click the first one
      const fileButton = backend.contentFrame.getByRole('button', { name: 'bus_lane.jpg' }).first();
      await expect(fileButton).toBeVisible();
      const formEngineReady = await backend.formEngine.formEngineLoaded();
      await fileButton.click();
      await formEngineReady();

      // Verify the metadata editor heading
      await expect(backend.contentFrame.locator('h1')).toContainText('bus_lane.jpg');
    });
  });

  test('Closing the metadata editor returns to a folder with special characters', async ({ backend }) => {
    const folderName = 'Blüte ' + Date.now();
    const fileName = 'metadata.txt';
    const documentRoot = process.env.PLAYWRIGHT_DOCUMENT_ROOT
      || path.join(__dirname, '../../../../../typo3temp/var/tests/playwright-composer/public');
    fs.mkdirSync(path.join(documentRoot, 'fileadmin', folderName));
    fs.writeFileSync(path.join(documentRoot, 'fileadmin', folderName, fileName), '');

    await test.step('Open folder', async () => {
      await backend.fileTree.refresh();
      const folder = await backend.fileTree.open('fileadmin', folderName);
      await folder.click();
      await backend.fileTree.isReady();
      await expect(backend.contentFrame.getByRole('button', { name: fileName })).toBeVisible();
    });

    await test.step('Open and close file metadata editor', async () => {
      const formEngineReady = await backend.formEngine.formEngineLoaded();
      await backend.contentFrame.getByRole('button', { name: fileName }).click();
      await formEngineReady();
      await expect(backend.contentFrame.locator('h1')).toContainText(fileName);

      await backend.contentFrame.locator('.module-docheader .btn[title="Close"]').click();
      await expect(backend.contentFrame.locator('.t3-filelist-container').getByRole('button', { name: fileName })).toBeVisible();
    });
  });
});
