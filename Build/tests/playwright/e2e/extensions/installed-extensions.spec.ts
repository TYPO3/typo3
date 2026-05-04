import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async function({ page, backend }) {
  await backend.gotoModule('extensionmanager');
  const modal = page.locator('typo3-backend-modal > dialog');

  try {
    await expect(modal).toBeVisible({ timeout: 5000 });
  } catch {
    // No modal appeared because sudo mode already verified
    return;
  }

  await expect(modal).toContainText('Verify with user password');
  await modal.locator('input[type="password"]').fill('password');
  await backend.modal.click({ name: 'verify' });
});

test('Filter installed extensions', async ({ backend }) => {
  await test.step('Check amount of installed extensions', async () => {
    await expect(backend.contentFrame.locator('h1')).toContainText('Installed Extensions');
    expect(await backend.contentFrame.locator('#typo3-extension-list tbody tr[role="row"]:not(.hidden)').count()).toBeGreaterThanOrEqual(10);
  });

  await test.step('Filter for "backend" extension', async () => {
    await backend.contentFrame.getByRole('searchbox', { name: 'Search term' }).pressSequentially('backend');
    await expect(backend.contentFrame.locator('tr#core')).not.toBeVisible();
    expect(await backend.contentFrame.locator('#typo3-extension-list tbody tr[role="row"]:not(.hidden)').count()).toBe(3);
    expect(await backend.contentFrame.locator('#typo3-extension-list tbody tr[role="row"] td').filter({ hasText: 'backend' }).count()).toBeGreaterThan(0);
  });

  await test.step('Reset filter and check amount of installed extensions', async () => {
    const searchBox = backend.contentFrame.getByRole('searchbox', { name: 'Search term' });
    await searchBox.clear();
    await searchBox.dispatchEvent('search');
    expect(await backend.contentFrame.locator('#typo3-extension-list tbody tr[role="row"]:not(.hidden)').count()).toBeGreaterThanOrEqual(10);
  });
});
