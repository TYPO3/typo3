import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

async function toggleAllLanguages(backend: BackendPage, on: boolean): Promise<void> {
  const dropdown = backend.contentFrame.locator('.module-docheader-navigation button.dropdown-toggle');
  await dropdown.click();
  const menu = backend.contentFrame.locator('.module-docheader-navigation .dropdown-menu');
  await expect(menu).toBeVisible();
  await menu.getByText(on ? 'Check all' : 'Uncheck all').click();
}

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo', 'staticdata');
  await toggleAllLanguages(backend, true);
  await expect(backend.contentFrame.getByRole('heading', { name: 'staticdata' })).toBeVisible();
});

// No afterEach reset - prepare instance is fresh per run and the beforeEach
// toggle is idempotent.

test('inline pages adding a resource to default language replicates to localized page', async ({ backend }) => {
  // Open the page-properties form for the default language page.
  const formEngineReady = await backend.formEngine.formEngineLoaded();
  await backend.contentFrame.locator('.module-docheader a[title="Edit page properties"]').click();
  await formEngineReady();

  await backend.contentFrame.getByRole('tab', { name: 'Resources', exact: true }).click();

  // Open the file browser modal via the inline insert action and pick a file.
  const modalContent = await backend.modal.open(
    backend.contentFrame.locator('div.active span[data-identifier="actions-insert-record"]').first()
  );
  await modalContent
    .locator('//div[contains(@class, "element-browser-main-sidebar")]//*[text()="styleguide"]/..')
    .click();
  const fileEntry = modalContent.locator('[data-filelist-name="telephone_box.jpg"] [data-filelist-action="primary"]');
  await expect(fileEntry).toBeVisible();
  await fileEntry.click();

  await expect(backend.modal.element).not.toBeVisible();

  await backend.formEngine.save();
  await backend.formEngine.close();

  // The "show all languages" state is reset when the form was open, so the
  // records list comes back showing only the default-language records.
  // Re-enable the toggle so the pages_translated panel reappears.
  await toggleAllLanguages(backend, true);

  // Open the localized page properties via the records list.
  const localizedRow = backend.contentFrame.getByText('staticdata - language 1').first();
  await expect(localizedRow).toBeVisible();
  const formEngineReady2 = await backend.formEngine.formEngineLoaded();
  await localizedRow.click();
  await formEngineReady2();

  await backend.contentFrame.getByRole('tab', { name: 'Resources', exact: true }).click();
  await expect(backend.contentFrame.locator('body')).toContainText('telephone_box.jpg');
});
