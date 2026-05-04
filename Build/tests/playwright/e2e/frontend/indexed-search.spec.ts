import { test, expect } from '../../fixtures/setup-fixtures';

const indexedSearchUrl = '/styleguide-demo-242/indexedsearch-pi2';

const advancedSelectors = [
  '#tx-indexedsearch-selectbox-searchtype',
  '#tx-indexedsearch-selectbox-defaultoperand',
  '#tx-indexedsearch-selectbox-media',
  '#tx-indexedsearch-selectbox-sections',
  '#tx-indexedsearch-selectbox-freeIndexUid',
  '#tx-indexedsearch-selectbox-order',
  '#tx-indexedsearch-selectbox-desc',
  '#tx-indexedsearch-selectbox-results',
  '#tx-indexedsearch-selectbox-group',
];

test.describe('Frontend indexed search plugin', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(indexedSearchUrl);
  });

  test('shows a "no results" message for an unindexed phrase', async ({ page }) => {
    await page.locator('#tx-indexedsearch-searchbox-sword').fill('bumblebee phrase');
    await page.locator('.tx-indexedsearch-search-submit input[type=submit]').click();
    await expect(page.locator('.tx-indexedsearch-info-noresult')).toContainText('No results found.');
  });

  test('toggles between regular and advanced search', async ({ page }) => {
    await page.locator('#tx-indexedsearch-searchbox-sword').fill('search word');

    await page.getByRole('link', { name: 'Advanced search' }).click();
    for (const selector of advancedSelectors) {
      await expect(page.locator(selector)).toBeVisible();
    }
    await page.locator('.tx-indexedsearch-search-submit input[type=submit]').click();
    await expect(page.locator('.tx-indexedsearch-info-noresult')).toContainText('No results found.');

    await page.getByRole('link', { name: 'Regular search' }).click();
    for (const selector of advancedSelectors) {
      await expect(page.locator(selector)).toHaveCount(0);
    }
  });
});
