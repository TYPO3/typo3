import { test, expect } from '@playwright/test';
import config from '../../config';

// A session of its own, so that both the login form and the sudo mode
// verification appear inside the embedding application.
test.use({ storageState: { cookies: [], origins: [] } });

const applicationUrl = new URL('/embedding-application', config.baseUrl).toString();
const backendPath = new URL(config.baseUrl).pathname;

/**
 * An application of the same origin that carries no TYPO3 object embeds the
 * backend in an iframe and lets each XMLHttpRequest of it through only once
 * its own main thread has had a turn. A frame of the same origin shares that
 * thread, so a synchronous request from the backend never completes.
 */
test('backend embedded by an application relaying its requests', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));

  // A document the server delivered, not one fulfilled by the test: Chrome
  // refuses to frame the private test host from a document without an address.
  await page.goto(applicationUrl);
  await page.route(
    (url) => url.pathname.startsWith(backendPath),
    async (route) => {
      if (route.request().resourceType() === 'xhr') {
        await page.mainFrame().evaluate(() => new Promise((resolve) => setTimeout(resolve)));
      }
      await route.continue();
    },
  );
  await page.evaluate((src: string): void => {
    document.body.replaceChildren();
    document.body.style.margin = '0';
    const frame = document.createElement('iframe');
    frame.id = 'embedded-backend';
    frame.src = src;
    frame.style.cssText = 'border: 0; width: 100vw; height: 100vh';
    document.body.append(frame);
  }, config.baseUrl);
  expect(await page.evaluate(() => 'TYPO3' in window)).toBe(false);

  const backend = page.frameLocator('#embedded-backend');

  await test.step('log in without navigating the application away', async () => {
    await expect(backend.locator('body')).toHaveAttribute('data-typo3-login-ready', 'true');
    await backend.getByLabel('Username').fill(config.login.admin.username);
    await backend.getByLabel('Password').fill(config.login.admin.password);
    await backend.getByRole('button', { name: 'Login' }).click();

    await expect(backend.locator('.modulemenu-action-active')).toBeVisible();
    expect(page.url()).toBe(applicationUrl);
  });

  await test.step('verify sudo mode inside the embedded backend', async () => {
    const contentFrame = backend.frameLocator('#typo3-contentIframe');
    await backend.locator('.toolbar-item-user .toolbar-item-link').click();
    await backend.getByRole('menuitem', { name: 'User Settings' }).click();
    const securityTab = contentFrame.getByRole('tab', { name: 'Account security' });
    // The tabs module sets the attribute once it handles clicks.
    await expect(securityTab).toHaveAttribute('aria-selected', 'false');
    await securityTab.click();
    await contentFrame.getByRole('link', { name: 'Setup multi-factor authentication' }).click();

    const dialog = backend.locator('typo3-backend-modal > dialog');
    await expect(dialog).toContainText('Verify with user password');
    await dialog.locator('input[type="password"]').fill(config.login.admin.password);
    await dialog.locator('button[name="verify"]').click();

    await expect(dialog).not.toBeVisible();
    await expect(contentFrame.locator('h1')).toContainText('Multi-factor Authentication Overview');
    expect(page.url()).toBe(applicationUrl);
  });

  await test.step('read the user settings without blocking the application', async () => {
    await backend.locator('a[data-modulemenu-identifier="system_log"]').click();

    await expect(backend.frameLocator('#typo3-contentIframe').locator('input[data-date-type]').first()).toBeAttached();
  });

  expect(errors).toEqual([]);
});
