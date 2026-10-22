import { test, expect } from '../../fixtures/setup-fixtures';
import type { Page } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

const filterInputSelector = '#typo3-pagetree-toolbar .search-input';
const treeNodesListSelector = '#typo3-pagetree-tree .nodes-list';

test.describe('Page tree selection across a filter', () => {
  test.beforeEach(async ({ page, backend }: { page: Page; backend: BackendPage }) => {
    await backend.gotoModule('records');
    await backend.pageTree.open('Root');
    const rootNode = page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="1"]');
    await expect(rootNode).toBeVisible();
    if (await rootNode.locator('[identifier="actions-chevron-end"]').count() > 0) {
      await rootNode.locator('.node-toggle').click();
      await backend.pageTree.isReady();
    }
  });

  // The filter input is debounced, so wait for the tree to report the filter
  function waitForTreeEvent(page: Page, eventName: string): Promise<void> {
    return page.evaluate((name) => new Promise<void>((resolve) => {
      document.querySelector('typo3-backend-navigation-component-pagetree-tree')
        .addEventListener(name, () => resolve(), { once: true });
    }), eventName);
  }

  async function selectFilteredPage(page: Page, backend: BackendPage, filter: string, title: string): Promise<void> {
    const treeNodes = page.locator(treeNodesListSelector);
    const filterInput = page.locator(filterInputSelector);

    const filterApplied = waitForTreeEvent(page, 'typo3:tree:filter-applied');
    await filterInput.fill(filter);
    await filterApplied;
    await backend.pageTree.isReady();

    const node = treeNodes.locator('[role="treeitem"]', { hasText: new RegExp(`^\\s*${title}\\s*$`) });
    await expect(node).toHaveCount(1);
    await node.click();
    await expect(node).toHaveClass(/node-selected/);

    const filterReset = waitForTreeEvent(page, 'typo3:tree:filter-reset');
    await filterInput.fill('');
    await filterReset;
    await backend.pageTree.isReady();
  }

  test('keeps a page selected that was visible before filtering', async ({ page, backend }) => {
    await expect(page.locator(treeNodesListSelector).locator('[role="treeitem"]', { hasText: /^\s*Dummy 1-4\s*$/ })).toBeVisible();

    await selectFilteredPage(page, backend, 'Dummy 1-4', 'Dummy 1-4');

    const selectedNodes = page.locator(`${treeNodesListSelector} [role="treeitem"].node-selected`);
    await expect(selectedNodes).toHaveCount(1);
    await expect(selectedNodes).toHaveText(/^\s*Dummy 1-4\s*$/);
  });

  test('moves the selection to another page after the filter was cleared', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    await selectFilteredPage(page, backend, 'Dummy 1-4', 'Dummy 1-4');

    const selectedNodes = treeNodes.locator('[role="treeitem"].node-selected');
    await expect(selectedNodes).toHaveText(/^\s*Dummy 1-4\s*$/);

    await selectedNodes.focus();
    await page.keyboard.press('ArrowUp');
    await page.keyboard.press('Enter');

    await expect(selectedNodes).toHaveCount(1);
    await expect(selectedNodes).toHaveText(/^\s*Dummy 1-3\s*$/);
  });

  test('keeps pages expandable after the filter was cleared', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    await selectFilteredPage(page, backend, 'Dummy 1-4', 'Dummy 1-4');

    const parentNode = treeNodes.locator('[role="treeitem"]', { hasText: /^\s*Dummy 1-4\s*$/ });
    await expect(treeNodes).not.toContainText('Dummy 1-4-5');
    await parentNode.locator('.node-toggle').click();
    await expect(treeNodes).toContainText('Dummy 1-4-5');
    await parentNode.locator('.node-toggle').click();
    await expect(treeNodes).not.toContainText('Dummy 1-4-5');
  });

  test('keeps a page selected that was not visible before filtering', async ({ page, backend }) => {
    await expect(page.locator(treeNodesListSelector)).not.toContainText('Dummy 1-4-5');

    await selectFilteredPage(page, backend, 'Dummy 1-4-5', 'Dummy 1-4-5');

    const selectedNodes = page.locator(`${treeNodesListSelector} [role="treeitem"].node-selected`);
    await expect(selectedNodes).toHaveCount(1);
    await expect(selectedNodes).toHaveText(/^\s*Dummy 1-4-5\s*$/);
    await expect(page.locator(treeNodesListSelector).locator('[role="treeitem"]', { hasText: /^\s*Dummy 1-4\s*$/ })).toBeVisible();
  });

  test('expands the collapsed branch of a page selected while filtering', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    const parentNode = treeNodes.locator('[role="treeitem"]', { hasText: /^\s*Dummy 1-4\s*$/ });

    // Load the children of the branch and collapse it again
    if (await parentNode.locator('[identifier="actions-chevron-end"]').count() > 0) {
      await parentNode.locator('.node-toggle').click();
      await backend.pageTree.isReady();
    }
    await expect(treeNodes).toContainText('Dummy 1-4-5');
    await parentNode.locator('.node-toggle').click();
    await expect(treeNodes).not.toContainText('Dummy 1-4-5');

    await selectFilteredPage(page, backend, 'Dummy 1-4-5', 'Dummy 1-4-5');

    const selectedNodes = treeNodes.locator('[role="treeitem"].node-selected');
    await expect(selectedNodes).toHaveCount(1);
    await expect(selectedNodes).toHaveText(/^\s*Dummy 1-4-5\s*$/);
  });

  test('scrolls a page selected while filtering into view', async ({ page, backend }) => {
    const treeNodes = page.locator(treeNodesListSelector);
    const styleguideNode = treeNodes.locator('[role="treeitem"][data-id="51"]');
    if (await styleguideNode.locator('[identifier="actions-chevron-end"]').count() > 0) {
      await styleguideNode.locator('.node-toggle').click();
      await backend.pageTree.isReady();
    }
    await expect(treeNodes.locator('[role="treeitem"]', { hasText: /^\s*valuesdefault\s*$/ })).not.toBeInViewport();

    await selectFilteredPage(page, backend, 'valuesdefault', 'valuesdefault');

    const selectedNodes = treeNodes.locator('[role="treeitem"].node-selected');
    await expect(selectedNodes).toHaveCount(1);
    await expect(selectedNodes).toHaveText(/^\s*valuesdefault\s*$/);
    await expect(selectedNodes).toBeInViewport();
  });
});
