import { expect, Locator, Page } from '@playwright/test';

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
    const triggerButton = this.container.getByRole('button', { name: triggerName });
    await expect(triggerButton).toBeVisible();
    await triggerButton.click();

    // Wait for dropdown menu to be visible
    const dropdownMenu = this.container.locator('.dropdown-menu.show');
    await expect(dropdownMenu).toBeVisible();

    // Select dropdown item using title attribute for precise matching
    // Use force:true since we've verified visibility and the element can detach during navigation
    const optionButton = dropdownMenu.locator(`.dropdown-item[title="${option}"]`);
    await expect(optionButton).toBeVisible();
    await optionButton.click({ force: true });

    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();
  }

  async selectItemInDropDownByIndex(triggerName: string | RegExp, index: number = 0): Promise<void> {
    // Open dropdown
    const triggerButton = this.container.getByRole('button', { name: triggerName });
    await expect(triggerButton).toBeVisible();
    await triggerButton.click();

    // Wait for dropdown menu to be visible
    const dropdownMenu = this.container.locator('.dropdown-menu.show');
    await expect(dropdownMenu).toBeVisible();

    // Select dropdown item
    const optionButton = dropdownMenu.locator('.dropdown-item').nth(index);
    await expect(optionButton).toBeVisible();
    await optionButton.click({ force: true });

    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();
  }

  async countItemsInDropDown(triggerName: string | RegExp): Promise<number> {
    // Open dropdown
    const triggerButton = this.container.getByRole('button', { name: triggerName });
    await expect(triggerButton).toBeVisible();
    await triggerButton.click();

    // Count dropdown items
    const optionButtons = this.container.locator('.dropdown-menu.show .dropdown-item');
    const count = await optionButtons.count();

    // Close dropdown again to be in a stable state
    await triggerButton.click();
    // Wait for the trigger button to be stable/enabled again
    await expect(triggerButton).toBeEnabled();

    return count;
  }
}
