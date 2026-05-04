import { test, expect } from '../../fixtures/setup-fixtures';
import type { Locator } from '@playwright/test';

test.describe('Layout module', () => {
  test('shows headline message when root (pid=0) is selected', async ({ page, backend }) => {
    await backend.gotoModule('web_layout');
    await backend.pageTree.isReady();
    await page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="0"] .node-contentlabel').click();
    await expect(backend.contentFrame.locator('body')).toContainText(
      'Please select a page in the page tree to edit page content.'
    );
  });

  test('switching modules back and forth keeps the selected page', async ({ page, backend }) => {
    const contentBody = backend.contentFrame.locator('body');
    const switchModule = async (identifier: string) => {
      const moduleLoaded = await backend.moduleLoaded(identifier);
      await page.locator(`a[data-modulemenu-identifier="${identifier}"]`).click();
      await moduleLoaded();
    };

    await backend.gotoModule('web_layout');
    await backend.pageTree.open('styleguide TCA demo', 'ctrl common');
    await expect(contentBody).toContainText('ctrl common');

    await switchModule('records');
    await expect(contentBody).toContainText('ctrl common');

    await backend.pageTree.open('styleguide TCA demo', 'ctrl minimal');
    await expect(contentBody).toContainText('ctrl minimal');

    await switchModule('web_layout');
    await expect(contentBody).toContainText('ctrl minimal');
  });

  test('editable page title can be renamed and reverted', async ({ backend }) => {
    const oldPageTitle = 'styleguide TCA demo';
    const newPageTitle = 'styleguide TCA demo page';

    await backend.gotoModule('web_layout');
    await backend.pageTree.open(oldPageTitle);

    const editable = backend.contentFrame.locator('typo3-backend-editable-page-title');
    await expect(editable).toBeVisible();

    await renamePageTitle(editable, newPageTitle);
    await expect.poll(() => editable.evaluate((el: any) => el.pageTitle)).toBe(newPageTitle);

    await renamePageTitle(editable, oldPageTitle);
    await expect.poll(() => editable.evaluate((el: any) => el.pageTitle)).toBe(oldPageTitle);
  });
});

async function renamePageTitle(editable: Locator, title: string): Promise<void> {
  await editable.locator('[data-action="edit"]').click();
  await editable.locator('input').fill(title);
  await editable.locator('[data-action="save"]').click();
}
