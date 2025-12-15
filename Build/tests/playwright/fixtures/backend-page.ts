import { Page, FrameLocator, expect, Locator } from '@playwright/test';
import { PageTree } from './page-tree';
import { FormEngine } from './form-engine';
import { DocHeader } from './doc-header';
import { Modal } from './modal';
import { FileTree } from './file-tree';
import { Sidebar } from './sidebar';

export enum ViewportSize {
  Desktop = 'desktop',
  Tablet = 'tablet',
  Mobile = 'mobile',
}

const viewportDimensions = {
  [ViewportSize.Desktop]: { width: 1280, height: 960 },
  [ViewportSize.Tablet]: { width: 768, height: 1024 },
  [ViewportSize.Mobile]: { width: 375, height: 667 },
};

export class BackendPage {
  readonly moduleNavigation: Locator;
  readonly contentFrame: FrameLocator;
  readonly pageTree: PageTree;
  readonly fileTree: FileTree;
  readonly formEngine: FormEngine;
  readonly docHeader: DocHeader;
  readonly modal: Modal;
  readonly sidebar: Sidebar;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.moduleNavigation = this.page.locator('#modulemenu');
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
    this.pageTree = new PageTree(page);
    this.formEngine = new FormEngine(page);
    this.docHeader = new DocHeader(page);
    this.modal = new Modal(page);
    this.fileTree = new FileTree(page);
    this.sidebar = new Sidebar(page);
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

  async setViewportSize(size: ViewportSize): Promise<void> {
    await this.page.setViewportSize(viewportDimensions[size]);
  }
}
