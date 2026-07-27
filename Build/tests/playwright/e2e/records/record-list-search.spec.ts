import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

const docHeaderButtons = '.module-docheader-buttons';
const searchBox = '[data-recordsearchbox-container]';
const searchField = `${searchBox} input[name="searchTerm"]`;
const searchButton = `${searchBox} button[name="search"]`;
const pagesTable = 'table[data-table="pages"]';

test.describe('Database record list search', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('records');
    await backend.pageTree.open('Root');
    await showSearchBox(backend);
  });

  test('A cleared search term is not restored when navigating', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    const table = contentFrame.locator(pagesTable);

    await test.step('Search for a single page record', async () => {
      await contentFrame.locator(searchField).fill('Dummy 1-33');
      const moduleResponse = backend.waitForModuleResponse();
      await contentFrame.locator(searchButton).click();
      await moduleResponse;

      await expect(table).toContainText('Dummy 1-33');
      await expect(table).not.toContainText('Dummy 1-2');

      // The search term must not become part of the form action, otherwise it is
      // carried over into every url built from the current request.
      await expect(contentFrame.locator(`${searchBox} form`)).not.toHaveAttribute('action', /searchTerm/);
    });

    await test.step('Clear the search term', async () => {
      await contentFrame.locator(searchField).fill('');
      const moduleResponse = backend.waitForModuleResponse();
      await contentFrame.locator(searchButton).click();
      await moduleResponse;

      await expect(table).toContainText('Dummy 1-2');
      await expect(contentFrame.locator(searchField)).toHaveValue('');
    });

    await test.step('Switch to the single table view, the previous search term must not be applied again', async () => {
      const moduleResponse = backend.waitForModuleResponse();
      await contentFrame.getByRole('link', { name: 'Expand table' }).click();
      await moduleResponse;

      await expect(contentFrame.locator(searchField)).toHaveValue('');
      await expect(table).toContainText('Dummy 1-2');
      await expect(table).toContainText('Dummy 1-50');
    });
  });
});

async function showSearchBox(backend: BackendPage): Promise<void> {
  const contentFrame = backend.contentFrame;
  if (await contentFrame.locator(searchBox).isVisible()) {
    return;
  }
  await contentFrame.locator(`${docHeaderButtons} button.dropdown-toggle`).first().click();
  const moduleResponse = backend.waitForModuleResponse();
  await contentFrame.locator(`${docHeaderButtons} .dropdown-menu [title="Show search"]`).click({ force: true });
  await moduleResponse;
  await expect(contentFrame.locator(searchBox)).toBeVisible();
}
