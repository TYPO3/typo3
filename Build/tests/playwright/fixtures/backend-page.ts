import {Page, FrameLocator, expect} from '@playwright/test';
import {PageTree} from "./page-tree";

export class BackendPage {
  private readonly page: Page;

  readonly contentFrame: FrameLocator;
  readonly pageTree: PageTree;

  constructor(page: Page) {
    this.page = page;
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
    this.pageTree = new PageTree(page);
  }

  async gotoModule(identifier) {
    await this.page.goto('');
    let moduleLink = await this.page.locator(`a[data-modulemenu-identifier="${identifier}"]`);
    await moduleLink.click();

    await expect(moduleLink).toHaveClass(/modulemenu-action-active/);
    await expect(this.page.locator('.nprogress-busy')).not.toBeVisible();
  }
}
