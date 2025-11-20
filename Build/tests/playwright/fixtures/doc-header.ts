import {Page, expect, Locator} from '@playwright/test';

export class DocHeader {
  private readonly page: Page;
  readonly container: Locator;

  constructor(page: Page) {
    this.page = page;
    this.container = this.page.frameLocator('#typo3-contentIframe').locator('.t3js-module-docheader-buttons');
  }

  async selectInDropDown(triggerName: string, option: string): Promise<void>
  {
    // Open dropdown
    let triggerButton = this.container.getByRole('button', { name: triggerName });
    await expect(triggerButton).toBeVisible()
    await triggerButton.click();

    // Select dropdown item
    let optionButton = this.container.locator('.dropdown-menu.show .dropdown-item', { hasText: option });
    await expect(optionButton).toBeAttached();
    await optionButton.click();

    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();
  }
}
