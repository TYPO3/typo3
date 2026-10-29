import { test, expect } from '../../fixtures/setup-fixtures';

const contentToggleSelector = '[data-modulemenu-identifier="content"]';
const contentGroupContainerSelector = '[data-modulemenu-identifier="content"] + .modulemenu-group-container';
const contentSubItemsSelector = contentGroupContainerSelector + ' .modulemenu-action';

test.describe('Module Menu', () => {
  test.beforeEach(async ({ page, backend }) => {
    await page.goto('module/web/layout');
    await backend.sidebar.expand();

    // The collapsed state is persisted per user, restore it in case a previous test left the group collapsed
    const contentToggle = page.locator(contentToggleSelector);
    if (await contentToggle.getAttribute('aria-expanded') === 'false') {
      await contentToggle.click();
      await expect(page.locator(contentGroupContainerSelector)).not.toHaveClass(/collapsing/);
    }
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

  test('double-clicking a module menu group keeps its state in sync', async ({ page }) => {
    const contentToggle = page.locator(contentToggleSelector);
    const firstSubItem = page.locator(contentSubItemsSelector).first();

    await expect(firstSubItem).toBeVisible();

    await contentToggle.dblclick();
    await expect(firstSubItem).not.toBeVisible();
    await expect(contentToggle).toHaveAttribute('aria-expanded', 'false');

    await contentToggle.click();
    await expect(firstSubItem).toBeVisible();
    await expect(contentToggle).toHaveAttribute('aria-expanded', 'true');
  });

  for (const key of ['ArrowRight', 'Enter']) {
    test(`pressing ${key} while a module menu group collapses keeps the focus on the group`, async ({ page }) => {
      const contentToggle = page.locator(contentToggleSelector);
      const firstSubItem = page.locator(contentSubItemsSelector).first();

      await expect(firstSubItem).toBeVisible();

      // Click and key press in one task, so the key press always hits the running collapse transition
      await contentToggle.evaluate((element: HTMLElement, key: string) => {
        element.focus();
        element.click();
        element.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
      }, key);
      await expect(firstSubItem).not.toBeVisible();
      await expect(contentToggle).toHaveAttribute('aria-expanded', 'false');
      await expect(contentToggle).toBeFocused();

      await contentToggle.click();
      await expect(firstSubItem).toBeVisible();
    });
  }

  test('pressing ArrowRight while a module menu group expands moves the focus into the group', async ({ page }) => {
    const contentToggle = page.locator(contentToggleSelector);
    const firstSubItem = page.locator(contentSubItemsSelector).first();

    await contentToggle.click();
    await expect(firstSubItem).not.toBeVisible();

    // Click and key press in one task, so the key press always hits the running expand transition
    await contentToggle.evaluate((element: HTMLElement) => {
      element.focus();
      element.click();
      element.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true, cancelable: true }));
    });
    await expect(firstSubItem).toBeVisible();
    await expect(contentToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(firstSubItem).toBeFocused();
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
