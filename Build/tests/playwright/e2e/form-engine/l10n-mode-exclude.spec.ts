import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

const recordList = '#recordlist-tx_styleguide_l10nreadonly';
const excludedFields = ['exclude_input', 'exclude_hidediff_input', 'exclude_hidediff_flex'];

async function showAllLanguages(backend: BackendPage): Promise<void> {
  const dropdown = backend.contentFrame.locator('.module-docheader-navigation button.dropdown-toggle');
  await dropdown.click();
  const menu = backend.contentFrame.locator('.module-docheader-navigation .dropdown-menu');
  await expect(menu).toBeVisible();
  // The toggle item offers "Deselect all" instead once every language is shown
  const checkAll = menu.locator('[aria-label="Select all available languages"]');
  if (await checkAll.count() === 0) {
    await dropdown.click();
    return;
  }
  const moduleResponse = backend.waitForModuleResponse();
  await checkAll.click();
  await moduleResponse;
}

async function openRecord(backend: BackendPage, rowSelector: string): Promise<void> {
  const formEngineReady = await backend.formEngine.formEngineLoaded();
  await backend.contentFrame
    .locator(`${recordList} ${rowSelector} a[aria-label="Edit record"]`)
    .first()
    .click();
  await formEngineReady();
}

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo', 'l10nreadonly');
  await showAllLanguages(backend);
});

test('fields with l10n_mode=exclude are rendered in the default language record', async ({ backend }) => {
  await openRecord(backend, 'tr[data-l10nparent="0"]');
  await backend.contentFrame.getByRole('tab', { name: 'Exclude', exact: true }).click();
  for (const fieldName of excludedFields) {
    await expect(backend.contentFrame.locator(`[name*="[${fieldName}]"]`).first()).toBeAttached();
  }
});

test('fields with l10n_mode=exclude are not rendered in a translated record unless defaultAsReadonly is set', async ({ backend }) => {
  await openRecord(backend, 'tr[data-l10nparent]:not([data-l10nparent="0"])');
  await expect(backend.contentFrame.getByRole('tab', { name: 'Flex', exact: true })).toBeVisible();
  await expect(backend.contentFrame.getByRole('tab', { name: 'Exclude', exact: true })).toHaveCount(0);
  for (const fieldName of excludedFields) {
    await expect(backend.contentFrame.locator(`[name*="[${fieldName}]"]`)).toHaveCount(0);
  }
});
