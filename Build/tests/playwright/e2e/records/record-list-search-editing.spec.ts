import { test, expect } from '../../fixtures/setup-fixtures';

const docHeaderButtons = '.module-docheader-buttons';
const resultTable = 'table[data-table="tx_styleguide_elements_basic"]';

test.describe('Record list search result editing', () => {
  test('closing a record of a search result returns to the searched page', async ({ backend }) => {
    const contentFrame = backend.contentFrame;

    await backend.gotoModule('records');
    await backend.pageTree.open('styleguide TCA demo');

    const pageTitle = contentFrame.locator('typo3-backend-editable-page-title');
    const searchedPageId = await pageTitle.getAttribute('pageId');

    await contentFrame.locator(`${docHeaderButtons} button.dropdown-toggle`).first().click();
    await contentFrame.locator(`${docHeaderButtons} .dropdown-menu [title="Show search"]`).click({ force: true });

    // Search across subpages, so that the results live on other pages than the one being searched from
    await contentFrame.locator('[name="searchTerm"]').fill('lipsum');
    await contentFrame.locator('select[name="search_levels"]').selectOption('4');
    const moduleResponse = backend.waitForModuleResponse();
    await contentFrame.locator('.recordsearchbox-container button[name="search"]').click();
    await moduleResponse;
    await expect(contentFrame.locator(resultTable)).toBeVisible();

    // Edit the first record of the search result and close it again
    const formEngineLoaded = await backend.formEngine.formEngineLoaded();
    await contentFrame.locator(`${resultTable} tbody a[href*="record/edit"]`).first().click();
    await formEngineLoaded();
    await expect(backend.formEngine.container).toBeAttached();

    await backend.formEngine.close();

    // The record list must show the page the search was started from, not the page of the record
    await expect(pageTitle).toHaveAttribute('pageId', searchedPageId);
    await expect(contentFrame.locator('[name="searchTerm"]')).toHaveValue('lipsum');
    await expect(contentFrame.locator(resultTable)).toBeVisible();
  });
});
