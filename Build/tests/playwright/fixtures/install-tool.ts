import { test as base, Page, expect, Locator, APIRequestContext } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import config from '../config';

export const INSTALL_TOOL_PASSWORD = 'Temporary Password - 123';
export const FLASH_MESSAGE_SELECTOR = '#alert-container typo3-notification-message';

const HELPER_SECRET_HEADER = 'X-Playwright-Helper-Secret';
const HELPER_SECRET_FILE = resolve(__dirname, '../../../../typo3temp/var/tests/playwright-composer/var/transient/playwright-helper.secret');

function readHelperSecret(): string {
  if (process.env.PLAYWRIGHT_HELPER_SECRET) {
    return process.env.PLAYWRIGHT_HELPER_SECRET;
  }
  const file = process.env.PLAYWRIGHT_HELPER_SECRET_FILE || HELPER_SECRET_FILE;
  return readFileSync(file, 'utf-8').trim();
}

export type InstallToolPage = 'Maintenance' | 'Settings' | 'Upgrade' | 'Environment';

export interface InstallToolStatus {
  enabled: boolean;
}

export class InstallTool {
  private readonly page: Page;
  private readonly request: APIRequestContext;
  private readonly helperHeaders: Record<string, string>;

  constructor(page: Page, request: APIRequestContext) {
    this.page = page;
    this.request = request;
    this.helperHeaders = { [HELPER_SECRET_HEADER]: readHelperSecret() };
  }

  async enable(): Promise<void> {
    await this.request.get(`${config.baseUrl}playwright-helper/install-tool/enable`, { headers: this.helperHeaders });
  }

  async disable(): Promise<void> {
    await this.request.get(`${config.baseUrl}playwright-helper/install-tool/disable`, { headers: this.helperHeaders });
  }

  async getStatus(): Promise<InstallToolStatus> {
    const response = await this.request.get(`${config.baseUrl}playwright-helper/install-tool/status`, { headers: this.helperHeaders });
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
