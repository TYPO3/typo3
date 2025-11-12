import {expect, Locator, Page} from '@playwright/test';

export class DocHeader {
  private readonly page: Page;
  private container: Locator;

  constructor(page: Page) {
    this.page = page;
    this.container = this.page.frameLocator('#typo3-contentIframe').locator('.t3js-module-docheader-buttons');
  }

  setContainerLocator(locator: Locator): void {
    this.container = locator;
  }

  async selectInDropDown(triggerName: string, option: string): Promise<void> {
    // Open dropdown
    let triggerButton = this.container.getByRole('button', {name: triggerName});
    await expect(triggerButton).toBeVisible()
    await triggerButton.click();

    // Select dropdown item
    let optionButton = this.container.locator('.dropdown-menu.show .dropdown-item', {hasText: option});
    await expect(optionButton).toBeAttached();
    await optionButton.click();

    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();
  }

  async selectItemInDropDownByIndex(triggerName: string | RegExp, index: number = 0): Promise<void> {
    // Open dropdown
    let triggerButton = this.container.getByRole('button', {name: triggerName});
    await expect(triggerButton).toBeVisible()
    await triggerButton.click();

    // Select dropdown item
    let optionButton = this.container.locator('.dropdown-menu.show .dropdown-item').nth(index);
    await expect(optionButton).toBeAttached();
    await optionButton.click();

    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();
  }

  async countItemsInDropDown(triggerName: string | RegExp): Promise<number> {
    // Open dropdown
    let triggerButton = this.container.getByRole('button', {name: triggerName});
    await expect(triggerButton).toBeVisible();
    await triggerButton.click();

    // Count dropdown items
    let optionButtons = this.container.locator('.dropdown-menu.show .dropdown-item');
    let count = await optionButtons.count();

    // Close dropdown again to be in a stable state
    await triggerButton.click();
    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();

    return count;
  }
}
