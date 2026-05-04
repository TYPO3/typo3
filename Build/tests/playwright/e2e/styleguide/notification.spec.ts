import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('Styleguide notifications', () => {
  test('clear-all button appears once a second notification stacks', async ({ page, backend, workspace }) => {
    await page.goto('module/web/layout');
    await workspace.ensureLiveWorkspace();
    await backend.gotoModule('styleguide');

    const contentFrame = backend.contentFrame;
    await contentFrame.locator('a[aria-label="Open Component Library: Components module"]').click();

    await contentFrame.getByRole('link', { name: 'Open Notifications component' }).click();
    await expect(contentFrame.locator('.t3js-module-body')).toContainText('Notifications');

    const alertContainer = page.locator('#alert-container');
    const clearAll = alertContainer.locator('typo3-notification-clear-all');

    const exampleButton = contentFrame.locator('.styleguide-content .styleguide-example button').first();
    await exampleButton.click();
    await expect(alertContainer.locator('typo3-notification-message')).toHaveCount(1);
    await expect(clearAll).not.toBeVisible();

    await exampleButton.click();
    await expect(alertContainer.locator('typo3-notification-message')).toHaveCount(2);
    await expect(clearAll).toBeVisible();

    await clearAll.click();
    await expect(alertContainer.locator('typo3-notification-message')).toHaveCount(0);
    await expect(clearAll).not.toBeVisible();
  });
});
