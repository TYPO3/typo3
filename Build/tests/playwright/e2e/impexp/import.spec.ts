import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import type { Page } from '@playwright/test';
import * as path from 'node:path';

const fixturesDir = path.join(__dirname, 'fixtures');

const moduleHeader = '.module-docheader-navigation';
const moduleTabs = '#ImportExportController .nav-tabs';
const tabUpload = 'button[data-typo3-tab="#import-upload"]';
const tabMessages = 'button[data-typo3-tab="#import-errors"]';
const tabImport = '#import-import';
const inputUploadFile = 'input[type=file]';
const checkboxOverwriteFile = 'input#checkOverwriteExistingFiles';
const buttonUploadFile = 'button[name="_upload"]';
const buttonImport = 'button[name="tx_impexp[import_file]"]';
const buttonNewImport = 'button[name="tx_impexp[new_import]"]';
const flashMessages = '.typo3-messages';

async function openImportFromContextMenu(page: Page, node: Locator): Promise<void> {
  await node.click({ button: 'right' });
  const menu = page.locator('typo3-backend-context-menu');
  await menu.locator('button[data-contextmenu-id="root_more"]').click();
  await menu.locator('button[data-contextmenu-id="root_more_importT3d"]').click();
}

async function uploadFixture(page: Page, contentFrame: ReturnType<Page['frameLocator']>, fileName: string): Promise<void> {
  await expect(contentFrame.locator(tabUpload)).toBeVisible();
  await contentFrame.locator(tabUpload).click();
  await expect(contentFrame.locator(inputUploadFile)).toBeVisible();
  await contentFrame.locator(inputUploadFile).setInputFiles(path.join(fixturesDir, fileName));
  await contentFrame.locator(buttonUploadFile).click({ force: true });
}

