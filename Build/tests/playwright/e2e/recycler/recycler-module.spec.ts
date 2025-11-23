import { test, expect } from '../../fixtures/setup-fixtures';

test('Delete page and check recycler', async ({ page, backend }) => {
  const newPageTitle = `Dummy ${backend.getUnixTimestamp()}-styleguide TCA demo`;
  const newSysNoteSubject = `Dummy sys note ${backend.getUnixTimestamp()}`;

  await test.step('Add page for recycler test', async () => {
    await backend.gotoModule('web_list');
    await backend.pageTree.open('styleguide TCA demo');
    await backend.contentFrame.getByRole('button', { name: 'Create new record' }).click();
    await backend.formEngine.formEngineLoaded();
    await backend.contentFrame.getByRole('button', { name: 'Page (inside)' }).click();
    await backend.contentFrame.getByRole('link', { name: 'Standard' }).click();

    await expect(backend.contentFrame.locator('h1')).toContainText('Create new Page');
    await backend.contentFrame.getByText('[title]').fill(newPageTitle);
    await backend.formEngine.save();
    await backend.formEngine.close();
    await backend.pageTree.refresh();

    await test.step('Add sys note on new page', async () => {
      await backend.pageTree.open('styleguide TCA demo', newPageTitle);
      await backend.contentFrame.getByRole('button', { name: 'Create new record' }).click();
      await backend.formEngine.formEngineLoaded();
      await backend.contentFrame.getByRole('link', { name: 'Internal note' }).click();

      await expect(backend.contentFrame.locator('h1')).toContainText(`Create new Internal note on page "${newPageTitle}"`);
      await backend.contentFrame.getByText('[subject]').fill(newSysNoteSubject);
      await backend.formEngine.save();
      await backend.formEngine.close();
    });

    await test.step('Delete page', async () => {
      await backend.pageTree.open('styleguide TCA demo', newPageTitle);
      await backend.contentFrame.getByRole('button', { name: 'Edit page properties' }).click();
      await backend.formEngine.formEngineLoaded();
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

      const searchBox = backend.contentFrame.getByRole('textbox', { name: 'Search term' });
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

      const searchBox = backend.contentFrame.getByRole('textbox', { name: 'Search term' });
      await searchBox.fill(newSysNoteSubject);
      await backend.contentFrame.getByRole('button', { name: 'Search' }).click();

      await expect(backend.contentFrame.getByRole('cell', { name: newSysNoteSubject }).first()).toBeVisible();
    });

    await test.step('Restore deleted page and the content', async () => {
      const restoreButton = backend.contentFrame.getByRole('button', { name: 'Restore' }).first();
      await backend.modal.open(restoreButton);

      const modal = page.locator('typo3-backend-modal > dialog');
      await expect(modal).toBeVisible();
      await expect(modal).toContainText('Restore records');
      await modal.getByRole('checkbox', { name: 'Restore content and subpages' }).click();

      await backend.modal.click({ text: 'Restore' });
    });

    await test.step('Verify restored page and content', async () => {
      await backend.pageTree.refresh();
      await backend.gotoModule('web_list');
      await backend.pageTree.open('styleguide TCA demo', newPageTitle);
      await backend.contentFrame.locator(`a:has-text("${newSysNoteSubject}")`).click();
      await expect(backend.contentFrame.locator('h1')).toContainText(`Edit Internal note "${newSysNoteSubject}" on page "${newPageTitle}"`);
      await backend.formEngine.close();
    });
  });
});
