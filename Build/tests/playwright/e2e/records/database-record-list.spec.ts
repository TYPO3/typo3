import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

const dataTable = 'table[data-table="tx_styleguide_displaycond"]';
const docHeaderNav = '.module-docheader-navigation';
const docHeaderButtons = '.module-docheader-buttons';

test.describe('Database record list filtering', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('records');
    await backend.pageTree.open('styleguide TCA demo', 'displaycond');
    // Ensure clean state independent of order: check all then uncheck all
    // leaves only the default language selected.
    await toggleAllLanguages(backend, true);
    await toggleAllLanguages(backend, false);
  });

  test('record list can be filtered by language', async ({ backend }) => {
    const contentFrame = backend.contentFrame;

    await openLanguageDropdown(backend);
    await expect(contentFrame.locator(
      `${docHeaderNav} .dropdown-menu [data-dropdowntoggle-status="active"][title*="Default language is always shown"]`,
    )).toBeAttached();
    // Close dropdown
    await contentFrame.locator(`${docHeaderNav} button.dropdown-toggle`).click();
    await checkRowVisibility(backend, ['1'], ['2', '3', '4', '5']);

    await selectLanguageInDropdown(backend, 'styleguide demo language german');
    await checkRowVisibility(backend, ['1', '3'], ['2', '4', '5']);

    await selectLanguageInDropdown(backend, 'styleguide demo language danish');
    await checkRowVisibility(backend, ['1', '3', '2'], ['4', '5']);

    await toggleAllLanguages(backend, true);
    await checkRowVisibility(backend, ['1', '2', '3', '4', '5']);

    await toggleAllLanguages(backend, false);
    await checkRowVisibility(backend, ['1'], ['2', '3', '4', '5']);
  });

  test('search keeps language filter', async ({ backend }) => {
    const contentFrame = backend.contentFrame;

    // Apply language filter first; selecting an extra language navigates the
    // module which would dismiss a previously-toggled search bar.
    await selectLanguageInDropdown(backend, 'styleguide demo language danish');

    await contentFrame.locator(`${docHeaderButtons} button.dropdown-toggle`).first().click();
    await contentFrame.locator(`${docHeaderButtons} .dropdown-menu [title="Show search"]`).click({ force: true });

    await contentFrame.locator('[name="searchTerm"]').fill('2');
    const moduleResponse = backend.waitForModuleResponse();
    await contentFrame.locator('.recordsearchbox-container button[name="search"]').click();
    await moduleResponse;

    await expect(contentFrame.locator('.module-docheader .icon-flags-dk')).toBeAttached();
    await checkRowVisibility(backend, ['1', '2'], ['3', '4', '5']);
  });
});

async function openLanguageDropdown(backend: BackendPage): Promise<void> {
  await backend.contentFrame.locator(`${docHeaderNav} button.dropdown-toggle`).click();
}

async function toggleAllLanguages(backend: BackendPage, check: boolean): Promise<void> {
  const contentFrame = backend.contentFrame;
  await openLanguageDropdown(backend);
  const moduleResponse = backend.waitForModuleResponse();
  await contentFrame.locator(`${docHeaderNav} .dropdown-menu`)
    .getByText(check ? 'Check all' : 'Uncheck all')
    .click({ force: true });
  await moduleResponse;
}

async function selectLanguageInDropdown(backend: BackendPage, languageName: string): Promise<void> {
  const contentFrame = backend.contentFrame;
  await openLanguageDropdown(backend);
  const moduleResponse = backend.waitForModuleResponse();
  await contentFrame.locator(`${docHeaderNav} .dropdown-menu`)
    .getByText(languageName)
    .click({ force: true });
  await moduleResponse;
}

async function checkRowVisibility(backend: BackendPage, mustSee: string[], mustNotSee: string[] = []): Promise<void> {
  const table = backend.contentFrame.locator(dataTable);
  await expect(table).toBeVisible();
  for (const value of mustSee) {
    await expect(table).toContainText(value);
  }
  for (const value of mustNotSee) {
    await expect(table).not.toContainText(value);
  }
}
