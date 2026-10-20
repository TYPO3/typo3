import { test, expect } from '../../fixtures/setup-fixtures';
import { PageWizard } from '../../fixtures/page-wizard';

test.describe('Page tree focus after creating a page', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('web_layout');
    await backend.pageTree.isReady();
  });

  test('Focus stays on the drop target after a page was created by dragging a toolbar icon', async ({ page, backend }) => {
    const pageTitle = `Focus page ${backend.getUnixTimestamp()}`;
    const dropTarget = backend.pageTree.tree.locator('[role="treeitem"]', { has: page.locator('[identifier="apps-pagetree-root"]') });

    await backend.pageTree.dragNewPageTo(backend.pageTree.root, 1);
    await PageWizard.createDefaultPageAfterDrag(backend.modal, pageTitle);
    await backend.pageTree.isReady();

    const newPageNode = backend.pageTree.tree.locator('[role="treeitem"]', { hasText: pageTitle });
    await expect(newPageNode).toHaveCount(1);
    await expect(dropTarget).toBeFocused();

    await page.keyboard.press('Shift+Tab');
    await expect(backend.pageTree.toolbar.locator(':focus')).toHaveCount(1);
    await page.keyboard.press('Tab');
    await expect(dropTarget).toBeFocused();

    await backend.pageTree.dragDeletePage(newPageNode);
  });

  test('The tree item focused before the page wizard keeps the tab stop', async ({ page, backend }) => {
    const pageTitle = `Focus page ${backend.getUnixTimestamp()}`;
    const addPageButton = backend.pageTree.toolbar.getByRole('button', { name: 'Page', exact: true });

    await backend.pageTree.open('Root');
    const rootNode = backend.pageTree.tree.locator('[role="treeitem"][data-id="1"]');
    await rootNode.focus();
    await expect(rootNode).toBeFocused();

    await addPageButton.focus();
    await page.keyboard.press('Enter');
    await PageWizard.ensurePageWizardModalVisible(backend.modal);
    const modalContent = await backend.modal.getModalContent();
    await PageWizard.goToNextStep(modalContent);
    await PageWizard.goToNextStep(modalContent);
    await PageWizard.fillTitleField(pageTitle, modalContent);
    await PageWizard.goToNextStep(modalContent);
    await PageWizard.getButton(modalContent, PageWizard.CONFIRM_BUTTON_TEXT).click();
    await PageWizard.isReady(modalContent);
    await PageWizard.getButton(modalContent, PageWizard.FINISH_BUTTON_TEXT).click();
    await backend.pageTree.isReady();

    const newPageNode = backend.pageTree.tree.locator('[role="treeitem"]', { hasText: pageTitle });
    await expect(newPageNode).toHaveCount(1);
    await expect(backend.pageTree.tree.locator('[role="treeitem"][tabindex="0"]')).toHaveCount(1);
    await expect(rootNode).toHaveAttribute('tabindex', '0');

    await backend.pageTree.dragDeletePage(newPageNode);
  });
});
