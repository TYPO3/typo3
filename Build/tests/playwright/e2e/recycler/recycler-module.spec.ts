import { test, expect } from '../../fixtures/setup-fixtures';
import { PageWizard } from '../../fixtures/page-wizard';

test('Delete page and check recycler', async ({ page, backend }) => {
  const newPageTitle = `Dummy ${backend.getUnixTimestamp()}-styleguide TCA demo`;
  const newSysNoteSubject = `Dummy sys note ${backend.getUnixTimestamp()}`;

  await test.step('Add page for recycler test', async () => {
    await page.goto('module/web/layout');
    await backend.pageTree.isReady();
    const targetNode = backend.pageTree.tree.locator('.node', { hasText: 'styleguide TCA demo' });
    await backend.pageTree.dragNewPageTo(targetNode);
    const pageWizard = new PageWizard(page);
    await pageWizard.createDefaultPageAfterDrag(backend.modal, newPageTitle);
    await backend.gotoModule('records');

    await test.step('Add sys note on new page', async () => {
      await backend.pageTree.open('styleguide TCA demo', newPageTitle);
      const formEngineLoaded = await backend.formEngine.formEngineLoaded();
      await backend.contentFrame.getByRole('button', { name: 'Create new record' }).click();
      await formEngineLoaded();
      await backend.contentFrame.getByRole('link', { name: 'Internal note' }).click();

      await expect(backend.contentFrame.locator('h1')).toContainText('Create new Internal note');
      await backend.contentFrame.getByText('[subject]').fill(newSysNoteSubject);
      await backend.formEngine.save();
      await backend.formEngine.close();
    });

    await test.step('Delete page', async () => {
      await backend.pageTree.open('styleguide TCA demo', newPageTitle);
      const formEngineLoaded = await backend.formEngine.formEngineLoaded();
      backend.contentFrame.getByRole('button', { name: 'Edit page properties' }).click();
      await formEngineLoaded();
      const deleteButton = backend.contentFrame.getByRole('button', { name: 'Delete' });
      await backend.modal.open(deleteButton);

      const modal = page.locator('typo3-backend-modal > dialog');
      await expect(modal).toBeVisible();
      await expect(modal).toContainText('Delete this record?');

      await backend.modal.click({ name: 'yes' });
    });

    await test.step('Go to recycler module and check for deleted page', async () => {
      await backend.gotoModule('recycler');
      await backend.pageTree.open('styleguide TCA demo');

      await expect(backend.contentFrame.locator('h1')).toContainText('Recycler');
      await backend.contentFrame.getByLabel('Depth').selectOption('Infinite');
      await expect(backend.contentFrame.getByLabel('Depth')).toContainText('Infinite');

      const searchBox = backend.contentFrame.getByRole('searchbox', { name: 'Search term' });
      await searchBox.fill(newPageTitle);
      await backend.contentFrame.getByRole('button', { name: 'Search' }).click();

      await expect(backend.contentFrame.getByRole('cell', { name: newPageTitle }).first()).toBeVisible();
    });

    await test.step('Go to recycler module and check for sys note', async () => {
      await backend.gotoModule('recycler');
      await backend.pageTree.open('styleguide TCA demo');

      await expect(backend.contentFrame.locator('h1')).toContainText('Recycler');
      await backend.contentFrame.getByLabel('Depth').selectOption('Infinite');
      await expect(backend.contentFrame.getByLabel('Depth')).toContainText('Infinite');

      const searchBox = backend.contentFrame.getByRole('searchbox', { name: 'Search term' });
      await searchBox.fill(newSysNoteSubject);
      await backend.contentFrame.getByRole('button', { name: 'Search' }).click();

      await expect(backend.contentFrame.getByRole('cell', { name: newSysNoteSubject }).first()).toBeVisible();
    });

    await test.step('Restore deleted page and the content', async () => {
      const searchBox = backend.contentFrame.getByRole('searchbox', { name: 'Search term' });
      await searchBox.fill(newPageTitle);
      const searchResponse = page.waitForResponse(response =>
        response.url().includes('getDeletedRecords') && response.status() === 200
      );
      await backend.contentFrame.getByRole('button', { name: 'Search' }).click();
      await searchResponse;
      await expect(backend.contentFrame.getByRole('cell', { name: newPageTitle }).first()).toBeVisible();

      // Target the restore button on the pages row specifically,
      // since the recursive checkbox only appears for page records.
      const pageRow = backend.contentFrame.locator('tr[data-table="pages"]').first();
      await expect(pageRow).toBeVisible();
      const restoreButton = pageRow.getByRole('button', { name: 'Restore' });
      await backend.modal.open(restoreButton);

      const modal = page.locator('typo3-backend-modal > dialog');
      await expect(modal).toBeVisible();
      await expect(modal).toContainText('Restore records');
      const restoreCheckbox = modal.getByRole('checkbox', { name: 'Restore content and subpages recursively' });
      await expect(restoreCheckbox).toBeVisible();
      await restoreCheckbox.click();

      await backend.modal.click({ text: 'Restore' });
    });

    await test.step('Verify restored page and content', async () => {
      await backend.pageTree.refresh();
      await backend.gotoModule('records');
      await backend.pageTree.open('styleguide TCA demo', newPageTitle);
      await backend.contentFrame.locator(`a:has-text("${newSysNoteSubject}")`).click();
      await expect(backend.contentFrame.locator('h1')).toContainText(newSysNoteSubject);
      await backend.formEngine.close();
    });
  });
});
