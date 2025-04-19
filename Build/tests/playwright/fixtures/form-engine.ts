import {Page, expect, Locator, FrameLocator} from '@playwright/test';

export class FormEngine {
  private readonly page: Page;

  readonly contentFrame: FrameLocator;
  readonly container: Locator;
  readonly saveButton: Locator;
  readonly closeButton: Locator;

  constructor(page: Page) {
    this.page = page;
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
    this.container = this.contentFrame.locator('#EditDocumentController');
    this.saveButton = this.contentFrame.locator('[name="_savedok"]');
    this.closeButton = this.contentFrame.locator('.t3js-editform-close');
  }

  /**
   * Click the save button and wait for form engine to be ready
   */
  async save() {
    await expect(this.saveButton).toBeEnabled();

    // Wait for the save request to complete (navigation happens)
    const loaded = this.formEngineLoaded();

    this.saveButton.click();
    await loaded;
  }

  /**
   * Close the form engine
   */
  async close() {
    let formEngineLoaded = this.formEngineLoaded();
    await this.closeButton.click();
    await formEngineLoaded;

    await expect(this.container).not.toBeAttached();
  }

  async formEngineLoaded() {
    return this.page.waitForFunction(() => {
      return new Promise((resolve) => {
        document.addEventListener('typo3-module-loaded', resolve, {once: true});
      });
    });
  }
}
