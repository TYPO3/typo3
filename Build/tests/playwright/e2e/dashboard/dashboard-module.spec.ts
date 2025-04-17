import { test, expect } from '../../fixtures/setup-fixtures';
import {FrameLocator, Locator} from "@playwright/test";
import {BackendPage} from "../../fixtures/backend-page";

const customDashboardTitle = 'My Custom Dashboard';

test.beforeEach(async ({ page, backend }) => {
  await page.goto('module/web/layout');
  await backend.gotoModule('dashboard');
});

test('See initial dashboard and widgets', async ({backend}) => {
  const contentFrame = backend.contentFrame;

  await expect(contentFrame.locator('.dashboard-tab--active')).toContainText('My dashboard');
  await expect(widgetTitle(contentFrame, 'docGettingStarted')).toContainText('Getting Started with TYPO3');
  await expect(widgetTitle(contentFrame, 't3information')).toContainText('About TYPO3');
});

test('Create custom dashboard and widgets', async ({backend}) => {
  const contentFrame = backend.contentFrame;

  // Create Dashboard
  let addModal = await open(backend, 'actions-plus');

  await addModal.locator('#dashboard-form-add-title').fill(customDashboardTitle);
  await addModal.locator('label[for="dashboard-form-add-preset-empty"]').click();
  await backend.modal.click('save');
  await expect(contentFrame.locator('.dashboard-tab--active')).toContainText(customDashboardTitle);

  // Add widget
  const addWidget = contentFrame.locator('.dashboard-add-item .btn-dashboard-add-widget').first();
  let widgetModalContent = await backend.modal.open(addWidget);

  await widgetModalContent.locator('[data-identifier="systemInfo"]').click();
  await widgetModalContent.locator('[data-identifier="typeOfUsers"]').click();
  await expect(widgetTitle(contentFrame, 'typeOfUsers')).toContainText('Type of backend users');
});

test('Delete dashboard and widgets', async ({backend}) => {
  const contentFrame = backend.contentFrame;

  // Delete widget
  let customDashboardTab = contentFrame.locator('.dashboard-tabs', {hasText: customDashboardTitle});
  await expect(customDashboardTab).toBeEnabled();
  await customDashboardTab.first().click();
  await expect(widgetTitle(contentFrame, 'typeOfUsers')).toBeVisible();

  let modalButton = contentFrame.locator('div[data-widget-key="typeOfUsers"] .widget-action-remove');
  await backend.modal.open(modalButton);
  await backend.modal.click('delete');

  await expect(contentFrame.getByRole('button', { name: 'Remove Widget' })).toBeHidden();
  await expect(contentFrame.locator('.dashboard-empty-content')).toBeVisible();

  // Delete custom dashboard
  let deleteModal = await open(backend, 'actions-delete');
  await backend.modal.click('delete');
  await expect(deleteModal).not.toBeVisible();
  await expect(contentFrame.locator('.dashboard-tab--active', {hasText: customDashboardTitle})).not.toBeVisible();
});

test('Create, rename and delete dashboard',async ({backend}) => {
  let addModal = await open(backend, 'actions-plus');
  let titleBeforeRename = 'Custom Dashboard mistake';
  let titleAfterRename = 'Custom Dashboard correct';

  // Add dashboard
  await addModal.locator('[name="title"]').fill(titleBeforeRename);
  await addModal.locator('label[for="dashboard-form-add-preset-empty"]').click();
  await backend.modal.click('save');
  await expect(backend.contentFrame.locator('.dashboard-tab--active')).toContainText(titleBeforeRename);

  // Rename dashboard
  let renameModal = await open(backend, 'actions-cog');
  await renameModal.locator('[name="title"]').fill(titleAfterRename);
  await backend.modal.click('save');
  await expect(backend.contentFrame.locator('.dashboard-tab--active')).toContainText(titleAfterRename);

  // Delete dashboard
  let deleteModal = await open(backend, 'actions-delete');
  await backend.modal.click('delete');
  await expect(deleteModal).not.toBeVisible();
  await expect(backend.contentFrame.locator('.dashboard-tab--active', {hasText: titleAfterRename})).not.toBeVisible();
})

function widgetTitle(contentFrame: FrameLocator, key: string): Locator {
  return contentFrame.locator(`[data-widget-key="${key}"] .widget-title`);
}

async function open(backend: BackendPage, identifier: string): Promise<Locator> {
  let button = backend.contentFrame.locator(`.dashboard-header [identifier="${identifier}"]`);

  return await backend.modal.open(button);
}
