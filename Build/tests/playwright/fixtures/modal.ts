import { Page, Locator, expect } from '@playwright/test';

export class Modal {
  readonly frameSelector = 'typo3-backend-modal iframe[name="modal_frame"]';
  readonly element: Locator;
  readonly header: Locator;
  readonly footer: Locator;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.element = this.page.locator('typo3-backend-modal');
    this.header = this.element.locator('.modal-header');
    this.footer = this.element.locator('.modal-footer');
  }

  /**
   * Open the modal and get the body. For modals of type iframe
   * it returns the actual iframe content.
   */
  async open(locator: Locator): Promise<Locator> {
    await locator.click();

    const iframeLocator = this.page.locator(this.frameSelector);
    if (await iframeLocator.count() > 0) {
      return this.page.frameLocator(this.frameSelector).locator('html');
    }

    return this.page.locator('typo3-backend-modal .t3js-modal-body');
  }

  /**
   * Click a button in the modal footer
   *
   * @param name The buttons name (not text)
   */
  async click(name: string) {
    await expect(this.footer.locator(`button[name="${name}"]`)).toBeEnabled();
    await this.footer.locator(`button[name="${name}"]`).click();
  }
}
