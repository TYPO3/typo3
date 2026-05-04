import { test, expect } from '../../fixtures/setup-fixtures';

const contentToggleSelector = '[data-modulemenu-identifier="content"]';
const contentSubItemsSelector = '[data-modulemenu-identifier="content"] + .modulemenu-group-container .modulemenu-action';

test.describe('Module Menu', () => {
  test.beforeEach(async ({ page, backend }) => {
    await page.goto('module/web/layout');
    await backend.sidebar.expand();
  });

  test('module menu groups are collapsible', async ({ page }) => {
    const contentToggle = page.locator(contentToggleSelector);
    const firstSubItem = page.locator(contentSubItemsSelector).first();

    await expect(firstSubItem).toBeVisible();

    await contentToggle.click();
    await expect(firstSubItem).not.toBeVisible();

    await contentToggle.click();
    await expect(firstSubItem).toBeVisible();
  });

  test('selecting a module highlights it', async ({ page, backend }) => {
    const subItemsCount = await page.locator(contentSubItemsSelector).count();
    expect(subItemsCount).toBeGreaterThanOrEqual(2);
    expect(subItemsCount).toBeLessThanOrEqual(20);

    const recordsLink = page.locator('[data-modulemenu-identifier="records"]');
    await expect(recordsLink).not.toHaveClass(/modulemenu-action-active/);

    const moduleLoaded = await backend.moduleLoaded('records');
    await recordsLink.click();
    await moduleLoaded();

    await expect(recordsLink).toHaveClass(/modulemenu-action-active/);
  });
});
