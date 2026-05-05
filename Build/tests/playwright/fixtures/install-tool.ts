import { test as base, Page, expect, Locator, APIRequestContext } from '@playwright/test';
import config from '../config';

export const INSTALL_TOOL_PASSWORD = 'Temporary Password - 123';
export const FLASH_MESSAGE_SELECTOR = '#alert-container typo3-notification-message';

export type InstallToolPage = 'Maintenance' | 'Settings' | 'Upgrade' | 'Environment';

export interface InstallToolStatus {
  enabled: boolean;
}

export class InstallTool {
  private readonly page: Page;
  private readonly request: APIRequestContext;

  constructor(page: Page, request: APIRequestContext) {
    this.page = page;
    this.request = request;
  }

  async enable(): Promise<void> {
    await this.request.get(`${config.baseUrl}playwright-helper/install-tool/enable`);
  }

  async disable(): Promise<void> {
    await this.request.get(`${config.baseUrl}playwright-helper/install-tool/disable`);
  }

  async getStatus(): Promise<InstallToolStatus> {
    const response = await this.request.get(`${config.baseUrl}playwright-helper/install-tool/status`);
    return await response.json();
  }

  async goto(): Promise<void> {
    await this.page.goto(`${config.baseUrl}?__typo3_install`);
  }

  async login(password: string = INSTALL_TOOL_PASSWORD): Promise<void> {
    await this.page.locator('#t3-install-form-password').fill(password);
    await this.page.getByRole('button', { name: 'Login' }).click();
  }

  async navigateTo(installToolPage: InstallToolPage): Promise<void> {
    await this.page.getByRole('menuitem', { name: installToolPage }).click();
    await expect(this.page.locator('h1')).toContainText(installToolPage);
  }

  getModal(): Locator {
    return this.page.locator('.t3js-modal[open]');
  }

  async openModal(buttonName: string): Promise<Locator> {
    await this.page.getByRole('button', { name: buttonName }).click();
    const modal = this.getModal();
    await expect(modal).toBeVisible();
    return modal;
  }

  async closeModal(): Promise<void> {
    await this.page.locator('.t3js-modal[open] .t3js-modal-close').click();
    await expect(this.getModal()).not.toBeVisible();
    await this.dismissFlashMessages();
  }

  /**
   * Asserts that a flash message with the given text appeared and dismisses
   * all flash messages afterwards, so consecutive saves do not stack
   * notifications and trip strict-mode locator violations.
   */
  async expectFlashMessage(text: string): Promise<void> {
    const flashMessage = this.page.locator(FLASH_MESSAGE_SELECTOR, { hasText: text });
    await expect(flashMessage).toBeVisible();
    await this.dismissFlashMessages();
  }

  async dismissFlashMessages(): Promise<void> {
    await this.page.evaluate(() => {
      document.dispatchEvent(new CustomEvent('typo3-notification-clear-all', { bubbles: true, composed: true }));
    });
    await expect(this.page.locator(FLASH_MESSAGE_SELECTOR)).toHaveCount(0);
  }
}

type InstallToolFixtures = {
  installTool: InstallTool;
};

export const test = base.extend<InstallToolFixtures>({
  installTool: async ({ page, request }, use) => {
    await use(new InstallTool(page, request));
  },
});

export { expect } from '@playwright/test';
