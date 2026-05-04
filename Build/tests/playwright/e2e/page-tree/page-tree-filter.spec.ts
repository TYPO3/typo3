import { test, expect } from '../../fixtures/setup-fixtures';
import type { Page } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

const filterInputSelector = '#typo3-pagetree-toolbar .search-input';
const treeNodesListSelector = '#typo3-pagetree-tree .nodes-list';
const secondaryOptionsToggleSelector = '#typo3-pagetree-toolbar .dropdown-toggle';
const reloadButtonSelector = '#typo3-pagetree-toolbar typo3-backend-icon[identifier=actions-refresh]';

test.describe('Page tree filter', () => {
  test.beforeEach(async ({ page, backend }: { page: Page; backend: BackendPage }) => {
    await backend.gotoModule('records');
    await backend.pageTree.open('styleguide TCA demo');
    // pageTree.open() selects but does not expand the last node. Expand
    // styleguide TCA demo so its children are visible for the
    // post-filter-cleared assertions.
    const styleguideNode = page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="51"]');
    if (await styleguideNode.locator('[identifier="actions-chevron-end"]').count() > 0) {
      await styleguideNode.locator('.node-toggle').click();
      await backend.pageTree.isReady();
    }
  });

  test('filtering for "group" narrows the tree to matching nodes and survives reload', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    const filterInput = page.locator(filterInputSelector);

    await filterInput.fill('group');
    await backend.pageTree.isReady();
    // Filter should apply automatically after fill, no Enter press needed.
    // [#91884] this assertion confirms the filter applied (no Enter press).
    await expect(treeNodes.locator('text=inline expandsingle')).not.toBeVisible();

    await expect(treeNodes).toContainText('elements group');
    await expect(treeNodes).toContainText('inline mngroup');
    // [#91883] translated pages must not appear in the filtered tree.
    await expect(treeNodes.locator('text=elements group - language 3')).not.toBeVisible();

    await page.locator(secondaryOptionsToggleSelector).click();
    await page.locator(reloadButtonSelector).click();
    await backend.pageTree.isReady();

    // [#91885] filter must still apply after page tree reload.
    await expect(treeNodes.locator('text=flex')).not.toBeVisible();
    await expect(filterInput).toHaveValue('group');
  });

  test('clearing the filter via Escape reloads the unfiltered tree', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    const filterInput = page.locator(filterInputSelector);

    await filterInput.fill('group');
    await backend.pageTree.isReady();
    await expect(treeNodes.locator('text=inline expandsingle')).not.toBeVisible();
    await expect(treeNodes).toContainText('elements group');
    await expect(treeNodes).toContainText('inline mngroup');

    await filterInput.press('Escape');
    await backend.pageTree.isReady();

    await expect(treeNodes.locator('text=inline expandsingle').first()).toBeVisible();
    await expect(treeNodes).toContainText('elements group');
    await expect(treeNodes).toContainText('inline mngroup');
  });

  test('deleting a filtered page keeps the filter applied after the tree reloads', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    const filterInput = page.locator(filterInputSelector);

    await filterInput.fill('group');
    await backend.pageTree.isReady();
    await expect(treeNodes).toContainText('elements group');
    await expect(treeNodes).toContainText('inline mngroup');

    const targetNode = treeNodes.locator('[role="treeitem"]', { hasText: /^\s*inline mngroup\s*$/ }).first();
    await expect(targetNode).toBeVisible();
    await targetNode.click({ button: 'right' });

    await page.locator('typo3-backend-context-menu button[data-contextmenu-id="root_delete"]').click();

    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await dialog.locator('button[name="delete"]').click();
    await expect(dialog).not.toBeVisible();
    await backend.pageTree.isReady();

    // Filter still active: matching siblings remain, deleted node is gone,
    // non-matching pages stay hidden.
    await expect(treeNodes).toContainText('elements group');
    await expect(treeNodes.locator('text=inline mngroup')).not.toBeVisible();
    await expect(treeNodes.locator('text=inline expandsingle')).not.toBeVisible();
    await expect(treeNodes.locator('text=flex')).not.toBeVisible();
  });
});
