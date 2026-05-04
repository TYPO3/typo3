import { test, expect } from '../../fixtures/setup-fixtures';

const feLoginUrl = '/styleguide-demo-242/felogin-login';

test.describe('Frontend login plugin (felogin)', () => {
  test('shows a login failure on wrong credentials', async ({ page }) => {
    await page.goto(feLoginUrl);
    const frame = page.locator('.frame-type-felogin_login');
    await frame.locator('input[name="user"]').fill('username');
    await frame.locator('input[type="password"]').fill('wrong password');
    await frame.locator('input[type=submit]').click();
    await expect(frame.locator('h3')).toHaveText('Login failure');
  });

  test('logs in successfully and logs out again', async ({ page }) => {
    await page.goto(feLoginUrl);
    const loginFrame = page.locator('.frame-type-felogin_login');
    await loginFrame.locator('input[name="user"]').fill('styleguide-frontend-demo');
    await loginFrame.locator('input[type="password"]').fill('password');
    await loginFrame.locator('input[type=submit]').click();
    await expect(loginFrame).toContainText('You are now logged in as \'styleguide-frontend-demo\'');

    // Reload the page to reveal the logout form.
    await page.goto(feLoginUrl);
    const loggedInFrame = page.locator('.frame-type-felogin_login');
    await expect(loggedInFrame).toContainText('Username styleguide-frontend-demo');

    // Submit again to log out, then verify the login form is back.
    await loggedInFrame.locator('input[type=submit]').click();
    await expect(page.locator('.frame-type-felogin_login input[name="user"]')).toBeVisible();
  });
});
