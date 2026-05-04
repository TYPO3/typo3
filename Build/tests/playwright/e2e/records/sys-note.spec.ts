import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async ({ backend } ) => {
  await backend.gotoModule('records');
});

test('System note can be created and edited from records module', async ({ backend }) => {
  await test.step('Create a new system note', async () => {
    await backend.pageTree.open('styleguide TCA demo');
    await backend.contentFrame.getByRole('button', { name: 'Create new record' }).click();
    const formEngineReady = await backend.formEngine.formEngineLoaded();
    await backend.contentFrame.getByRole('link', { name: 'Internal note' }).click();
    await formEngineReady();

    await expect(backend.contentFrame.locator('h1')).toContainText('Create new Internal note');
    await backend.contentFrame.getByText('[subject]').fill('new sys_note');
    await backend.formEngine.save();
    await backend.formEngine.close();
  });

  await test.step('Verify the created system note is listed', async () => {
    await expect(backend.contentFrame.getByRole('heading', { name: 'Internal notes' })).toBeAttached();
    await expect(backend.contentFrame.locator('.note-body', { hasText: 'new sys_note' })).toBeVisible();
  });

  await test.step('Edit the created system note', async () => {
    const loaded = await backend.moduleLoaded('records');
    backend.contentFrame.locator('.note', { hasText: 'new sys_note' })
      .getByRole('link', { name: 'Edit note record' }).click();
    await loaded();

    await expect(backend.contentFrame.locator('h1')).toContainText('new sys_note');
    await backend.contentFrame.getByText('[subject]').fill('edited sys_note');
    await backend.formEngine.save();
    await backend.formEngine.close();

    await expect(backend.contentFrame.locator('.note-body', { hasText: 'edited sys_note' })).toBeVisible();
  });
});
