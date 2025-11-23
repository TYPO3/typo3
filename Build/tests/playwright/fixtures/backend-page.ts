import { Page, FrameLocator, expect } from '@playwright/test';
import { PageTree } from './page-tree';
import { FormEngine } from './form-engine';
import { DocHeader } from './doc-header';
import { Modal } from './modal';

export class BackendPage {
  readonly contentFrame: FrameLocator;
  readonly pageTree: PageTree;
  readonly formEngine: FormEngine;
  readonly docHeader: DocHeader;
  readonly modal: Modal;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
    this.pageTree = new PageTree(page);
    this.formEngine = new FormEngine(page);
    this.docHeader = new DocHeader(page);
    this.modal = new Modal(page);
  }

  async gotoModule(identifier: string) {
    await this.page.goto('module/web/layout');
    const moduleLink = this.page.locator(`a[data-modulemenu-identifier="${identifier}"]`);
    const moduleLoaded = this.moduleLoaded(identifier);
    moduleLink.click();
    await moduleLoaded;

    await expect(moduleLink).toHaveClass(/modulemenu-action-active/);
  }

  async moduleLoaded(identifier: string) {
    return this.page.waitForFunction(() => {
      return new Promise((resolve) => {
        // Listen for module loaded event and verify it's the right module
        document.addEventListener('typo3-module-loaded', () => {
          resolve(true);
        }, { once: true });
      });
    }, identifier);
  }

  async waitForModuleResponse(urlPattern?: string | RegExp): Promise<void> {
    await this.page.waitForResponse(response => {
      if (urlPattern) {
        const urlMatches = typeof urlPattern === 'string'
          ? response.url().includes(urlPattern)
          : urlPattern.test(response.url());
        return urlMatches && response.status() === 200;
      }

      return (response.url().includes('/typo3/module/') || response.url().includes('/typo3/web/'))
        && response.status() === 200;
    });
  }

  getUnixTimestamp(): number {
    return Math.floor(Date.now() / 1000);
  }
}
