import { Page, expect, Locator } from '@playwright/test';

export class FileTree {
  readonly container: Locator;
  readonly toolbar: Locator;
  readonly root: Locator;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.container = this.page.locator('#typo3-filestoragetree');
    this.toolbar = this.container.locator('#filestoragetree-toolbar');
    this.root = this.container.locator('typo3-backend-navigation-component-filestorage-tree');
  }

  /**
   * Wait for the file tree to be loaded, that means:
   * - No tree loading spinner overlay
   */
  async isReady() {
    await expect(this.container.locator('.nodes-loader-inner').last()).not.toBeAttached();
  }

  /**
   * Reload the file tree and wait for it
   * to be fully reloaded and ready
   */
  async refresh() {
    await this.page.dispatchEvent('body', 'typo3:filestoragetree:refresh');
    await this.isReady();
  }

  /**
   * Open the given hierarchical path in the file tree and click the last folder.
   *
   * Example to open "fileadmin -> styleguide" folder:
   * [
   *    'fileadmin',
   *    'styleguide',
   * ]
   *
   * @param folders Array of folders to open in tree
   * @return The last folder in the array
   */
  async open(...folders: Array<string>): Promise<Locator> {
    await this.isReady();

    let level = 0;

    for (const folder of folders) {
      level++;

      // Consider only folder on the current level to avoid naming conflicts.
      const element = this.container.locator(`[aria-level="${level}"]`, {
        has: this.page.locator('.node-contentlabel', { hasText: new RegExp(`^${folder}$`) })
      });

      const targetElement = element.first();

      // Wait for the element to be visible in the DOM
      await expect(targetElement).toBeAttached();

      // Check if this node needs to be expanded (not the last folder in path)
      if (folder !== folders[folders.length - 1]) {
        const toggleIcon = targetElement.locator('.node-toggle [identifier="actions-chevron-end"]');
        const isCollapsed = await toggleIcon.count() === 1;

        if (isCollapsed) {
          await expect(targetElement.locator('.node-toggle')).toBeVisible();
          await targetElement.locator('.node-toggle').click();
          await this.isReady();
        }
      } else {
        // This is the last folder - click to select it
        const contentLabel = targetElement.locator('.node-contentlabel').first();
        await expect(contentLabel).toBeAttached({ timeout: 10000 });
        await contentLabel.click();
        await this.isReady();

        await expect(targetElement).toHaveClass(/node-selected/);

        // Return a locator for the specific element by its unique data-id
        const dataId = await targetElement.getAttribute('data-id');
        if (!dataId) {
          throw new Error(`Could not get data-id for page "${folder}"`);
        }
        return this.container.locator(`[data-id="${dataId}"]`);
      }
    }

    // This should never be reached if pages array is not empty
    throw new Error('No folders provided to open()');
  }
}
