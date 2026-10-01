import { test, expect } from '../../fixtures/setup-fixtures';
import { setUserTsConfig } from '../../helper/user-tsconfig';

test.describe.serial('Record list pagination', () => {
  test('pressing enter in the page input only navigates to the given page', async ({ page, backend }) => {
    await setUserTsConfig(
      page,
      backend,
      2,
      'page.mod.web_list.itemsLimitPerTable = 2\n'
        + 'page.mod.web_list.itemsLimitSingleTable = 2',
    );
    try {
      await backend.gotoModule('records');
      await backend.pageTree.open('styleguide TCA demo');

      const contentFrame = backend.contentFrame;
      let moduleResponse = backend.waitForModuleResponse();
      await contentFrame.locator('table[data-table="pages"]').getByRole('link', { name: 'Expand table' }).click();
      await moduleResponse;

      const toggleVisibilityRequests: string[] = [];
      page.on('request', (request) => {
        if (request.url().includes('/record/toggle-visibility')) {
          toggleVisibilityRequests.push(request.url());
        }
      });

      const pageInput = contentFrame.locator('.t3js-recordlist-paging').first();
      await expect(pageInput).toHaveValue('1');
      await pageInput.fill('2');
      moduleResponse = backend.waitForModuleResponse();
      await pageInput.press('Enter');
      await moduleResponse;

      await expect(contentFrame.locator('.t3js-recordlist-paging').first()).toHaveValue('2');
      await expect(page.locator('typo3-backend-modal > dialog')).toHaveCount(0);
      expect(toggleVisibilityRequests).toEqual([]);
    } finally {
      await setUserTsConfig(page, backend, 2, '');
    }
  });
});
