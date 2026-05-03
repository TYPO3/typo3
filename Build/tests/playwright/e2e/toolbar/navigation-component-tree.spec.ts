import { test, expect } from '../../fixtures/setup-fixtures';

const navigationContainer = 'typo3-backend-content-navigation[identifier="backend"]';
const navigationExpanded = `${navigationContainer}:not([navigation-collapsed])`;
const navigationCollapsed = `${navigationContainer}[navigation-collapsed]`;
const navigationSlot = `${navigationContainer} [slot="navigation"]`;

test('navigation tree expands and collapses for the page module', async ({ page, backend }) => {
  await page.goto('module/web/layout');
  await expect(page.locator(navigationExpanded)).toBeVisible();
  await expect(page.locator(navigationSlot)).toContainText('New TYPO3 site');

  await backend.pageTree.toolbar.locator('typo3-backend-content-navigation-toggle[action="collapse"]').click();
  await expect(page.locator(navigationCollapsed)).toBeVisible();

  await backend.contentFrame.locator('typo3-backend-content-navigation-toggle[action="expand"]').click();
  await expect(page.locator(navigationExpanded)).toBeVisible();
  await expect(page.locator(navigationSlot)).toContainText('New TYPO3 site');
});

test('navigation tree expands and collapses for the file module', async ({ page, backend }) => {
  await backend.gotoModule('media_management');
  // Make sure 'fileadmin' is selected since other tests may have clicked around the file tree.
  await backend.fileTree.open('fileadmin');

  await expect(page.locator(navigationExpanded)).toBeVisible();
  await expect(page.locator(navigationSlot)).toContainText('fileadmin');

  await backend.fileTree.toolbar.locator('typo3-backend-content-navigation-toggle[action="collapse"]').click();
  await expect(page.locator(navigationCollapsed)).toBeVisible();

  await backend.contentFrame.locator('typo3-backend-content-navigation-toggle[action="expand"]').click();
  await expect(page.locator(navigationExpanded)).toBeVisible();
  await expect(page.locator(navigationSlot)).toContainText('fileadmin');
});
