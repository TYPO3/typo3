import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('Styleguide exception', () => {
  test('debug exception page is shown inside the components module', async ({ page, backend, workspace }) => {
    await page.goto('module/web/layout');
    await workspace.ensureLiveWorkspace();
    await backend.gotoModule('styleguide');

    const contentFrame = backend.contentFrame;
    await contentFrame.locator('a[aria-label="Open Component Library: Components module"]').click();

    await contentFrame.getByRole('link', { name: 'Open Exception component' }).click();
    await expect(contentFrame.locator('.styleguide-content h1')).toHaveText('Exception');

    const exceptionFrame = contentFrame.frameLocator('.styleguide-content iframe');
    await expect(exceptionFrame.locator('.exception-title').first()).toContainText('StatusException');
    await expect(exceptionFrame.locator('.trace-message').first()).toContainText('Dummy exception');
    await expect(exceptionFrame.locator('.exception-title')).toHaveCount(1);
    await expect(exceptionFrame.locator('.copy-button').first()).toBeVisible();
  });
});
