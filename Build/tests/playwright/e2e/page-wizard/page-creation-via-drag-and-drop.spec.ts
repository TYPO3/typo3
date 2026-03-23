import { test, expect } from '../../fixtures/setup-fixtures';
import { PageWizard } from '../../fixtures/page-wizard';

test.beforeEach(async ({ backend, page }) => {
  await page.goto('module/web/layout');
  await backend.pageTree.isReady();
});

test('Drag and drop new page in node with children', async ({ backend }) => {
  const dummySiteTitle = `Dummy page tree with children ${backend.getUnixTimestamp()}`;
  const pageTitle = `Dummy page tree child ${backend.getUnixTimestamp()}`;

  // Create root page
  await backend.pageTree.dragNewPageTo(backend.pageTree.root, 1);
  await PageWizard.createDefaultPageAfterDrag(backend.modal, dummySiteTitle);
  await backend.pageTree.refresh();
  const newRootPageElement = backend.pageTree.tree.locator('[role="treeitem"]', { hasText: dummySiteTitle });

  // Create child page under newRootPageElement
  await backend.pageTree.dragNewPageTo(newRootPageElement, 1);
  await PageWizard.createDefaultPageAfterDrag(backend.modal, pageTitle);
  await backend.pageTree.isReady();

  const newPageElement = backend.pageTree.tree.locator('[role="treeitem"]', { hasText: pageTitle });

  // Validate page creation
  await backend.pageTree.open(dummySiteTitle, pageTitle);
  await expect(newPageElement.locator('.node-name')).toHaveText(pageTitle);

  // Delete pages
  await backend.pageTree.dragDeletePage(newPageElement);
  await backend.pageTree.dragDeletePage(newRootPageElement);
});

test('Drag and drop new page & create another page afterwards', async ({ backend }) => {
  const dummyPageOne = `Dummy page One ${backend.getUnixTimestamp()}`;
  const dummyPageTwo = `Dummy page Two ${backend.getUnixTimestamp()}`;

  await backend.pageTree.dragNewPageTo(backend.pageTree.root, 1);

  await PageWizard.ensurePageWizardModalVisible(backend.modal);
  const modalContent = await backend.modal.getModalContent();

  // Position and doktype are taken from the drop, so the wizard opens on step 3
  await expect(modalContent.locator('#tracker-details')).toHaveText('Step 3 of 5');

  // Step 3
  await PageWizard.fillTitleField(dummyPageOne, modalContent);
  await PageWizard.goToNextStep(modalContent);

  // Step 4
  await PageWizard.getButton(modalContent, PageWizard.CONFIRM_BUTTON_TEXT).click();
  await PageWizard.isReady(modalContent);

  // Step 5
  await PageWizard.getButton(modalContent, 'Create another page').click();
  await PageWizard.isReady(modalContent);

  // Step 1
  await PageWizard.goToNextStep(modalContent);

  // Step 2
  await PageWizard.goToNextStep(modalContent);

  // Step 3
  await PageWizard.fillTitleField(dummyPageTwo, modalContent);
  await PageWizard.goToNextStep(modalContent);

  // Step 4
  await PageWizard.getButton(modalContent, PageWizard.CONFIRM_BUTTON_TEXT).click();
  await PageWizard.isReady(modalContent);

  // Step 5
  await PageWizard.getButton(modalContent, PageWizard.FINISH_BUTTON_TEXT).click();

  await backend.pageTree.isReady();

  const dummyPageOneNode = backend.pageTree.tree.locator('[role="treeitem"]', { hasText: dummyPageOne });
  await expect(dummyPageOneNode).toHaveCount(1);

  const dummyPageTwoNode = backend.pageTree.tree.locator('[role="treeitem"]', { hasText: dummyPageTwo });
  await expect(dummyPageTwoNode).toHaveCount(1);

  await backend.pageTree.dragDeletePage(dummyPageTwoNode);
  await backend.pageTree.dragDeletePage(dummyPageOneNode);
});
