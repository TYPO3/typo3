import { Page, Locator, expect } from '@playwright/test';

export class Sidebar {
  readonly scaffold: Locator;
  readonly element: Locator;
  readonly toggle: Locator;
  private readonly page: Page;

  private readonly expandedClass = 'scaffold-sidebar-expanded';
  private readonly flyoutClass = 'scaffold-sidebar-flyout';

  constructor(page: Page) {
    this.page = page;
    this.scaffold = this.page.locator('.t3js-scaffold');
    this.element = this.page.locator('.scaffold-sidebar');
    this.toggle = this.page.locator('typo3-backend-sidebar-toggle');
  }

  async isExpanded(): Promise<boolean> {
    return this.scaffold.evaluate((el, cls) => el.classList.contains(cls), this.expandedClass);
  }

  async isFlyout(): Promise<boolean> {
    return this.scaffold.evaluate((el, cls) => el.classList.contains(cls), this.flyoutClass);
  }

  async expand(): Promise<void> {
    if (!await this.isExpanded()) {
      await this.toggle.click();
      await expect(this.scaffold).toHaveClass(new RegExp(this.expandedClass));
    }
  }

  async collapse(): Promise<void> {
    if (await this.isExpanded()) {
      await this.toggle.click();
      await expect(this.scaffold).not.toHaveClass(new RegExp(this.expandedClass));
    }
  }
}
