import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import type { Page } from '@playwright/test';

const moduleHeader = '.module-docheader-navigation';
const moduleDocheaderColumn = '.module-docheader-column';
const moduleTabsBody = '#ImportExportController .tab-content';
const tabConfiguration = '#export-configuration';
const tabFilePreset = '#export-filepreset';
const tabFilePresetButton = 'button[data-typo3-tab="#export-filepreset"]';
const buttonSaveToFile = 'button[name="tx_impexp[save_export]"]';
const flashMessages = '.typo3-messages';

async function openExportFromContextMenu(page: Page, node: Locator): Promise<void> {
  await node.click({ button: 'right' });
  const menu = page.locator('typo3-backend-context-menu');
  await menu.locator('button[data-contextmenu-id="root_more"]').click();
  await menu.locator('button[data-contextmenu-id="root_more_exportT3d"]').click();
}

// The data-typo3-tab click handler is registered by typo3/backend/tab.js
// when the JS module loads; under CI the click can fire before the handler
// is attached, so the tab pane stays hidden. Retry the click until the
// pane reports as visible.
async function activateTab(contentFrame: ReturnType<Page['frameLocator']>, navButtonSelector: string, paneSelector: string): Promise<void> {
  await expect(async () => {
    await contentFrame.locator(navButtonSelector).click();
    await expect(contentFrame.locator(paneSelector)).toBeVisible({ timeout: 1000 });
  }).toPass();
}

