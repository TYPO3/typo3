import { Page, expect, Locator, FrameLocator } from '@playwright/test';

export class FormEngine {
  readonly contentFrame: FrameLocator;
  readonly container: Locator;
  readonly saveButton: Locator;
  readonly closeButton: Locator;
  private readonly page: Page;

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

    const loaded = await this.formEngineLoaded();
    this.saveButton.click();
    await loaded();
  }

  /**
   * Close the form engine
   */
  async close() {
    const loaded = await this.formEngineLoaded();
    this.closeButton.click();
    await loaded();

    await expect(this.container).not.toBeAttached();
  }

  /**
   * Returns a waiter for the next `typo3-module-loaded` event. Awaiting
   * the call installs the listener; the returned waiter resolves when
   * the event fires. Both awaits are needed so the listener is in
   * place before the triggering action runs:
   *
   *   const ready = await formEngine.formEngineLoaded();
   *   await action();
   *   await ready();
   */
  async formEngineLoaded(): Promise<() => Promise<void>> {
    const initial = await this.page.evaluate(() => {
      const w = window as Window & { __typo3ModuleLoadedCounter?: number };
      if (typeof w.__typo3ModuleLoadedCounter !== 'number') {
        w.__typo3ModuleLoadedCounter = 0;
        document.addEventListener('typo3-module-loaded', () => {
          w.__typo3ModuleLoadedCounter = (w.__typo3ModuleLoadedCounter ?? 0) + 1;
        });
      }
      return w.__typo3ModuleLoadedCounter;
    });
    return async () => {
      await this.page.waitForFunction(
        (initial) => {
          const w = window as Window & { __typo3ModuleLoadedCounter?: number };
          return (w.__typo3ModuleLoadedCounter ?? 0) > initial;
        },
        initial,
      );
    };
  }
}
