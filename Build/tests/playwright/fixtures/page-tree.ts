import { Page, expect, Locator } from '@playwright/test';

export class PageTree {
  private readonly page: Page;

  readonly container: Locator;
  readonly toolbar: Locator;
  readonly root: Locator;

  constructor(page: Page) {
    this.page = page;
    this.container = this.page.locator('#typo3-pagetree');
    this.toolbar = this.container.locator('#typo3-pagetree-toolbar');
    this.root = this.container.locator('[identifier="apps-pagetree-root"]');
  }

  /**
   * Create a new page using drag and drop
   *
   * @param targetElement The element to drag the new node on
   * @param title The page title to be used
   * @param nodeType Derived from data-node-type, defaults to 1 (Standard)
   */
  async create(targetElement: Locator, title, nodeType = 1) {
    await this.dragNewPageTo(targetElement, nodeType);
    await this.fill(title);
  }

  /**
   * Drag new node type to tree
   *
   * @param targetElement The element to drag the new node on
   * @param nodeType Derived from data-node-type, defaults to 1 (Standard)
   */
  async dragNewPageTo(targetElement: Locator, nodeType = 1) {
    await this.toolbar.locator(`[data-node-type="${nodeType}"]`).dragTo(targetElement)
  }

  /**
   * Wait for the page tree to be loaded, that means:
   * - No tree loading spinner overlay
   * - NProgress has finished
   * - All icons are loaded
   */
  async isReady() {
    await expect(this.container.locator('.nodes-loader-inner')).not.toBeAttached();
    await expect(this.container.locator('[identifier="spinner-circle"]')).not.toBeAttached();
    await expect(this.page.locator('.nprogress-busy')).not.toBeVisible();
  }

  /**
   * Reload the page tree and wait for it
   * to be fully reloaded and ready
   */
  async refresh() {
    await this.page.dispatchEvent('body', 'typo3:pagetree:refresh');
    await this.isReady();
  }

  /**
   * Fill in the node currently in editing mode
   *
   * @param title
   */
  async fill(title: string) {
    // Intercept "page create" request
    const newPageProcessedResponse = this.page.waitForResponse(response =>
      response.url().includes('/typo3/ajax/record/process') && response.status() === 200
    );

    const nodeEditLocator = this.page.locator('.node-edit');
    await nodeEditLocator.fill(title);
    await this.page.keyboard.press('Enter');

    await newPageProcessedResponse;
    await expect(nodeEditLocator).not.toBeAttached();
  }

  /**
   * Delete a page using drag&drop
   *
   * @param pageToDelete The element to be deleted
   */
  async dragDeletePage(pageToDelete: Locator) {
    const box = await pageToDelete.boundingBox();
    if (!box) throw new Error('Unable to get bounding box for the page to delete');
    await pageToDelete.dragTo(pageToDelete, {
      sourcePosition: { x: 10, y: box.height / 2 },
      targetPosition: { x: box.width - 10, y: box.height / 2 }
    });
    await this.isReady();
    await pageToDelete.page().locator('typo3-backend-modal button[name="delete"]').click();
    await expect(pageToDelete).not.toBeAttached();
  }

  /**
   * Open the given hierarchical path in the pagetree and click the last page.
   *
   * Example to open "styleguide -> elements basic" page:
   * [
   *    'styleguide TCA demo',
   *    'elements basic',
   * ]
   *
   * @param pages Array of pages to open in tree
   * @return The last page in the array
   */
  async open(...pages: Array<string>) {
    let resultPage: Locator;
    let level = 1;
    let element = this.container.locator(`[aria-level="${level}"][data-tree-id*="0"]`);

    for (const page of pages) {
      level++;

      // Consider only pages on the current level to avoid naming conflicts.
      // Does not work when 2 pages have the same name and are on the same tree level
      element = this.container.locator(`[aria-level="${level}"]`, { hasText: page });

      const isCollapsed = await element.locator('.node-toggle [identifier="actions-chevron-right"]').count() === 1;
      if (isCollapsed) {
        await element.locator('.node-toggle').click();
      }

      if (page === pages[pages.length - 1]) {
        await element.locator('.node-contentlabel').click();
        await this.isReady();
        await expect(element).toHaveClass(/node-selected/);
        resultPage = element;
      }
    }
    return resultPage;
  }
}
