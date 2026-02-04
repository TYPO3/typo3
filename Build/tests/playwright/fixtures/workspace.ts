import { Page, Locator, expect } from '@playwright/test';

/**
 * Helper for workspace-related elements.
 * All elements are in the main frame (not the content iframe).
 */
export class Workspace {
  /** Top indicator element (uses Shadow DOM) */
  readonly indicator: Locator;
  /** Sidebar selector element (no Shadow DOM - light DOM) */
  readonly selector: Locator;
  readonly dropdownMenu: Locator;
  readonly dropdownTrigger: Locator;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.indicator = this.page.locator('typo3-backend-workspace-top-indicator');
    this.selector = this.page.locator('typo3-backend-workspace-selector');
    this.dropdownMenu = this.selector.locator('.dropdown-menu');
    this.dropdownTrigger = this.selector.locator('button.workspace-selector');
  }

  /**
   * Wait for workspace selector to be ready
   */
  async waitForReady(): Promise<void> {
    await expect(this.selector).toBeAttached({ timeout: 10000 });
  }

  /**
   * Get the workspace name from the top indicator (uses Shadow DOM)
   */
  async getIndicatorWorkspaceName(): Promise<string | null> {
    return this.indicator.evaluate((el: Element) => {
      return el.shadowRoot?.querySelector('.workspace-indicator-badge')?.textContent?.trim() ?? null;
    });
  }

  /**
   * Get the workspace name from the selector trigger
   */
  async getSelectorWorkspaceName(): Promise<string | null> {
    return this.selector.locator('.workspace-selector-name').textContent();
  }

  /**
   * Get the active workspace name from the dropdown
   */
  async getActiveWorkspaceName(): Promise<string | null> {
    return this.selector.locator('.dropdown-item.active').textContent();
  }

  /**
   * Check if dropdown is currently open
   */
  async isDropdownOpen(): Promise<boolean> {
    return this.dropdownMenu.isVisible();
  }

  /**
   * Open the workspace selector dropdown
   */
  async openDropdown(): Promise<void> {
    if (!await this.isDropdownOpen()) {
      await this.dropdownTrigger.click();
      await expect(this.dropdownMenu).toBeVisible();
    }
  }

  /**
   * Close the workspace selector dropdown
   */
  async closeDropdown(): Promise<void> {
    if (await this.isDropdownOpen()) {
      await this.dropdownTrigger.click();
      await expect(this.dropdownMenu).not.toBeVisible();
    }
  }

  /**
   * Switch to a workspace by name
   */
  async switchTo(workspaceName: string): Promise<void> {
    await this.openDropdown();
    await this.selector.locator('.dropdown-item', { hasText: workspaceName }).click();
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Ensure we are in LIVE workspace, switch if needed
   */
  async ensureLiveWorkspace(): Promise<void> {
    await this.waitForReady();
    const currentWorkspace = await this.getSelectorWorkspaceName();
    if (!currentWorkspace?.includes('LIVE')) {
      await this.switchTo('LIVE');
    }
  }

  /**
   * Verify the indicator shows the expected workspace name
   */
  async expectIndicatorToShow(workspaceName: string): Promise<void> {
    await expect(this.indicator).toBeAttached();
    const indicatorText = await this.getIndicatorWorkspaceName();
    expect(indicatorText).toContain(workspaceName);
  }

  /**
   * Verify the selector shows the expected workspace name
   */
  async expectSelectorToShow(workspaceName: string): Promise<void> {
    await expect(this.selector).toBeAttached();
    const selectorText = await this.getSelectorWorkspaceName();
    expect(selectorText?.trim()).toContain(workspaceName);
  }

  /**
   * Open dropdown and verify the selected item
   */
  async expectDropdownSelectionToBe(workspaceName: string): Promise<void> {
    await this.openDropdown();
    const activeText = await this.getActiveWorkspaceName();
    expect(activeText?.trim()).toContain(workspaceName);
  }

  /**
   * Verify both indicator and selector show the expected workspace
   */
  async expectWorkspaceToBe(workspaceName: string): Promise<void> {
    await this.expectIndicatorToShow(workspaceName);
    await this.expectSelectorToShow(workspaceName);
  }
}
