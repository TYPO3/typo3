import { test, expect } from '../../fixtures/setup-fixtures';
import type { Locator, Page } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

const wizardButtonSelector = 'typo3-scheduler-new-task-wizard-button';

async function openSchedulerModule({ page, backend }: { page: Page; backend: BackendPage }): Promise<void> {
  await page.goto('module/web/layout');
  const moduleLoaded = await backend.moduleLoaded('scheduler');
  await page.locator('a[data-modulemenu-identifier="scheduler"]').click();
  await moduleLoaded();
}

test.describe.serial('Scheduler tasks - System Status Update lifecycle', () => {
  test.beforeEach(openSchedulerModule);

  test('a scheduler task can be created via the wizard', async ({ page, backend }) => {
    await expect(backend.contentFrame.locator('body')).toContainText('No tasks found');
    await expect(backend.contentFrame.locator('body')).toContainText(
      'There are currently no configured tasks found. You can create a new one.'
    );

    await backend.contentFrame.locator(`.module-docheader ${wizardButtonSelector}`).click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    const wizard = dialog.locator('typo3-backend-new-record-wizard');
    await wizardItemClick(wizard, 'reports');
    await wizardItemClick(wizard, 'reports_TYPO3_CMS_Reports_Task_SystemStatusUpdateTask');

    await expect(backend.formEngine.container).toBeVisible();
    await backend.contentFrame
      .locator('textarea[data-formengine-input-name*="data[tx_scheduler_task]"][data-formengine-input-name*="[tx_reports_notification_email]"]')
      .fill('test@local.typo3.org');

    await backend.formEngine.save();
    await backend.formEngine.close();

    await expect(backend.contentFrame.locator('body')).toContainText('System Status Update');
  });

  test('the task can be executed once', async ({ backend }) => {
    await backend.contentFrame.locator('button[name="action[execute]"]').click();
    await expect(backend.contentFrame.locator('body')).toContainText('Task "System Status Update [reports]" with uid');
    await expect(
      backend.contentFrame.locator('[data-module-name="scheduler"] tr[data-task-disabled="true"]')
    ).toBeVisible();
    await expect(backend.contentFrame.locator('body')).toContainText('disabled');
  });

  test('the task email can be edited', async ({ backend }) => {
    await backend.contentFrame.locator('a[title*="Edit"]').first().click();
    await expect(backend.contentFrame.locator('h1')).toContainText('System Status Update');

    const emailField = backend.contentFrame.locator(
      'textarea[data-formengine-input-name*="data[tx_scheduler_task]"][data-formengine-input-name*="[tx_reports_notification_email]"]'
    );
    await expect(emailField).toHaveValue('test@local.typo3.org');
    await emailField.fill('foo@local.typo3.org');

    await backend.formEngine.save();
    await expect(backend.contentFrame.locator('h1')).toContainText('System Status Update');
    await backend.formEngine.close();
  });

  test('the task can be enabled and disabled again', async ({ backend }) => {
    const taskRow = backend.contentFrame.locator('#tx_scheduler_form_0');
    await taskRow.locator('button[title*="Enable"]').click();
    await expect(
      backend.contentFrame.locator('[data-module-name="scheduler"] tr[data-task-disabled="true"]')
    ).not.toBeVisible();

    await backend.contentFrame.locator('button[title*="Disable"]').click();
    await expect(
      backend.contentFrame.locator('[data-module-name="scheduler"] tr[data-task-disabled="true"]')
    ).toBeVisible();
    await expect(backend.contentFrame.locator('body')).toContainText('disabled');
  });

  test('the task can be deleted with a confirm dialog', async ({ page, backend }) => {
    await backend.contentFrame.locator('button[title*="Delete"]').first().click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(dialog).not.toBeVisible();

    await backend.contentFrame.locator('button[title*="Delete"]').first().click();
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(dialog).not.toBeVisible();

    await expect(backend.contentFrame.locator('body')).toContainText('The task was successfully deleted.');
    await expect(backend.contentFrame.locator('body')).toContainText('No tasks found');
  });
});

test('Scheduler setup check modal opens', async ({ page, backend }) => {
  await openSchedulerModule({ page, backend });

  await backend.contentFrame.locator('.module-docheader typo3-scheduler-setup-check-button').click();
  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog).toContainText('Setup check');
  await expect(dialog).toContainText(
    'This screen checks if the requisites for running the Scheduler as a cron job are fulfilled'
  );
});

async function wizardItemClick(wizard: Locator, identifier: string): Promise<void> {
  await wizard.evaluate((el: any, id: string) => {
    el.shadowRoot.querySelector(`[data-identifier="${id}"]`).click();
  }, identifier);
}
