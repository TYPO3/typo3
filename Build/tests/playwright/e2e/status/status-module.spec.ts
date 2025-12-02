import { test, expect } from '../../fixtures/setup-fixtures';

test('See status modules', async ({ backend }) => {
  await backend.gotoModule('content_status');
  await expect(backend.contentFrame.locator('h1')).toContainText('Status');

  const menuItems = [
    {
      title: 'Pagetree Overview',
      description: 'View page records and settings in a tree structure with detailed metadata.',
    },
    {
      title: 'Localization Overview',
      description: 'Check translation status and manage localized content for pages.',
    },
  ];

  for (const menuItem of menuItems) {
    await backend.gotoModule('content_status');

    await test.step(`Status module "${menuItem.title}" is available`, async () => {
      await expect(backend.contentFrame.getByRole('heading', { name: menuItem.title })).toBeVisible();
      await expect(backend.contentFrame.getByText(menuItem.description)).toBeVisible();
      await expect(backend.contentFrame.locator(`[aria-label='Open ${menuItem.title} module']`)).toBeVisible();

      await backend.contentFrame.locator(`[aria-label='Open ${menuItem.title} module']`).click();
      await expect(backend.contentFrame.locator('h1')).toContainText(menuItem.title, { ignoreCase: true });
    });
  }
});
