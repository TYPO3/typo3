import { test, expect } from '../../fixtures/setup-fixtures';

const toolbarItemSelector = '.t3js-toolbar-item-search';
const searchFieldSelector = 'input[type="search"][name="query"]';

test('Live search autocompletes a record and Edit action opens the edit form', async ({ page, backend }) => {
  await page.goto('module/web/layout');
  await expect(page.locator(searchFieldSelector)).not.toBeVisible();
  await page.locator(toolbarItemSelector).click();

  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();

  await dialog.locator(searchFieldSelector).fill('adm');

  const adminResult = dialog.locator('typo3-backend-live-search-result-item').filter({ hasText: 'admin' }).first();
  await expect(adminResult).toBeVisible();
  await dialog.locator('typo3-backend-live-search-result-item [title~="admin"] + .livesearch-expand-action').first().click();
  await dialog.locator('typo3-backend-live-search-result-item-action', { hasText: 'Edit' }).click();

  await expect(backend.contentFrame.locator('#EditDocumentController')).toBeVisible();
  await expect(backend.contentFrame.locator('h1')).toContainText('admin');
});

test('Live search shows empty result info and Escape clears the field', async ({ page }) => {
  await page.goto('module/web/layout');
  await page.locator(toolbarItemSelector).click();

  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();

  // todo: check why TYPO3 does not return a result for "Kasper" by itself
  await dialog.locator(searchFieldSelector).fill('Kasper = Jesus # joh316');
  await expect(dialog.locator('div.alert')).toContainText('No results found.');

  await dialog.locator(searchFieldSelector).press('Escape');
  await expect(dialog.locator(searchFieldSelector)).toHaveValue('');
});
