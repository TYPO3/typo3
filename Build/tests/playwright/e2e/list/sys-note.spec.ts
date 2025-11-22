import {test, expect} from '../../fixtures/setup-fixtures';

test.beforeEach(async ({backend}) => {
  await backend.gotoModule('web_list');
});

test('System note can be created and edited from list module', async ({backend}) => {
  await test.step('Create a new system note', async () => {
    await backend.pageTree.open('styleguide TCA demo');
    await backend.contentFrame.getByRole('button', {name: 'Create new record'}).click();
    await backend.formEngine.formEngineLoaded()
    await backend.contentFrame.getByRole('link', {name: 'Internal note'}).click();

    await expect(backend.contentFrame.locator('h1')).toContainText('Create new Internal note on page "styleguide TCA demo"');
    await backend.contentFrame.getByText('[subject]').fill('new sys_note');
    await backend.formEngine.save();
    await backend.formEngine.close();
  });

  await test.step('Verify the created system note is listed', async () => {
    await expect(backend.contentFrame.getByRole('heading', { name: 'Internal notes'})).toBeAttached();
    await expect(backend.contentFrame.locator('.note-body').first()).toContainText('new sys_note')
  })

  await test.step('Edit the created system note', async () => {
    await backend.contentFrame.getByRole('link', { name: 'Edit note record' }).first().click()

    await expect(backend.contentFrame.locator('h1')).toContainText('Edit Internal note "new sys_note" on page "styleguide TCA demo"');
    await backend.contentFrame.getByText('[subject]').fill('edited sys_note');
    await backend.formEngine.save();
    await backend.formEngine.close();

    await expect(backend.contentFrame.locator('.note-body').first()).toContainText('edited sys_note')
  });
});
