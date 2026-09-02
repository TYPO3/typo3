import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { TOTP } from 'otpauth';
import { BackendPage } from '../../fixtures/backend-page';
import type { Locator } from '@playwright/test';

const testuser = 'mfa_test_user';
const testpass = 'Pa$$w0rd';

const handleSudoModeVerification = async (page: Locator, backend: BackendPage, password: string): Promise<void> => {
  const sudoModal = page.locator('typo3-backend-modal > dialog');
  try {
    await expect(sudoModal).toContainText('Verify with user password');
    await sudoModal.locator('input[type="password"]').fill(password);
    await backend.modal.click({ name: 'verify' });
    await expect(sudoModal).not.toBeVisible();
  } catch {
    // No sudo verification required
  }
};

test('Create OTP user and test login with OTP authentication', async ({ backend, page, browser }) => {

  await test.step('Create OTP user', async () => {
    const { contentFrame, formEngine } = backend;

    await backend.gotoModule('backend_user_management');

    const formEngineReady = await formEngine.formEngineLoaded();
    await contentFrame.getByRole('button', { name: 'Create admin' }).click();
    await formEngineReady();

    await contentFrame.locator('input[data-formengine-input-name$="[username]"]').fill(testuser);
    await contentFrame.locator('input[data-formengine-input-name$="[password]"]').fill(testpass);

    await contentFrame.getByRole('tab', { name: 'Access' }).click();
    await contentFrame.locator('input[data-formengine-input-name$="[disable]"]').click();

    await formEngine.closeButton.click();
    await page.getByRole('button', { name: 'Save and close' }).click();

    const loaded = await formEngine.formEngineLoaded();
    await handleSudoModeVerification(page, backend, 'password');
    await loaded();

    const switchToUser = contentFrame.locator('#typo3-backend-user-list tr')
      .filter({ hasText: testuser, exact: true })
      .getByRole('button', { name: 'Switch to user' });
    await expect(switchToUser).toBeVisible();
  });

  let totp: TOTP;

  await test.step('Login as User and setup OTP', async () => {
    // Create a new (incognito) context to test the login for "mfa_test_suer"
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const incognitoPage = await context.newPage();
    const backend = new BackendPage(incognitoPage);
    await incognitoPage.goto(config.baseUrl);
    await incognitoPage.getByLabel('Username').fill(testuser);
    await incognitoPage.getByLabel('Password').fill(testpass);
    await incognitoPage.getByRole('button', { name: 'Login' }).click();
    await incognitoPage.waitForLoadState('networkidle');

    // Using `.toPass()` since dropdown click handler may not
    // be hydrated by the time we're clicking the button
    const openUserDropdown = () => expect(async () => {
      await incognitoPage.locator('.toolbar-item-user .toolbar-item-link').click();
      await expect(incognitoPage.locator('.toolbar-item-user .dropdown-menu')).toBeVisible({ timeout: 1000 });
      await expect(incognitoPage.locator('.toolbar-item-user .toolbar-item-name')).toHaveText(testuser);
    }).toPass();

    await openUserDropdown();

    await incognitoPage.getByRole('menuitem', { name: 'User Settings' }).click();

    const { contentFrame } = backend;
    await contentFrame.getByRole('tab', { name: 'Account security' }).click();
    await contentFrame.getByRole('link', { name: 'Setup multi-factor authentication' }).click();

    await handleSudoModeVerification(incognitoPage, backend, testpass);

    // @todo update to playwright 1.60 and use
    //   `await contentFrame.getByRole('button', { description: 'Setup Time-based one-time password' }).click();`
    // instead of:
    await contentFrame.getByRole('link', { name: 'Setup' }).first().click();

    const secret = await contentFrame.locator('#secret').inputValue();

    totp = new TOTP({
      algorith: 'SHA1',
      digits: 6,
      period: 30,
      secret,
    });
    const totpValue = totp.generate();
    await contentFrame.locator('input[name="totp"]').fill(totpValue);

    await contentFrame.getByRole('button', { name: 'Save' }).click();
    await expect(contentFrame.getByText('Successfully activated MFA')).toBeVisible();

    await openUserDropdown();
    await incognitoPage.getByRole('Link', { name: 'Logout' }).click();

    // Dispose (incognito) context
    await context.close();
  });

  await test.step('Login as User with OTP', async () => {
    // Create a new ("incognito") context to test the login for the mfa test user
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const incognitoPage = await context.newPage();
    await incognitoPage.goto(config.baseUrl);
    await incognitoPage.getByLabel('Username').fill(testuser);
    await incognitoPage.getByLabel('Password').fill(testpass);
    await incognitoPage.getByRole('button', { name: 'Login' }).click();

    await incognitoPage.getByLabel('Enter the six-digit code').fill(totp.generate());
    await incognitoPage.getByRole('button', { name: 'Verify' }).click();

    await expect(incognitoPage.locator('.toolbar-item-user .toolbar-item-name')).toHaveText(testuser);

    // Dispose (incognito) context
    await context.close();
  });

  await test.step('Login via route redirect with OTP', async () => {
    // Create a new ("incognito") context to test the login for the mfa test user
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const incognitoPage = await context.newPage();
    await incognitoPage.goto(config.baseUrl + 'module/file/list');
    await incognitoPage.getByLabel('Username').fill(testuser);
    await incognitoPage.getByLabel('Password').fill(testpass);
    await incognitoPage.getByRole('button', { name: 'Login' }).click();

    await incognitoPage.getByLabel('Enter the six-digit code').fill(totp.generate());
    await incognitoPage.getByRole('button', { name: 'Verify' }).click();

    // Expect the Media URL to be active
    await expect(incognitoPage.locator('.modulemenu-action-active')).toHaveText('Media');

    // Dispose (incognito) context
    await context.close();
  });

  await test.step('MFA aborted, but restarted later (#109133)', async () => {
    // Create a new ("incognito") context to test the login for the mfa test user
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const incognitoPage = await context.newPage();
    await incognitoPage.goto(config.baseUrl);
    await incognitoPage.getByLabel('Username').fill(testuser);
    await incognitoPage.getByLabel('Password').fill(testpass);
    await incognitoPage.getByRole('button', { name: 'Login' }).click();

    await expect(incognitoPage.getByLabel('Enter the six-digit code')).toBeVisible();

    // Switch to a new URL, albeit MFA procedere has not been completed
    await incognitoPage.goto(config.baseUrl + 'module/file/list');

    await incognitoPage.getByLabel('Enter the six-digit code').fill(totp.generate());
    await incognitoPage.getByRole('button', { name: 'Verify' }).click();

    // Expect the Media URL to be active (module redirect must not be "lost" during MFA)
    await expect(incognitoPage.locator('.modulemenu-action-active')).toHaveText('Media');

    // Dispose (incognito) context
    await context.close();
  });
});