test.describe('ext:impexp Export', () => {
  test.beforeEach(async ({ backend, workspace }) => {
    await backend.gotoModule('records');
    // The playwright admin defaults to a non-live workspace which hides
    // versioned styleguide records the export tests rely on.
    await workspace.ensureLiveWorkspace();
  });

  test('Export view displays title of selected page in module header', async ({ page, backend }) => {
    await backend.pageTree.open('styleguide TCA demo', 'elements t3editor');
    const node = page.locator('#typo3-pagetree-tree [role="treeitem"]', {
      has: page.locator('.node-contentlabel', { hasText: /^elements t3editor$/ }),
    }).first();
    await openExportFromContextMenu(page, node);

    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator(moduleHeader)).toContainText('elements t3editor');
    await contentFrame.locator(tabConfiguration).getByRole('button', { name: 'Update' }).click();
    await expect(contentFrame.locator(moduleHeader)).toContainText('elements t3editor');
  });

  test('Exporting a table from the records list displays the root page title', async ({ backend }) => {
    const tablePageTitle = 'elements t3editor';
    const rootPageTitle = 'New TYPO3 site';

    await backend.pageTree.open('styleguide TCA demo', tablePageTitle);
    const contentFrame = backend.contentFrame;
    const tableLink = contentFrame.getByRole('link', { name: /^Form engine elements - t3editor \(\d+\)$/ });
    await expect(tableLink).toBeVisible();
    await tableLink.click();

    await contentFrame.locator(`${moduleDocheaderColumn} a[title="Export"]`).click();
    await expect(contentFrame.locator(tabConfiguration)).toBeVisible();
    await expect(contentFrame.locator(moduleHeader)).toContainText(rootPageTitle);
    await expect(contentFrame.locator(moduleHeader)).not.toContainText(tablePageTitle);

    await contentFrame.locator(tabConfiguration).getByRole('button', { name: 'Update' }).click();
    await expect(contentFrame.locator(moduleHeader)).toContainText(rootPageTitle);
    await expect(contentFrame.locator(moduleHeader)).not.toContainText(tablePageTitle);
  });

  test('Exporting a single record displays the root page title', async ({ page, backend }) => {
    const recordPageTitle = 'elements t3editor';
    const rootPageTitle = 'New TYPO3 site';

    await backend.pageTree.open('styleguide TCA demo', recordPageTitle);
    const contentFrame = backend.contentFrame;

    // The records list trigger button has data-contextmenu-trigger="click",
    // i.e. left-click opens the context menu (not the browser's native one).
    const recordIcon = contentFrame.locator('#recordlist-tx_styleguide_elements_t3editor tbody tr').first()
      .locator('button[data-contextmenu-trigger]').first();
    await expect(recordIcon).toBeVisible();
    await recordIcon.click();
    const menu = page.locator('typo3-backend-context-menu');
    await menu.locator('button[data-contextmenu-id="root_more"]').click();
    await menu.locator('button[data-contextmenu-id="root_more_exportT3d"]').click();

    await expect(contentFrame.locator(tabConfiguration)).toBeVisible();
    await expect(contentFrame.locator(moduleHeader)).toContainText(rootPageTitle);
    await expect(contentFrame.locator(moduleHeader)).not.toContainText(recordPageTitle);

    await contentFrame.locator(tabConfiguration).getByRole('button', { name: 'Update' }).click();
    await expect(contentFrame.locator(moduleHeader)).toContainText(rootPageTitle);
    await expect(contentFrame.locator(moduleHeader)).not.toContainText(recordPageTitle);
  });

  test('A preset can be saved and deleted', async ({ page, backend }) => {
    const node = await backend.pageTree.open('styleguide TCA demo', 'staticdata');
    const contentFrame = backend.contentFrame;
    const presetTitle = 'My First Preset';

    await openExportFromContextMenu(page, node);
    await expect(contentFrame.getByRole('heading', { name: 'Export pagetree configuration' })).toBeVisible();

    await activateTab(contentFrame, tabFilePresetButton, tabFilePreset);
    await contentFrame.locator(`${moduleTabsBody} input[name="tx_impexp[preset][title]"]`).fill(presetTitle);
    await contentFrame.locator(`${moduleTabsBody} button[name="preset[save]"]`).click();

    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await backend.modal.click({ name: 'ok' });
    await expect(dialog).not.toBeVisible();

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-info`)).toContainText(`New preset "${presetTitle}" is created`);

    await activateTab(contentFrame, tabFilePresetButton, tabFilePreset);
    // The option label is "{title} [{uid}] [Own]"; pick the value of the
    // option whose text contains our preset title and select by value.
    const presetOption = contentFrame.locator('#preset-select option', { hasText: presetTitle }).first();
    const presetValue = await presetOption.getAttribute('value');
    await contentFrame.locator('#preset-select').selectOption(presetValue ?? '');
    await contentFrame.locator(`${moduleTabsBody} button[name="preset[delete]"]`).click();

    await expect(dialog).toBeVisible();
    await backend.modal.click({ name: 'ok' });
    await expect(dialog).not.toBeVisible();

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-info`)).toContainText(/Preset #[0-9]+ deleted!/);
  });

  test('Exporting a page tree from the page tree context menu writes a file', async ({ page, backend }) => {
    const node = await backend.pageTree.open('styleguide TCA demo', 'staticdata');
    const contentFrame = backend.contentFrame;

    await openExportFromContextMenu(page, node);
    await expect(contentFrame.getByRole('heading', { name: 'Export pagetree configuration' })).toBeVisible();
    await expect(contentFrame.locator(moduleTabsBody)).not.toContainText('No tree exported - only tables on the page.');

    await activateTab(contentFrame, tabFilePresetButton, tabFilePreset);
    await contentFrame.locator(`${moduleTabsBody} ${buttonSaveToFile}`).click();

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success .alert-title`)).toContainText('SAVED FILE');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success .alert-message`)).toContainText(/Saved in "[^"]+", bytes/);
  });

  test('Exporting a table from the records list writes a file', async ({ page, backend }) => {
    const rootNode = page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="0"] .node-contentlabel');
    await expect(rootNode).toBeVisible();
    await rootNode.click();
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('h1', { hasText: 'New TYPO3 site' })).toBeVisible();

    await contentFrame.getByRole('link', { name: /^Backend usergroup/ }).click();
    await contentFrame.locator(`${moduleDocheaderColumn} a[title="Export"]`).click();

    await expect(contentFrame.locator(tabFilePresetButton)).toBeVisible();
    await expect(contentFrame.locator(moduleTabsBody)).toContainText('No tree exported - only tables on the page.');
    await expect(contentFrame.locator(moduleTabsBody)).toContainText('Export tables from pages');

    await activateTab(contentFrame, tabFilePresetButton, tabFilePreset);
    await contentFrame.locator(`${moduleTabsBody} ${buttonSaveToFile}`).click();

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success .alert-title`)).toContainText('SAVED FILE');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success .alert-message`)).toContainText(/Saved in "[^"]+", bytes/);
  });

  test('Exporting a single record writes a file', async ({ page, backend }) => {
    const rootNode = page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="0"] .node-contentlabel');
    await expect(rootNode).toBeVisible();
    await rootNode.click();
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('h1', { hasText: 'New TYPO3 site' })).toBeVisible();

    const recordIcon = contentFrame.locator('#recordlist-be_groups tbody tr').first()
      .locator('button[data-contextmenu-trigger]').first();
    await expect(recordIcon).toBeVisible();
    await recordIcon.click();
    const menu = page.locator('typo3-backend-context-menu');
    await menu.locator('button[data-contextmenu-id="root_more"]').click();
    await menu.locator('button[data-contextmenu-id="root_more_exportT3d"]').click();

    await expect(contentFrame.locator(tabFilePresetButton)).toBeVisible();
    await expect(contentFrame.locator(moduleTabsBody)).toContainText('No tree exported - only tables on the page.');
    await expect(contentFrame.locator(moduleTabsBody)).toContainText('Export single record');

    await activateTab(contentFrame, tabFilePresetButton, tabFilePreset);
    await contentFrame.locator(`${moduleTabsBody} ${buttonSaveToFile}`).click();

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success .alert-title`)).toContainText('SAVED FILE');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success .alert-message`)).toContainText(/Saved in "[^"]+", bytes/);
  });
});
