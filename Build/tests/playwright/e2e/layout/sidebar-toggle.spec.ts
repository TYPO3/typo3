import { expect, test } from '../../fixtures/setup-fixtures';
import { ViewportSize } from '../../fixtures/backend-page';

/**
 * Tests for the sidebar toggle button in the topbar.
 *
 * On large screens (≥992px): sidebar is always visible, toggle expands/collapses it.
 * On small screens (<992px): sidebar is hidden, toggle opens flyout overlay.
 */
test.describe('Sidebar Toggle', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('web_layout');
    await backend.sidebar.expand();
  });

  test('sidebar toggle from desktop to mobile', async ({ backend }) => {
    // Ensure desktop viewport
    await backend.setViewportSize(ViewportSize.Desktop);

    // Desktop: sidebar is visible
    await expect(backend.sidebar.element).toBeVisible();

    // Collapse the sidebar
    await backend.sidebar.toggle.click();

    // Sidebar should still be visible (just collapsed)
    await expect(backend.sidebar.element).toBeVisible();

    // Shrink to mobile
    await backend.setViewportSize(ViewportSize.Mobile);

    // On mobile, sidebar is hidden
    await expect(backend.sidebar.element).not.toBeVisible();

    // Open the sidebar flyout
    await backend.sidebar.toggle.click();

    // Sidebar appears as flyout overlay
    await expect(backend.sidebar.element).toBeVisible();
  });

  test('sidebar toggle from mobile to desktop', async ({ backend }) => {
    // Shrink to mobile
    await backend.setViewportSize(ViewportSize.Mobile);

    // On mobile, sidebar is hidden
    await expect(backend.sidebar.element).not.toBeVisible();

    // Open the sidebar flyout
    await backend.sidebar.toggle.click();

    // Sidebar appears as flyout
    await expect(backend.sidebar.element).toBeVisible();

    // Close the sidebar flyout
    await backend.sidebar.toggle.click();

    // Flyout should be closed
    await expect(backend.sidebar.element).not.toBeVisible();

    // Enlarge to desktop
    await backend.setViewportSize(ViewportSize.Desktop);

    // Sidebar should be visible
    await expect(backend.sidebar.element).toBeVisible();
  });
});
