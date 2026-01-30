import { test, expect } from '../../fixtures/setup-fixtures';

const topBarBookmarkSelector = '#typo3-cms-backend-backend-toolbaritems-bookmarktoolbaritem';

test.describe.serial('Bookmark functionality', () => {
  test.beforeEach(async ({ page, backend }) => {
    await page.goto('module/web/layout');
    await backend.gotoModule('scheduler');
  });

  test('bookmark list is initially empty', async ({ page }) => {
    await clearAllBookmarks(page);
    const dropdown = await openBookmarkDropdown(page);
    await expect(dropdown).toContainText('You have not added any bookmarks yet');
  });

  test('adding a bookmark adds item to the list', async ({ page, backend }) => {
    const contentFrame = backend.contentFrame;
    await contentFrame.locator('button[title="Share"]').click();
    const shareDropdown = contentFrame.locator('.module-docheader-column .dropdown-menu');
    await expect(shareDropdown).toBeVisible();

    const bookmarkButton = shareDropdown.locator('typo3-backend-bookmark-button');
    await expect(bookmarkButton).toContainText('Create bookmark');
    await bookmarkButton.click();

    const dropdown = await openBookmarkDropdown(page);
    await expect(dropdown).toContainText('Scheduled tasks');
  });

  test('bookmark item links to target', async ({ page, backend }) => {
    await backend.gotoModule('dashboard');
    const dropdown = await openBookmarkDropdown(page);
    await dropdown.getByText('Scheduled tasks').click();
    await expect(backend.contentFrame.locator('h1')).toContainText('Scheduled tasks');
  });

  test('edit bookmark item works', async ({ page }) => {
    const { modal, bookmarkManager } = await openBookmarkManager(page);

    const bookmarkRow = bookmarkManager.locator('tr', { hasText: 'Scheduled tasks' });
    await bookmarkRow.locator('button[title="Edit bookmark"]').click();

    await bookmarkManager.locator('#bookmark-title').fill('Scheduled tasks renamed');
    await bookmarkManager.locator('button[type="submit"]').click();
    await modal.locator('button.t3js-modal-close').click();

    const dropdown = await openBookmarkDropdown(page);
    await expect(dropdown).toContainText('Scheduled tasks renamed');
  });

  test('delete bookmark works', async ({ page }) => {
    const { modal, bookmarkManager } = await openBookmarkManager(page);

    const bookmarkRow = bookmarkManager.locator('tr', { hasText: 'Scheduled tasks' });
    await bookmarkRow.locator('button[title="Edit bookmark"]').click();
    await bookmarkManager.locator('button', { hasText: 'Delete' }).click();

    const confirmModal = page.locator('typo3-backend-modal[modaltitle="Delete bookmark"] > dialog');
    await expect(confirmModal).toBeVisible();
    await confirmModal.locator('button[name="delete"]').click();
    await modal.locator('button.t3js-modal-close').click();

    const dropdown = await openBookmarkDropdown(page);
    await expect(dropdown).not.toContainText('Scheduled tasks');
  });
});

async function openBookmarkDropdown(page: any): Promise<any> {
  await page.locator(`${topBarBookmarkSelector} .dropdown-toggle`).click();
  return page.locator(`${topBarBookmarkSelector} .dropdown-menu`);
}

async function openBookmarkManager(page: any): Promise<{ modal: any; bookmarkManager: any }> {
  await openBookmarkDropdown(page);
  await page.locator(`${topBarBookmarkSelector} typo3-backend-bookmark-manager-button`).click();
  const modal = page.locator('typo3-backend-modal[modaltitle="Manage bookmarks"] > dialog');
  await expect(modal).toBeVisible();
  const bookmarkManager = modal.locator('typo3-backend-bookmark-manager-content');
  return { modal, bookmarkManager };
}

async function clearAllBookmarks(page: any): Promise<void> {
  const dropdown = await openBookmarkDropdown(page);
  const dropdownText = await dropdown.textContent();
  if (dropdownText?.includes('You have not added any bookmarks yet')) {
    await page.keyboard.press('Escape');
    return;
  }

  await dropdown.locator('typo3-backend-bookmark-manager-button').click();
  const modal = page.locator('typo3-backend-modal[modaltitle="Manage bookmarks"] > dialog');
  await expect(modal).toBeVisible();
  const bookmarkManager = modal.locator('typo3-backend-bookmark-manager-content');

  await bookmarkManager.getByText('Select all').click();
  await bookmarkManager.locator('button', { hasText: 'Delete' }).click();

  const confirmModal = page.locator('typo3-backend-modal[modaltitle="Delete bookmarks"] > dialog');
  await expect(confirmModal).toBeVisible();
  await confirmModal.locator('button[name="delete"]').click();

  await expect(bookmarkManager).toContainText('No bookmarks yet');
  await modal.locator('button.t3js-modal-close').click();
}

