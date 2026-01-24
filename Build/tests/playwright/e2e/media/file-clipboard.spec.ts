import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('Media Clipboard', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('media_management');
  });

  test('Switch between copy and move modes', async ({ backend }) => {
    const clipBoard = backend.contentFrame.locator('[data-clipboard-panel]');

    await test.step('Verify initial state - move mode is checked', async () => {
      const copyRadio = clipBoard.getByText('Copy elements');
      await expect(copyRadio).toBeVisible();
      await copyRadio.click();

      const moveRadio = clipBoard.getByText('Move elements');
      await expect(moveRadio).toBeVisible();
      await expect(moveRadio).toBeChecked();
      await expect(copyRadio).toBeVisible();
      await expect(copyRadio).not.toBeChecked();
    });

    await test.step('Switch to copy mode', async () => {
      const copyRadio = clipBoard.getByRole('radio', { name: 'Copy elements' });
      const moveRadio = clipBoard.getByRole('radio', { name: 'Move elements' });

      // Click the label associated with the copy radio button
      const copyLabel = clipBoard.locator('label[for="clipboard-copymode-copy"]');
      await copyLabel.click();

      await expect(copyLabel).toBeVisible();
      // Wait for the radio state to change
      await expect(copyRadio).toBeChecked();
      await expect(moveRadio).not.toBeChecked();
    });
  });

  test('Add and remove single file from clipboard', async ({ backend }) => {
    const fileName = 'bus_lane.jpg';

    await test.step('Navigate to styleguide folder', async () => {
      // Click View dropdown to expand file tree
      await backend.contentFrame.getByText(' View', { exact: false }).click();
      await backend.contentFrame.getByText('List', { exact: true }).click();

      // Wait for and click styleguide folder
      const folder = await backend.fileTree.open('fileadmin', 'styleguide');
      await folder.click();
      await backend.fileTree.isReady();
      await expect(backend.contentFrame.getByText(fileName)).toBeVisible();
    });

    await test.step('Add file to clipboard via Cut action', async () => {
      const fileRow = backend.contentFrame.getByRole('row', { name: fileName });
      const actionDropdown = fileRow.getByRole('button', { name: 'More options...' });
      await actionDropdown.click();

      // Wait for dropdown menu to be visible and click Cut option
      const dropdownMenu = backend.contentFrame.locator('.dropdown-menu:popover-open');
      await expect(dropdownMenu).toBeVisible();

      // Wait for module response after Cut action
      const moduleResponse = backend.waitForModuleResponse();
      await dropdownMenu.getByText('Cut').click();
      await moduleResponse;

      // Verify file appears in clipboard panel
      const clipboardPanel = backend.contentFrame.locator('[data-clipboard-panel]');
      await expect(clipboardPanel.getByRole('link', { name: fileName })).toBeVisible();
    });

    await test.step('Remove file from clipboard', async () => {
      const clipboardPanel = backend.contentFrame.locator('[data-clipboard-panel]');
      await clipboardPanel.getByRole('button', { name: 'Remove element' }).click();

      // Verify file is no longer in clipboard
      await expect(clipboardPanel.getByRole('link', { name: fileName })).not.toBeVisible();
    });
  });

  test('Add and remove multiple files from clipboard', async ({ backend, page }) => {
    const expectedFiles = ['bus_lane.jpg', 'telephone_box.jpg', 'underground.jpg'];

    await test.step('Navigate to styleguide folder', async () => {
      await backend.contentFrame.getByText('View', { exact: true }).click();
      await backend.contentFrame.getByText('List', { exact: true }).click();
      const moduleLoaded = backend.waitForModuleResponse();
      await backend.contentFrame.getByRole('button', { name: 'styleguide', exact: true }).click();
      await moduleLoaded;
    });

    await test.step('Add multiple files to clipboard', async () => {
      // Mark iframe content as old to detect reload
      await page.evaluate(() => {
        const iframe = document.querySelector('#typo3-contentIframe') as HTMLIFrameElement;
        if (iframe?.contentDocument) {
          (iframe.contentDocument as any).cestIsOldPage = true;
        }
      });

      // Click to switch to multi-selection mode (reloads iframe)
      await backend.contentFrame.getByRole('button', { name: 'Clipboard #1 (multi-selection' }).click();

      // Wait for iframe to reload (cestIsOldPage should be gone)
      await page.waitForFunction(() => {
        const iframe = document.querySelector('#typo3-contentIframe') as HTMLIFrameElement;
        return iframe?.contentDocument && !('cestIsOldPage' in iframe.contentDocument);
      });

      // Wait for multi-selection mode to be ready
      await expect(backend.contentFrame.getByText('Clipboard #1 (multi-selection mode)')).toBeVisible();

      // Open multi-selection actions dropdown
      await backend.contentFrame.getByRole('button', { name: 'Open selection options' }).click();

      // Wait for dropdown menu to be visible
      const selectionDropdown = backend.contentFrame.locator('.dropdown-menu:popover-open');
      await expect(selectionDropdown).toBeVisible();

      // Select all files
      await selectionDropdown.getByRole('button', { name: 'Check all', exact: true }).click();

      // Copy marked files to clipboard
      const moduleResponse = backend.waitForModuleResponse();
      await backend.contentFrame.getByRole('button', { name: 'Transfer to clipboard' }).click();
      await moduleResponse;

      // Verify all files are in clipboard
      const clipboardPanel = backend.contentFrame.locator('[data-clipboard-panel]');
      for (const file of expectedFiles) {
        await expect(clipboardPanel.getByText(file)).toBeVisible();
      }
    });

    await test.step('Remove all files from clipboard', async () => {
      const clipboardPanel = backend.contentFrame.locator('[data-clipboard-panel]');
      const removeAllButton = clipboardPanel.getByRole('button', { name: 'Remove all' });

      // Wait for button to be visible and enabled before clicking
      await expect(removeAllButton).toBeVisible();
      await expect(removeAllButton).toBeEnabled();

      // Click and wait for the first file to disappear (indicates removal started)
      await removeAllButton.click();
      await expect(clipboardPanel.getByText(expectedFiles[0])).not.toBeVisible();

      // Verify all files are removed
      for (const file of expectedFiles) {
        await expect(clipboardPanel.getByText(file)).not.toBeVisible();
      }
    });
  });
});
