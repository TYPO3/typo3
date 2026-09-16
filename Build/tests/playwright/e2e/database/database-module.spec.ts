import { test, expect } from '../../fixtures/setup-fixtures';

test.describe('Database Module', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('system_database');
  });

  test('Full search', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('h1')).toHaveText('Database');

    await backend.docHeader.selectInDropDown('Module overview', 'Raw search');
    await expect(contentFrame.locator('h1')).toHaveText('Raw search');

    await contentFrame.locator('input[name="SET[sword]"]').fill('styleguide demo group 1');
    await contentFrame.getByRole('button', { name: 'Search All Records' }).click();

    await expect(contentFrame.locator('h2')).toHaveText('Result');
    await expect(contentFrame.locator('.table').getByText('styleguide demo group 1')).toBeVisible();

    const tableRows = contentFrame.locator('.table').getByText('styleguide demo group 2');
    await expect(tableRows).toHaveCount(0);

    const modalContent = await backend.modal.open(contentFrame.locator('a[data-dispatch-args-list]').first());
    await expect(modalContent.locator('.card-title')).toContainText('styleguide demo group 1');
  });
});
