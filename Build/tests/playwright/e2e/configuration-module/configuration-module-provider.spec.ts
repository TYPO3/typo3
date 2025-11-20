import {test, expect} from '../../fixtures/setup-fixtures';

test.describe('Configuration Module Provider', () => {
  test.beforeEach(async ({backend}) => {
    await backend.gotoModule('system_config');
  });

  test('Select and display configuration', async ({backend}) => {
    const contentFrame = backend.contentFrame;

    // Explicitly select the default provider (TYPO3_CONF_VARS) to ensure clean state
    await backend.docHeader.selectInDropDown('Configuration to show', '$GLOBALS[\'TYPO3_CONF_VARS\'] (Global Configuration)');

    // Module can be accessed
    // Sorting is applied and TYPO3_CONF_VARS is the default provider to display
    await expect(contentFrame.locator('h1')).toContainText('Configuration of "$GLOBALS[\'TYPO3_CONF_VARS\'] (Global Configuration)"');
    await expect(contentFrame.locator('#ConfigurationView')).toBeVisible();

    // Middleware provider can be loaded
    await backend.docHeader.selectInDropDown('Configuration to show', 'HTTP Middlewares (PSR-15)');
    await expect(contentFrame.locator('h1')).toContainText('Configuration of "HTTP Middlewares (PSR-15)"');

    // Tree search can be applied
    await contentFrame.locator('#searchValue').fill('authentication');
    await expect(contentFrame.getByText('typo3/cms-frontend/authentication').first()).toBeVisible();
  });

  test('See all pages in dropdown', async ({backend}) => {
    const contentFrame = backend.contentFrame;

    const itemList = [
      '$GLOBALS[\'TYPO3_CONF_VARS\'] (Global Configuration)',
      '$GLOBALS[\'TCA\'] (Table configuration array)',
      '$GLOBALS[\'T3_SERVICES\'] (Registered Services)',
      '$GLOBALS[\'TYPO3_USER_SETTINGS\'] (User Settings Configuration)',
      'Table permissions per page type',
      '$GLOBALS[\'BE_USER\']->uc (User Settings)',
      '$GLOBALS[\'BE_USER\']->getTSConfig() (User TSconfig)',
      'Backend Routes',
      'Backend Modules',
      'HTTP Middlewares (PSR-15)',
      'Sites: TCA configuration',
      'Sites: YAML configuration',
      'Event Listeners (PSR-14)',
      'MFA providers',
    ];

    for (const item of itemList) {
      await backend.docHeader.selectInDropDown('Configuration to show', item)

      await expect(contentFrame.locator('h1')).toContainText(`Configuration of "${item}"`);
    }
  });
});
