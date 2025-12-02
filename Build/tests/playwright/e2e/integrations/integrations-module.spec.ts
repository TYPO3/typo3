import { test, expect } from '../../fixtures/setup-fixtures';
test('See integration modules', async ({ backend }) => {
  await backend.gotoModule('integrations');
  await expect(backend.contentFrame.locator('h1')).toContainText('Integrations');
  const menuItems = [
    {
      title: 'Reactions',
      shortDescription: 'Manage incoming HTTP webhooks to external system',
    },
    {
      title: 'Webhooks',
      shortDescription: 'Manage outgoing HTTP webhooks to external system',
    },
  ];
  for (const menuItem of menuItems) {
    await backend.gotoModule('integrations');
    await test.step(`Status module "${menuItem.title}" is available`, async () => {
      await expect(backend.contentFrame.getByRole('heading', { name: menuItem.title })).toBeVisible();
      await expect(backend.contentFrame.getByText(menuItem.shortDescription)).toBeVisible();
      await expect(backend.contentFrame.locator(`[aria-label='Open ${menuItem.title} module']`)).toBeVisible();
      await backend.contentFrame.locator(`[aria-label='Open ${menuItem.title} module']`).click();
      await expect(backend.contentFrame.locator('h1')).toContainText(menuItem.title, { ignoreCase: true });
    });
  }
});
