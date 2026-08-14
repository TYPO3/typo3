import { Page } from '@playwright/test';
import { test, expect, InstallTool } from '../../fixtures/install-tool';

const BACKEND_LOGO_INPUT = '#em-backend-backendLogo';
const CUSTOM_LOGO = 'PKG:typo3/cms-core:Resources/Public/Images/typo3_variable.svg';
const MISSING_LOGO = 'PKG:typo3/cms-core:Resources/Public/Images/does-not-exist.svg';

/**
 * The backend logo is configured as "backendLogo" extension configuration of
 * EXT:backend and resolved through the system resource API. Both the "EXT:" and
 * the "PKG:" syntax must end up as an image the browser can actually load: a
 * URI is generated for a resource that does not exist as well, so a plain "src"
 * assertion would not notice a broken logo.
 *
 * The storage state is deliberately not reset here - the install tool has its
 * own authentication, but the topbar assertions need the backend session
 * provided by the e2e project.
 */
test.describe('Backend logo', () => {
  test.describe.configure({ mode: 'serial' });

  /**
   * Writes the given value into the "backendLogo" extension configuration and
   * returns the value that was configured before.
   */
  async function configureBackendLogo(installTool: InstallTool, page: Page, value: string): Promise<string> {
    await installTool.enable();
    await installTool.goto();
    // The install tool session survives locking, so the login form only shows up
    // on the first call, consecutive calls reuse the established session.
    const loginForm = page.locator('#t3-install-form-password');
    if (await loginForm.waitFor({ state: 'visible', timeout: 5000 }).then(() => true, () => false)) {
      await installTool.login();
    }
    await installTool.navigateTo('Settings');

    const modal = await installTool.openModal('Configure extensions…');
    await modal.locator('.panel-heading', { hasText: 'backend' }).click();
    // "backendLogo" lives in the "backend" category, the "login" category is the active tab.
    await modal.locator('[data-typo3-tab="#category-backend-backend"]').click();
    await expect(page.locator(BACKEND_LOGO_INPUT)).toBeVisible();
    const previousValue = await page.locator(BACKEND_LOGO_INPUT).inputValue();
    await page.locator(BACKEND_LOGO_INPUT).fill(value);
    await modal.getByRole('button', { name: 'Save "backend" configuration' }).click();
    await installTool.expectFlashMessage('Configuration saved');
    await installTool.closeModal();
    await installTool.disable();

    return previousValue;
  }

  test('logo is resolved from the configured system resource', async ({ installTool, page }) => {
    const previousValue = await configureBackendLogo(installTool, page, CUSTOM_LOGO);

    try {
      await test.step('package resource identifier is rendered in the topbar', async () => {
        await page.goto('module/web/layout');
        const logo = page.locator('.topbar-site-logo img');
        await expect(logo).toBeVisible();
        await expect(logo).toHaveAttribute('src', /typo3_variable\.svg/);
        // Dimensions come from the resolved resource, not from the defaults.
        await expect(logo).toHaveAttribute('width', '150');
        await expect(logo).toHaveAttribute('height', '52');
        // The generated URI must point at a resource the browser can fetch.
        await expect.poll(async () => logo.evaluate((image: HTMLImageElement) => image.naturalWidth)).toBeGreaterThan(0);
      });

      await configureBackendLogo(installTool, page, MISSING_LOGO);

      await test.step('missing resource falls back to the default logo', async () => {
        await page.goto('module/web/layout');
        const logo = page.locator('.topbar-site-logo img');
        await expect(logo).toBeVisible();
        await expect(logo).toHaveAttribute('src', /typo3_logo_orange\.svg/);
        await expect(logo).toHaveAttribute('width', '22');
        await expect(logo).toHaveAttribute('height', '22');
        await expect.poll(async () => logo.evaluate((image: HTMLImageElement) => image.naturalWidth)).toBeGreaterThan(0);
      });
    } finally {
      await configureBackendLogo(installTool, page, previousValue);
    }
  });
});
