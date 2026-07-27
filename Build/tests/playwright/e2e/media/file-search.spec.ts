import { test, expect } from '../../fixtures/setup-fixtures';

const listForm = 'form[name="fileListForm"]';
const searchField = '#filelist-searchterm';
const searchButton = `${listForm} button[name="search"]`;

test.describe('Media search', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('media_management');
  });

  test('A cleared search term is not restored when opening a folder', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    const resource = (name: string) => contentFrame.locator(`[data-filelist-element][data-filelist-name="${name}"]`);

    await test.step('Search the storage root for "telephone"', async () => {
      await expect(resource('styleguide')).toBeVisible();

      await contentFrame.locator(searchField).fill('telephone');
      const moduleResponse = backend.waitForModuleResponse();
      await contentFrame.locator(searchButton).click();
      await moduleResponse;

      await expect(resource('telephone_box.jpg').first()).toBeVisible();
      await expect(resource('styleguide')).toHaveCount(0);

      // The search term must not become part of the form action, otherwise it is
      // carried over into every url built from the current request.
      await expect(contentFrame.locator(listForm)).not.toHaveAttribute('action', /searchTerm/);
    });

    await test.step('Clear the search term', async () => {
      await contentFrame.locator(searchField).fill('');
      const moduleResponse = backend.waitForModuleResponse();
      await contentFrame.locator(searchButton).click();
      await moduleResponse;

      await expect(resource('styleguide')).toBeVisible();
      await expect(contentFrame.locator(searchField)).toHaveValue('');
    });

    await test.step('Open a folder, the previous search term must not be applied again', async () => {
      const moduleResponse = backend.waitForModuleResponse();
      await resource('styleguide').locator('[data-filelist-action="primary"]').click();
      await moduleResponse;

      await expect(contentFrame.locator(searchField)).toHaveValue('');
      await expect(resource('bus_lane.jpg')).toBeVisible();
      await expect(resource('telephone_box.jpg')).toBeVisible();
      await expect(resource('underground.jpg')).toBeVisible();
    });
  });
});
