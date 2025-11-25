import { test, expect } from '../../fixtures/setup-fixtures';

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
      await fileButton.click();

      // Wait for form engine to load
      await backend.formEngine.formEngineLoaded();

      // Verify the metadata editor heading
      await expect(backend.contentFrame.locator('h1')).toContainText('Edit File Metadata "bus_lane.jpg"');
    });
  });
});
