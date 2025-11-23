import { Page, Locator, expect } from '@playwright/test';

export interface ModalClickOptions {
  name?: string;
  text?: string;
}

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
   * Click a button in the modal footer (we have some buttons with name and some without)
   *
   * @param modalClickOptions Options to identify the button either by name or text
   */
  async click(modalClickOptions: ModalClickOptions) {
    const locator = modalClickOptions.name
      ? `button[name="${modalClickOptions.name}"]`
      : `button:has-text("${modalClickOptions.text}")`;

    await expect(this.footer.locator(locator)).toBeEnabled();
    await this.footer.locator(locator).click();
  }
}
