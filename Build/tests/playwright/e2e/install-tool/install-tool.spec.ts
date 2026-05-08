import { test, expect, INSTALL_TOOL_PASSWORD } from '../../fixtures/install-tool';

// The e2e project depends on the login helper which stores the backend session.
// Install Tool has its own authentication, so we reset the storage state to start clean.
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Install Tool', () => {
  test.describe.configure({ mode: 'serial' });

  test.describe('Login', () => {
    test('install tool login', async ({ installTool, page }) => {
      await test.step('Assert the install tool is locked in the first place', async () => {
        await installTool.disable();
        await installTool.goto();
        await expect(page.locator('.callout-warning')).toContainText('The Install Tool is locked');

        const status = await installTool.getStatus();
        expect(status.enabled).toBe(false);
      });

      await test.step('Lock the tool without logging in', async () => {
        await installTool.enable();

        const status = await installTool.getStatus();
        expect(status.enabled).toBe(true);

        await page.reload();
        await expect(page.locator('#t3-install-form-password')).toBeVisible();
        await expect(page.getByText('Login to TYPO3 Install Tool')).toBeVisible();

        await page.getByRole('button', { name: 'Lock Install Tool again' }).click();
        await expect(page.locator('.callout-warning')).toContainText('The Install Tool is locked');

        const statusAfterLock = await installTool.getStatus();
        expect(statusAfterLock.enabled).toBe(false);
      });

      await test.step('Log into Install Tool', async () => {
        await installTool.enable();
        await page.reload();

        await expect(page.locator('#t3-install-form-password')).toBeVisible();
        await installTool.login();
        await expect(page.locator('h1')).toContainText('Maintenance');
      });

      await test.step('Assert page Maintenance contains the 9 expected cards', async () => {
        await installTool.navigateTo('Maintenance');

        await expect(page.locator('h2.card-title', { hasText: 'Flush TYPO3 and PHP Cache' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Analyze Database Structure' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Remove Temporary Assets' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Rebuild PHP Autoload Information' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Clear Persistent Database Tables' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Check and Update Reference Index' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Create Administrative User' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Reset Backend User Preferences' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Manage Language Packs' })).toBeVisible();

        expect(await page.locator('.card').count()).toBe(9);
      });

      await test.step('Assert page Settings contains the 6 expected cards', async () => {
        await installTool.navigateTo('Settings');

        await expect(page.locator('h2.card-title', { hasText: 'Extension Configuration' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Change Install Tool Password' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Manage System Maintainers' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Configuration Presets' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Feature Toggles' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Configure Installation-Wide Options' })).toBeVisible();

        expect(await page.locator('.card').count()).toBe(6);
      });

      await test.step('Assert page Upgrade contains the 6 expected cards', async () => {
        await installTool.navigateTo('Upgrade');

        await expect(page.locator('h2.card-title', { hasText: 'Update TYPO3 Core' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Upgrade Wizard' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'View Upgrade Documentation' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Check for Broken Extensions' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Check TCA Migrations' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Scan Extension Files' })).toBeVisible();

        expect(await page.locator('.card').count()).toBe(6);
      });

      await test.step('Assert page Environment contains the 6 expected cards', async () => {
        await installTool.navigateTo('Environment');

        await expect(page.locator('h2.card-title', { hasText: 'Environment Overview' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Environment Status' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Directory Status' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'PHP Info' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Test Mail Setup' })).toBeVisible();
        await expect(page.locator('h2.card-title', { hasText: 'Image Processing' })).toBeVisible();

        expect(await page.locator('.card').count()).toBe(6);
      });
    });
  });

  test.describe('Maintenance', () => {
    test.beforeEach(async ({ installTool }) => {
      await installTool.enable();
      await installTool.goto();
      await installTool.login();
      await installTool.navigateTo('Maintenance');
    });

    test('flush cache works', async ({ page }) => {
      await page.getByRole('button', { name: 'Flush cache' }).click();
      await expect(page.locator('.alert-success')).toBeVisible();
      await expect(page.locator('.alert-success .alert-title')).toContainText('Caches cleared');
    });

    test('analyze database structure works', async ({ installTool, page }) => {
      const modal = await installTool.openModal('Analyze database…');
      await expect(modal.locator('.modal-header-title')).toContainText('Analyze Database Structure');
      await expect(page.locator('.callout-success')).toBeVisible();
      await expect(page.locator('.callout-success .callout-title')).toContainText('Database schema is up to date. Good job!');
    });

    test('remove temporary assets works', async ({ installTool }) => {
      const modal = await installTool.openModal('Scan temporary files…');
      await expect(modal.locator('.modal-header-title')).toContainText('Remove Temporary Assets');
    });

    test('clear persistent tables works', async ({ installTool }) => {
      const modal = await installTool.openModal('Scan tables…');
      await expect(modal.locator('.modal-header-title')).toContainText('Clear Persistent Database Tables');
    });

    test('create admin user works', async ({ installTool }) => {
      const modal = await installTool.openModal('Create Administrator…');
      await expect(modal.locator('.modal-header-title')).toContainText('Create Administrative User');
    });

    test('reset backend user preferences works', async ({ page }) => {
      await page.getByRole('button', { name: 'Reset backend user preferences' }).click();
      await expect(page.locator('.alert-success')).toBeVisible();
      await expect(page.locator('.alert-success .alert-title')).toContainText('Reset preferences of all backend users');
      await expect(page.locator('.alert-success p')).toContainText('Preferences of all backend users have been reset');
    });

    test('manage language packs works', async ({ installTool }) => {
      const modal = await installTool.openModal('Manage languages…');
      await expect(modal.locator('.modal-header-title')).toContainText('Manage Language Packs');
      await expect(modal.locator('h2').first()).toContainText('Active languages');
    });
  });

  test.describe('Settings', () => {
    test.beforeEach(async ({ installTool }) => {
      await installTool.enable();
      await installTool.goto();
      await installTool.login();
      await installTool.navigateTo('Settings');
    });

    test('extension configuration works', async ({ installTool, page }) => {
      const logoAltText = 'TYPO3 logo alt text';
      const inputAltText = '#em-backend-loginLogoAlt';
      const panel = 'backend';

      const modal = await installTool.openModal('Configure extensions…');
      await modal.locator('.panel-heading', { hasText: panel }).click();
      await expect(page.locator(inputAltText)).toBeVisible();
      const previousLogoAltText = await page.locator(inputAltText).inputValue();
      await page.locator(inputAltText).fill(logoAltText);
      await modal.getByRole('button', { name: 'Save "backend" configuration' }).click();
      await installTool.expectFlashMessage('Configuration saved');
      await installTool.closeModal();

      const modal2 = await installTool.openModal('Configure extensions…');
      await modal2.locator('.panel-heading', { hasText: panel }).click();
      await expect(page.locator(inputAltText)).toBeVisible();
      await expect(page.locator(inputAltText)).toHaveValue(logoAltText);
      await page.locator(inputAltText).fill(previousLogoAltText);
      await modal2.getByRole('button', { name: 'Save "backend" configuration' }).click();
      await installTool.expectFlashMessage('Configuration saved');
      await installTool.closeModal();
    });

    test('change install tool password works', async ({ installTool, page }) => {
      const modal = await installTool.openModal('Change Install Tool Password…');

      await expect(page.locator('#t3-install-tool-password')).toHaveValue('');
      await expect(page.locator('#t3-install-tool-password-repeat')).toHaveValue('');

      await page.locator('#t3-install-tool-password').fill(INSTALL_TOOL_PASSWORD);
      await page.locator('#t3-install-tool-password-repeat').fill(INSTALL_TOOL_PASSWORD);
      await modal.getByRole('button', { name: 'Set new password' }).click();
      await installTool.expectFlashMessage('Install tool password changed');
      await installTool.closeModal();
    });

    test('manage system maintainers works', async ({ installTool }) => {
      const modal = await installTool.openModal('Manage System Maintainers…');

      await modal.locator('select-pure').evaluate((el: Element) => {
        (el.shadowRoot?.querySelector('.label') as HTMLElement | null)?.click();
      });

      await expect(modal.locator('option-pure').first()).toBeVisible();
      await modal.locator('option-pure').first().evaluate((el: Element) => {
        (el.shadowRoot?.querySelector('.option') as HTMLElement | null)?.click();
      });

      await modal.getByRole('button', { name: 'Save system maintainer list' }).click();
      await installTool.expectFlashMessage('Updated system maintainers');
      await installTool.closeModal();

      const modal2 = await installTool.openModal('Manage System Maintainers…');
      await expect(modal2.locator('option-pure[selected]').first()).toBeAttached();
      await modal2.locator('select-pure').evaluate((el: Element) => {
        (el.shadowRoot?.querySelector('.multi-selected .cross') as HTMLElement | null)?.click();
      });
      await modal2.getByRole('button', { name: 'Save system maintainer list' }).click();
      await installTool.expectFlashMessage('Cleared system maintainer list');
      await installTool.closeModal();
    });

    test('configuration presets works', async ({ installTool, page }) => {
      const modal = await installTool.openModal('Choose Preset…');
      await modal.locator('.panel-heading', { hasText: 'Cache settings' }).click();
      await expect(page.locator('#t3-install-tool-configuration-cache-file')).toBeVisible();

      // First ensure we're on "File" so switching to "Database" is an actual change
      await page.locator('#t3-install-tool-configuration-cache-file').click();
      await modal.getByRole('button', { name: 'Activate preset' }).click();

      // Switch to "Database"
      await page.locator('#t3-install-tool-configuration-cache-database').click();
      await modal.getByRole('button', { name: 'Activate preset' }).click();
      await installTool.expectFlashMessage('Configuration written');

      // Verify the preset is now "Database"
      await expect(page.locator('input[type="radio"][name="install[values][Cache][enable]"]:checked')).toHaveValue('Database');

      // Switch back to "File"
      await page.locator('#t3-install-tool-configuration-cache-file').click();
      await modal.getByRole('button', { name: 'Activate preset' }).click();
      await installTool.expectFlashMessage('Configuration written');

      await installTool.closeModal();
    });

    test('feature toggles works', async ({ installTool, page }) => {
      const featureToggle = '#t3-install-tool-features-redirects\\.hitCount';

      const modal = await installTool.openModal('Configure Features…');
      await page.locator(featureToggle).click();
      await modal.getByRole('button', { name: 'Save' }).click();
      await installTool.expectFlashMessage('Features updated');
      await installTool.closeModal();

      const modal2 = await installTool.openModal('Configure Features…');
      await expect(page.locator(featureToggle)).toBeChecked();
      await page.locator(featureToggle).click();
      await modal2.getByRole('button', { name: 'Save' }).click();
      await installTool.closeModal();
    });

    test('configure installation wide options works', async ({ installTool, page }) => {
      const panel = 'Backend';
      const checkbox = '#BE_lockSSL';

      const modal = await installTool.openModal('Configure options…');
      await modal.locator('.panel-heading', { hasText: panel }).click();
      await expect(page.locator(checkbox)).toBeVisible();
      await page.locator(checkbox).click();
      await modal.getByRole('button', { name: 'Write configuration' }).click();
      await installTool.expectFlashMessage('BE/lockSSL');
      await installTool.closeModal();

      const modal2 = await installTool.openModal('Configure options…');
      await modal2.locator('.panel-heading', { hasText: panel }).click();
      await expect(page.locator(checkbox)).toBeVisible();
      await expect(page.locator(checkbox)).toBeChecked();
      await page.locator(checkbox).click();
      await modal2.getByRole('button', { name: 'Write configuration' }).click();
      await installTool.closeModal();
    });
  });

  test.describe('Upgrade', () => {
    test.beforeEach(async ({ installTool }) => {
      await installTool.enable();
      await installTool.goto();
      await installTool.login();
      await installTool.navigateTo('Upgrade');
    });

    test('view upgrade documentation works', async ({ installTool, page }) => {
      const versionPanel = '#version-2 .t3js-changelog-list > div:first-child';

      const modal = await installTool.openModal('View Upgrade Documentation…');
      await expect(modal).toContainText('View Upgrade Documentation');

      await modal.locator('#heading-2').click();
      await expect(modal.locator('#version-2')).toBeVisible();

      const textCurrentFirstPanelHeading = await page.locator(`${versionPanel} .panel-heading`).textContent();

      await page.locator(`${versionPanel} button[data-bs-toggle="collapse"]`).click();
      await page.locator(`${versionPanel} .t3js-upgradeDocs-markRead`).click();
      await expect(page.locator('#version-2')).not.toContainText(textCurrentFirstPanelHeading!);

      await modal.locator('.t3js-modal-body').evaluate((el) => {
        el.scrollTop = 100000;
      });
      await modal.locator('#heading-read').click();
      await expect(modal.locator('#collapseRead')).toBeVisible();
      await expect(modal.locator('#collapseRead')).toContainText(textCurrentFirstPanelHeading!);
      await page.locator('#collapseRead .t3js-changelog-list > div:first-child .t3js-upgradeDocs-unmarkRead').click();
      await expect(page.locator('#version-2')).toContainText(textCurrentFirstPanelHeading!);
    });

    test('check for broken extensions works', async ({ installTool }) => {
      const modal = await installTool.openModal('Check Extension Compatibility…');
      await expect(modal).toContainText('ext_localconf.php of all loaded extensions successfully loaded');

      await modal.getByRole('button', { name: 'Check extensions' }).click();
      await expect(modal).toContainText('ext_localconf.php of all loaded extensions successfully loaded');
    });

    test('check TCA migrations works', async ({ installTool }) => {
      const modal = await installTool.openModal('Check TCA Migrations…');
      await expect(modal).toContainText('Checks whether the current TCA needs migrations and displays the new migration paths which need to be adjusted manually');
    });
  });

  test.describe('Environment', () => {
    test.beforeEach(async ({ installTool }) => {
      await installTool.enable();
      await installTool.goto();
      await installTool.login();
      await installTool.navigateTo('Environment');
    });

    const cardsWithModals = [
      { title: 'Environment Overview', button: 'Show System Information…', seeInModal: 'Operating system' },
      { title: 'Environment Status', button: 'Check Environment…', seeInModal: 'File uploads allowed in PHP' },
      { title: 'Directory Status', button: 'Check Environment…', seeInModal: 'PHP version is fine' },
      { title: 'PHP Info', button: 'View PHP Info…', seeInModal: 'PHP Version' },
      { title: 'Test Mail Setup', button: 'Test Mail Setup…', seeInModal: 'Check the basic mail functionality by entering your email address here and clicking the button.' },
    ];

    for (const { title, button, seeInModal } of cardsWithModals) {
      test(`card "${title}" opens modal with expected content`, async ({ installTool, page }) => {
        await expect(page.locator('h2.card-title', { hasText: title })).toBeVisible();
        const modal = await installTool.openModal(button);
        await expect(modal).toContainText(seeInModal);
        await installTool.closeModal();
      });
    }

    test('image processing works', async ({ installTool }) => {
      const modal = await installTool.openModal('Test Images');
      await expect(modal.locator('.t3js-modal-close')).toBeVisible();
    });
  });

  test.afterEach(async ({ installTool }) => {
    await installTool.disable();
  });
});
