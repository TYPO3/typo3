import { Page, expect, Locator } from '@playwright/test';

export class PageTree {
  readonly toolbar: Locator;
  readonly tree: Locator;
  readonly root: Locator;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.toolbar = this.page.locator('#typo3-pagetree-toolbar');
    this.tree = this.page.locator('#typo3-pagetree-tree');
    this.root = this.tree.locator('[identifier="apps-pagetree-root"]');
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
    await this.toolbar.locator(`[data-node-type="${nodeType}"]`).dragTo(targetElement);
  }

  /**
   * Wait for the page tree to be loaded, that means:
   * - No tree loading spinner overlay
   * - typo3-backend-progress-bar has finished
   * - All icons are loaded
   */
  async isReady() {
    // For some reason sometimes there are multiple loaders, just wait for the last to disappear
    await expect(this.tree.locator('.nodes-loader-inner').last()).not.toBeAttached();
    await expect(this.tree.locator('[identifier="spinner-circle"]')).not.toBeAttached();
    await expect(this.page.locator('typo3-backend-progress-bar')).not.toBeVisible();
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
    // Wait for the edit field to appear after drag and drop
    await expect(nodeEditLocator).toBeAttached();
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
    if (!box) {
      throw new Error('Unable to get bounding box for the page to delete');
    }
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
  async open(...pages: Array<string>): Promise<Locator> {
    await this.isReady();

    let level = 1;

    for (const page of pages) {
      level++;

      // Consider only pages on the current level to avoid naming conflicts.
      const element = this.tree.locator(`[aria-level="${level}"]`, {
        has: this.page.locator('.node-contentlabel', { hasText: new RegExp(`^${page}$`) })
      });

      const targetElement = element.first();

      // Wait for the element to be visible in the DOM
      await expect(targetElement).toBeAttached();

      // Check if this node needs to be expanded (not the last page in path)
      if (page !== pages[pages.length - 1]) {
        const toggleIcon = targetElement.locator('.node-toggle [identifier="actions-chevron-end"]');
        const isCollapsed = await toggleIcon.count() === 1;

        if (isCollapsed) {
          // Set up event listener before clicking to expand
          const expandEventPromise = this.page.evaluate(() => {
            return new Promise<void>((resolve) => {
              const tree = document.querySelector('typo3-backend-navigation-component-pagetree-tree');
              if (tree) {
                tree.addEventListener('typo3:tree:expand-toggle', () => resolve(), { once: true });
              } else {
                resolve();
              }
            });
          });

          await targetElement.locator('.node-toggle').click();
          await expandEventPromise;
          await this.isReady();
        }
      } else {
        // This is the last page - click to select it
        const contentLabel = targetElement.locator('.node-contentlabel').first();
        await expect(contentLabel).toBeAttached({ timeout: 10000 });

        // Set up event listener before clicking to select
        const selectEventPromise = this.page.evaluate(() => {
          return new Promise<void>((resolve) => {
            const tree = document.querySelector('typo3-backend-navigation-component-pagetree-tree');
            if (tree) {
              tree.addEventListener('typo3:tree:node-selected', () => resolve(), { once: true });
            } else {
              resolve();
            }
          });
        });

        await contentLabel.click();
        await selectEventPromise;
        await this.isReady();

        await expect(targetElement).toHaveClass(/node-selected/);

        // Return a locator for the specific element by its unique data-id
        // This ensures dragDeletePage checks the exact element, not another with same name
        const dataId = await targetElement.getAttribute('data-id');
        if (!dataId) {
          throw new Error(`Could not get data-id for page "${page}"`);
        }
        return this.tree.locator(`[data-id="${dataId}"]`);
      }
    }

    // This should never be reached if pages array is not empty
    throw new Error('No pages provided to open()');
  }
}
