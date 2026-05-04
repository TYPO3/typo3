import { test, expect } from '../../fixtures/setup-fixtures';
import type { Page } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

test.describe('Page tree keyboard navigation', () => {
  test.beforeEach(async ({ page, backend }: { page: Page; backend: BackendPage }) => {
    await backend.gotoModule('records');
    await backend.pageTree.open('Root');
    const rootNode = page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="1"]');
    await expect(rootNode).toBeVisible();
    // pageTree.open() selects but does not expand the last node. Expand
    // Root so its children are part of keyboard navigation.
    if (await rootNode.locator('[identifier="actions-chevron-end"]').count() > 0) {
      await rootNode.locator('.node-toggle').click();
      await backend.pageTree.isReady();
      await rootNode.click();
    }
  });

  test('Down focuses the next page, Enter opens it', async ({ page, backend }) => {
    const selectedNode = page.locator('#typo3-pagetree-tree [role="treeitem"].node-selected');
    await expect(selectedNode).toBeVisible();
    await selectedNode.press('ArrowDown');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-2');

    await page.keyboard.press('Enter');
    await expect(backend.contentFrame.locator('body')).toContainText('Dummy 1-2');
  });

  test('Down then Up moves focus through siblings', async ({ page }) => {
    const selectedNode = page.locator('#typo3-pagetree-tree [role="treeitem"].node-selected');
    await expect(selectedNode).toBeVisible();
    await selectedNode.press('ArrowDown');
    await page.keyboard.press('ArrowDown');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-3');

    await page.keyboard.press('ArrowUp');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-2');
  });

  test('Right expands a subtree and steps into the first child', async ({ page }) => {
    const selectedNode = page.locator('#typo3-pagetree-tree [role="treeitem"].node-selected');
    await expect(selectedNode).toBeVisible();
    await selectedNode.press('ArrowDown');
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('ArrowDown');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-4');

    // First Right expands; focus stays on parent
    await page.keyboard.press('ArrowRight');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-4');
    await expect(page.locator('#typo3-pagetree-tree')).toContainText('Dummy 1-4-5');

    // Second Right moves into the first child
    await page.keyboard.press('ArrowRight');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-4-5');

    // Another Right on a leaf is a no-op
    await page.keyboard.press('ArrowRight');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-4-5');

    // Down moves to the next sibling
    await page.keyboard.press('ArrowDown');
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 6');
  });

  test('Left collapses the current subtree, then steps to the parent', async ({ page }) => {
    const selectedNode = page.locator('#typo3-pagetree-tree [role="treeitem"].node-selected');
    await expect(selectedNode).toBeVisible();
    await expect(selectedNode).toHaveText(/Root/);
    await expect(page.locator('#typo3-pagetree-tree')).toContainText('Dummy 1-2');

    // First Left collapses the current node
    await selectedNode.press('ArrowLeft');
    await expect.poll(() => focusedNodeText(page)).toBe('Root');
    await expect(page.locator('#typo3-pagetree-tree')).not.toContainText('Dummy 1-2');

    // Second Left moves up to the parent
    await page.keyboard.press('ArrowLeft');
    await expect.poll(() => focusedNodeText(page)).toBe('New TYPO3 site');
    await expect(page.locator('#typo3-pagetree-tree')).toContainText('Root');
    await expect(page.locator('#typo3-pagetree-tree')).toContainText('styleguide TCA demo');
  });

  test('Home jumps focus back to the root', async ({ page }) => {
    const selectedNode = page.locator('#typo3-pagetree-tree [role="treeitem"].node-selected');
    await expect(selectedNode).toBeVisible();
    await selectedNode.press('ArrowDown');
    for (let i = 0; i < 14; i++) {
      await page.keyboard.press('ArrowDown');
    }
    await expect.poll(() => focusedNodeText(page)).toBe('Dummy 1-21');

    await page.keyboard.press('Home');
    await expect.poll(() => focusedNodeText(page)).toBe('New TYPO3 site');
  });
});

async function focusedNodeText(page: Page): Promise<string> {
  return page.evaluate(() => {
    const focused = document.querySelector('#typo3-pagetree-tree [role="treeitem"]:focus');
    return focused?.textContent?.trim() ?? '';
  });
}
