import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import { PageWizard } from '../../fixtures/page-wizard';

test.beforeEach(async ({ backend, page }) => {
  await page.goto('module/web/layout');
  await backend.pageTree.isReady();
});

/**
 * Walk the page wizard from the page tree toolbar to a created record
 *
 * @param backend The backend page the toolbar belongs to
 * @param title The page title to be used
 * @param doktype Label of the doktype card to select, omitted to keep the preselected one
 * @return The tree node of the created record
 */
async function createViaToolbarButton(backend: BackendPage, title: string, doktype?: string): Promise<Locator> {
  const addPageButton = backend.pageTree.toolbar.getByRole('button', { name: 'Page', exact: true });
  await expect(addPageButton).toHaveCount(1);

  await addPageButton.click();
  await PageWizard.ensurePageWizardModalVisible(backend.modal);
  const modalContent = await backend.modal.getModalContent();

  // Step 1
  await PageWizard.goToNextStep(modalContent);

  // Step 2
  if (doktype !== undefined) {
    await PageWizard.selectDoktype(doktype, modalContent);
  }
  await PageWizard.goToNextStep(modalContent);

  // Step 3
  await PageWizard.fillTitleField(title, modalContent);
  await PageWizard.goToNextStep(modalContent);

  // Step 4
  await PageWizard.getButton(modalContent, PageWizard.CONFIRM_BUTTON_TEXT).click();
  await PageWizard.isReady(modalContent);

  // Step 5
  await PageWizard.getButton(modalContent, PageWizard.FINISH_BUTTON_TEXT).click();

  await backend.pageTree.isReady();

  return backend.pageTree.tree.locator('[role="treeitem"]', { hasText: title });
}

test('Create new page via "Create New Page Button"', async ({ backend }) => {
  const dummyPageTitle = `New Page ${backend.getUnixTimestamp()}`;

  // No doktype is picked, so this pins what the doktype step preselects.
  const newPageNode = await createViaToolbarButton(backend, dummyPageTitle);
  await expect(newPageNode).toHaveCount(1);
  await expect(newPageNode.locator('.node-icon [identifier="apps-pagetree-page-default"]')).toHaveCount(1);

  await backend.pageTree.dragDeletePage(newPageNode);
});

test('Create new folder via "Create New Page Button"', async ({ backend }) => {
  const dummyFolderTitle = `New Folder ${backend.getUnixTimestamp()}`;

  // The doktype step preselects the first selectable type, so only the icon
  // tells a folder apart from the standard page an unregistered click leaves.
  const newFolderNode = await createViaToolbarButton(backend, dummyFolderTitle, 'Folder');
  await expect(newFolderNode).toHaveCount(1);
  await expect(newFolderNode.locator('.node-icon [identifier="apps-pagetree-folder-default"]')).toHaveCount(1);

  await backend.pageTree.dragDeletePage(newFolderNode);
});
