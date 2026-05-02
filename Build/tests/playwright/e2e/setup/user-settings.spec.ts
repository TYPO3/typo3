import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('User Settings', () => {

  test('Workspace indicator checkbox can be toggled and persists after reload', async ({ page, backend }) => {
    await page.goto('module/user/setup');

    const contentFrame = backend.contentFrame;

    await expect(contentFrame.locator('h1')).toContainText('User Settings');
    await contentFrame.getByRole('tab', { name: 'Personalization' }).click();

    const hiddenField = contentFrame.locator('input[type="hidden"][name*="showWorkspaceLiveIndicator"]');
    await expect(hiddenField).toBeAttached();

    const checkboxContainer = hiddenField.locator('xpath=ancestor::div[contains(@class,"formengine-field-item")]');
    const toggleCheckbox = checkboxContainer.locator('input[type="checkbox"]');

    // Read initial state of the hidden field
    const initialValue = await hiddenField.inputValue();

    const expectedValue = initialValue === '1' ? '0' : '1';

    await test.step('Toggle the checkbox', async () => {
      await toggleCheckbox.click();
      await expect(hiddenField).toHaveValue(expectedValue);
    });

    await test.step('Save settings', async () => {
      const loaded = await backend.formEngine.formEngineLoaded();
      await contentFrame.locator('button[name="data[save]"]').click();
      await loaded();
    });

    await test.step('Verify setting persists after save', async () => {
      await contentFrame.getByRole('tab', { name: 'Personalization' }).click();
      await expect(contentFrame.locator('input[type="hidden"][name*="showWorkspaceLiveIndicator"]'))
        .toHaveValue(expectedValue);
    });

    await test.step('Verify setting persists after full backend reload', async () => {
      await page.goto('module/user/setup');
      await expect(contentFrame.locator('h1')).toContainText('User Settings');
      await contentFrame.getByRole('tab', { name: 'Personalization' }).click();
      await expect(contentFrame.locator('input[type="hidden"][name*="showWorkspaceLiveIndicator"]'))
        .toHaveValue(expectedValue);
    });

    await test.step('Restore original value', async () => {
      const restoreCheckbox = contentFrame.locator('input[type="hidden"][name*="showWorkspaceLiveIndicator"]')
        .locator('xpath=ancestor::div[contains(@class,"formengine-field-item")]')
        .locator('input[type="checkbox"]');
      await restoreCheckbox.click();

      const loaded = await backend.formEngine.formEngineLoaded();
      await contentFrame.locator('button[name="data[save]"]').click();
      await loaded();
    });
  });
});
