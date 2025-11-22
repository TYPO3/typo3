import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('page_preview');
  await backend.pageTree.open('styleguide frontend demo');
});

test('Shows page preview module', async ({ backend }) => {
  await expect(backend.contentFrame
    .locator('iframe[title="View selected page in different screen resolutions"]')
    .contentFrame()
    .getByRole('heading', { name: 'styleguide frontend demo' })
  ).toContainText('styleguide frontend demo');

  await expect(backend.contentFrame
    .locator('iframe[title="View selected page in different screen resolutions"]')
    .contentFrame()
    .getByText('This is the generated frontend for the Styleguide Extension.')
  ).toBeVisible();
});

test('Can change preview window size', async ({ backend }) => {
  const entries = [
    { name: 'Tablet landscape', width: '1024', height: '768' },
    { name: 'Tablet portrait', width: '768', height: '1024' },
  ];

  for (const entry of entries) {
    await test.step(`Check preset window size: ${entry.name}`, async () => {
      await backend.contentFrame
        .locator('div.viewpage-topbar-preset')
        .click();
      await backend.contentFrame.locator('.dropdown-menu').getByText(entry.name).click();
      await expect(backend.contentFrame.locator('input[name="width"]')).toHaveValue(entry.width);
      await expect(backend.contentFrame.locator('input[name="height"]')).toHaveValue(entry.height);
    });
  }
});
