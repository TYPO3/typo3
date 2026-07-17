import { test, expect } from '../../fixtures/setup-fixtures';
import { FrameLocator, Locator } from '@playwright/test';
import { BackendPage } from '../../fixtures/backend-page';

const customDashboardTitle = 'My Custom Dashboard';

test.beforeEach(async ({ page, backend }) => {
  await page.goto('module/web/layout');
  await backend.gotoModule('dashboard');
});

test('See initial dashboard and widgets', async ({ backend }) => {
  const contentFrame = backend.contentFrame;

  await expect(contentFrame.locator('.dashboard-tab--active')).toContainText('My dashboard');
  await expect(widgetTitle(contentFrame, 'docGettingStarted')).toContainText('Getting Started with TYPO3');
  await expect(widgetTitle(contentFrame, 't3information')).toContainText('About TYPO3');
});

test('Create custom dashboard and widgets', async ({ backend }) => {
  const contentFrame = backend.contentFrame;

  // Create Dashboard
  await createDashboard(backend, customDashboardTitle);
  await expect(contentFrame.locator('.dashboard-tab--active')).toContainText(customDashboardTitle);

  // Add widget
  const addWidget = contentFrame.locator('.dashboard-add-item .btn-dashboard-add-widget').first();
  const widgetModalContent = await backend.modal.open(addWidget);

  await widgetModalContent.locator('[data-identifier="systemInfo"]').click();
  await widgetModalContent.locator('[data-identifier="typeOfUsers"]').click();
  await expect(widgetTitle(contentFrame, 'typeOfUsers')).toContainText('Type of backend users');
});

test('Delete dashboard and widgets', async ({ backend }) => {
  const contentFrame = backend.contentFrame;

  // Delete widget
  const customDashboardTab = contentFrame.locator('.dashboard-tabs', { hasText: customDashboardTitle });
  await expect(customDashboardTab).toBeEnabled();
  await customDashboardTab.first().click();
  await expect(widgetTitle(contentFrame, 'typeOfUsers')).toBeVisible();

  const modalButton = contentFrame.locator('div[data-widget-key="typeOfUsers"] .widget-action-remove');
  await backend.modal.open(modalButton);
  await backend.modal.click({ name: 'delete' });

  await expect(contentFrame.getByRole('button', { name: 'Remove Widget' })).toBeHidden();
  await expect(contentFrame.locator('.dashboard-empty-content')).toBeVisible();

  // Delete custom dashboard
  const deleteModal = await open(backend, 'actions-delete');
  await backend.modal.click({ name: 'delete' });
  await expect(deleteModal).not.toBeVisible();
  await expect(contentFrame.locator('.dashboard-tab--active', { hasText: customDashboardTitle })).not.toBeVisible();
});

test('Create, rename and delete dashboard',async ({ backend }) => {
  const titleBeforeRename = 'Custom Dashboard mistake';
  const titleAfterRename = 'Custom Dashboard correct';

  // Add dashboard
  await createDashboard(backend, titleBeforeRename);
  await expect(backend.contentFrame.locator('.dashboard-tab--active')).toContainText(titleBeforeRename);

  // Rename dashboard
  const renameModal = await open(backend, 'actions-cog');
  await renameModal.locator('[name="title"]').fill(titleAfterRename);
  await backend.modal.click({ name: 'save' });
  await expect(backend.contentFrame.locator('.dashboard-tab--active')).toContainText(titleAfterRename);

  // Delete dashboard
  const deleteModal = await open(backend, 'actions-delete');
  await backend.modal.click({ name: 'delete' });
  await expect(deleteModal).not.toBeVisible();
  await expect(backend.contentFrame.locator('.dashboard-tab--active', { hasText: titleAfterRename })).not.toBeVisible();
});

function widgetTitle(contentFrame: FrameLocator, key: string): Locator {
  return contentFrame.locator(`[data-widget-key="${key}"] .widget-title`);
}

/**
 * Walk through the multi-step dashboard wizard: pick the "empty" preset,
 * enter a title, confirm, and finish. The forward button is only clicked
 * once it is no longer disabled, which also waits for the async finisher.
 */
async function createDashboard(backend: BackendPage, title: string): Promise<void> {
  const addModal = await open(backend, 'actions-plus');
  const forward = () => addModal.locator('.wizard-actions .btn-primary:not(.disabled)');

  // Step: preset
  await addModal.locator('#preset-empty').check();
  await forward().click();

  // Step: title
  await addModal.locator('#dashboard-wizard-title-input').fill(title);
  await forward().click();

  // Step: finisher — creates the dashboard; "Finish" reloads the module
  await forward().click();
}

async function open(backend: BackendPage, identifier: string): Promise<Locator> {
  const button = backend.contentFrame.locator(`.dashboard-header [identifier="${identifier}"]`);

  return await backend.modal.open(button);
}