test.describe('ext:impexp Import', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('records');
  });

  test('Import view displays title of selected page in module header', async ({ page, backend }) => {
    await backend.pageTree.open('styleguide TCA demo', 'elements t3editor');
    const node = page.locator('#typo3-pagetree-tree [role="treeitem"]', {
      has: page.locator('.node-contentlabel', { hasText: /^elements t3editor$/ }),
    }).first();
    await openImportFromContextMenu(page, node);

    await expect(backend.contentFrame.locator(moduleHeader)).toContainText('elements t3editor');
    await backend.contentFrame.locator(tabImport).getByRole('button', { name: 'Preview' }).click();
    await expect(backend.contentFrame.locator(moduleHeader)).toContainText('elements t3editor');
  });

  test('Upload of an existing file requires the overwrite flag', async ({ page, backend }) => {
    const contentFrame = backend.contentFrame;
    const fixture = '404_page_and_records.xml';

    // The upload form stays mounted only on a freshly entered import view;
    // after a submit the controller renders the import-progress state which
    // hides the upload form's interactive elements. Re-enter the view via
    // the context menu for each upload pass to keep state predictable.
    const enterUploadView = async () => {
      await backend.gotoModule('records');
      const node = await backend.pageTree.open('styleguide TCA demo');
      await openImportFromContextMenu(page, node);
      await contentFrame.locator(tabUpload).click();
      await expect(contentFrame.locator(inputUploadFile)).toBeVisible();
    };

    // First upload: succeeds (file does not yet exist or overwrite is on by default).
    await enterUploadView();
    await contentFrame.locator(inputUploadFile).setInputFiles(path.join(fixturesDir, fixture));
    await contentFrame.locator(buttonUploadFile).click();
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success`)).toBeVisible();

    // Second upload with overwrite: succeeds.
    await enterUploadView();
    await contentFrame.locator(inputUploadFile).setInputFiles(path.join(fixturesDir, fixture));
    await contentFrame.locator(checkboxOverwriteFile).check({ force: true });
    await contentFrame.locator(buttonUploadFile).click();
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success`)).toBeVisible();

    // Third upload without overwrite: fails.
    await enterUploadView();
    await contentFrame.locator(inputUploadFile).setInputFiles(path.join(fixturesDir, fixture));
    await contentFrame.locator(checkboxOverwriteFile).uncheck({ force: true });
    await contentFrame.locator(buttonUploadFile).click();
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-danger`)).toBeVisible();
  });

  test('Import is rejected if prerequisites are not met', async ({ page, backend }) => {
    const node = await backend.pageTree.open('styleguide TCA demo');
    const contentFrame = backend.contentFrame;
    const sysCategoryTable = '#recordlist-sys_category';
    const sysCategoryRecordsBefore = await contentFrame.locator(`${sysCategoryTable} .t3js-entity`).evaluateAll(
      (rows) => rows.map((row) => row.getAttribute('data-uid')),
    );

    await openImportFromContextMenu(page, node);
    await uploadFixture(page, contentFrame, 'sys_category_table_with_bootstrap_package.xml');

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success`)).toContainText('uploaded to');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-danger`)).toBeVisible();
    await expect(contentFrame.locator(flashMessages)).toContainText('Prerequisites for file import are not met.');
    await expect(contentFrame.locator(`${moduleTabs} ${tabMessages}`)).toBeVisible();

    await contentFrame.locator(buttonImport).click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await backend.modal.click({ name: 'ok' });

    await backend.pageTree.open('styleguide TCA demo');
    const sysCategoryRecordsAfter = await contentFrame.locator(`${sysCategoryTable} .t3js-entity`).evaluateAll(
      (rows) => rows.map((row) => row.getAttribute('data-uid')),
    );
    expect(sysCategoryRecordsAfter.filter((uid) => !sysCategoryRecordsBefore.includes(uid))).toHaveLength(0);
  });

  test('Importing a page tree adds the imported pages to the tree', async ({ page, backend }) => {
    const styleguideNode = await backend.pageTree.open('styleguide TCA demo');
    const contentFrame = backend.contentFrame;

    await openImportFromContextMenu(page, styleguideNode);
    await uploadFixture(page, contentFrame, '404_page_and_records.xml');

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success`)).toContainText('uploaded to');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-danger`)).not.toBeVisible();
    await expect(contentFrame.locator(`${moduleTabs} ${tabMessages}`)).not.toBeVisible();

    await contentFrame.locator(buttonImport).click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await backend.modal.click({ name: 'ok' });

    await expect(contentFrame.locator(buttonNewImport)).toBeVisible();
    await backend.pageTree.refresh();
    // 404 is imported as a child of styleguide TCA demo; expand the parent
    // so the new node is rendered in the virtualized tree.
    if (await styleguideNode.locator('[identifier="actions-chevron-end"]').count() > 0) {
      await styleguideNode.locator('.node-toggle').click();
      await backend.pageTree.isReady();
    }
    const importedPage = page.locator('#typo3-pagetree-tree [role="treeitem"]', {
      has: page.locator('.node-contentlabel', { hasText: /^404$/ }),
    });
    await expect(importedPage.first()).toBeVisible();
  });

  test('Importing a table adds the imported records', async ({ page, backend }) => {
    const node = await backend.pageTree.open('styleguide TCA demo');
    const contentFrame = backend.contentFrame;
    const sysCategoryTable = '#recordlist-sys_category';
    const sysCategoryRecordsBefore = await contentFrame.locator(`${sysCategoryTable} .t3js-entity`).evaluateAll(
      (rows) => rows.map((row) => row.getAttribute('data-uid')),
    );

    await openImportFromContextMenu(page, node);
    await uploadFixture(page, contentFrame, 'sys_category_table.xml');

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success`)).toContainText('uploaded to');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-danger`)).not.toBeVisible();

    await contentFrame.locator(buttonImport).click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await backend.modal.click({ name: 'ok' });

    await backend.pageTree.open('styleguide TCA demo');
    await expect(contentFrame.locator(`${sysCategoryTable} .t3js-entity`).first()).toBeVisible();
    const sysCategoryRecordsAfter = await contentFrame.locator(`${sysCategoryTable} .t3js-entity`).evaluateAll(
      (rows) => rows.map((row) => row.getAttribute('data-uid')),
    );
    const newRecords = sysCategoryRecordsAfter.filter((uid) => !sysCategoryRecordsBefore.includes(uid));
    expect(newRecords).toHaveLength(5);
  });

  test('Importing a single record adds it to the records list', async ({ page, backend }) => {
    const node = await backend.pageTree.open('styleguide TCA demo');
    const contentFrame = backend.contentFrame;
    const sysCategoryTable = '#recordlist-sys_category';
    const sysCategoryRecordsBefore = await contentFrame.locator(`${sysCategoryTable} .t3js-entity`).evaluateAll(
      (rows) => rows.map((row) => row.getAttribute('data-uid')),
    );

    await openImportFromContextMenu(page, node);
    await uploadFixture(page, contentFrame, 'sys_category_record.xml');

    await expect(contentFrame.locator(`${flashMessages} .alert.alert-success`)).toContainText('uploaded to');
    await expect(contentFrame.locator(`${flashMessages} .alert.alert-danger`)).not.toBeVisible();

    await contentFrame.locator(buttonImport).click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await backend.modal.click({ name: 'ok' });

    await backend.pageTree.open('styleguide TCA demo');
    await expect(contentFrame.locator(`${sysCategoryTable} .t3js-entity`).first()).toBeVisible();
    const sysCategoryRecordsAfter = await contentFrame.locator(`${sysCategoryTable} .t3js-entity`).evaluateAll(
      (rows) => rows.map((row) => row.getAttribute('data-uid')),
    );
    const newRecords = sysCategoryRecordsAfter.filter((uid) => !sysCategoryRecordsBefore.includes(uid));
    expect(newRecords).toHaveLength(1);
  });
});
