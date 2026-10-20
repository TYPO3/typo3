import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('Configuration Module Provider', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('system_config');
  });

  test('Select and display configuration', async ({ backend }) => {
    const contentFrame = backend.contentFrame;

    // Explicitly select the default provider (TYPO3_CONF_VARS) to ensure clean state
    await backend.docHeader.selectInDropDown('Configuration to show', 'TYPO3_CONF_VARS (Global Configuration)');

    // Module can be accessed
    // Sorting is applied and TYPO3_CONF_VARS is the default provider to display
    await expect(contentFrame.locator('h1')).toContainText('Configuration of "TYPO3_CONF_VARS (Global Configuration)"');
    await expect(contentFrame.locator('#ConfigurationView')).toBeVisible();

    // Middleware provider can be loaded
    await backend.docHeader.selectInDropDown('Configuration to show', 'HTTP Middlewares (PSR-15)');
    await expect(contentFrame.locator('h1')).toContainText('Configuration of "HTTP Middlewares (PSR-15)"');

    // Tree search can be applied
    await contentFrame.locator('#searchValue').fill('authentication');
    await expect(contentFrame.getByText('typo3/cms-frontend/authentication').first()).toBeVisible();
  });

  test('See all pages in dropdown', async ({ backend }) => {
    const contentFrame = backend.contentFrame;

    const itemList = [
      'TYPO3_CONF_VARS (Global Configuration)',
      'TCA (Table Configuration Array)',
      'T3_SERVICES (Registered Services)',
      'Table permissions per page type',
      'User Settings (BE_USER->uc)',
      'User TSconfig (BE_USER->getTSConfig())',
      'Backend Routes',
      'Backend Modules',
      'HTTP Middlewares (PSR-15)',
      'Sites: TCA Configuration',
      'Sites: YAML Configuration',
      'Sites: Sets',
      'Event Listeners (PSR-14)',
      'MFA Providers',
    ];

    for (const item of itemList) {
      await backend.docHeader.selectInDropDown('Configuration to show', item);

      await expect(contentFrame.locator('h1')).toContainText(`Configuration of "${item}"`);
    }
  });

  test('Display resolved site sets grouped by site', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await backend.docHeader.selectInDropDown('Configuration to show', 'Sites: Sets');

    const roots = contentFrame.locator('.t3js-collapse-states-search-tree > ul > li');
    await expect(roots.locator(':scope > .treelist-group > .treelist-label')).toHaveText(['sites', 'siteSets']);
    await roots.first().locator(':scope > typo3-backend-tree-node-toggle').click();

    const site = roots.first().locator(':scope > .treelist-collapse > ul > li').filter({
      has: contentFrame.locator('.treelist-label').filter({ hasText: /^typo3\/styleguide$/ }),
    });
    await expect(site.locator(':scope > .treelist-group > .treelist-label')).toHaveText(/^styleguide-demo-/);
    await site.locator(':scope > typo3-backend-tree-node-toggle').click();

    const sets = site.locator(':scope > .treelist-collapse > ul > li');
    await expect(sets.locator(':scope > .treelist-group > .treelist-label')).toContainText([
      'typo3/fluid-styled-content',
      'typo3/styleguide',
    ]);
    await expect(sets.last().locator(':scope > .treelist-group > .treelist-label')).toHaveText('typo3/styleguide');
    await sets.last().locator(':scope > typo3-backend-tree-node-toggle').click();
    await expect(sets.last().getByText('Styleguide Theme', { exact: true })).toBeVisible();

    await roots.last().locator(':scope > typo3-backend-tree-node-toggle').click();
    const allSets = roots.last().locator(':scope > .treelist-collapse > ul > li');
    await expect(allSets.locator(':scope > .treelist-group > .treelist-label')).toContainText([
      'typo3/fluid-styled-content',
      'typo3/styleguide',
    ]);
    const styleguide = allSets.filter({
      has: contentFrame.locator(':scope > .treelist-group > .treelist-label').filter({ hasText: /^typo3\/styleguide$/ }),
    });
    await styleguide.locator(':scope > typo3-backend-tree-node-toggle').click();
    await expect(styleguide.getByText('Styleguide Theme', { exact: true })).toBeVisible();

    const optionalDependencies = styleguide.locator(':scope > .treelist-collapse > ul > li').filter({
      has: contentFrame.locator(':scope > .treelist-group > .treelist-label').filter({ hasText: /^optionalDependencies$/ }),
    });
    await optionalDependencies.locator(':scope > typo3-backend-tree-node-toggle').click();
    await expect(optionalDependencies.locator(':scope > .treelist-collapse > ul > li > .treelist-group > .treelist-label')).toContainText([
      'typo3/fluid-styled-content',
      'typo3/form',
    ]);
  });
});
