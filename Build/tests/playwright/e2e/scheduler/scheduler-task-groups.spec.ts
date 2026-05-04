import { test, expect } from '../../fixtures/setup-fixtures';
import type { Locator, Page } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

const groupName = 'My task group';

async function openSchedulerModule({ page, backend }: { page: Page; backend: BackendPage }): Promise<void> {
  await page.goto('module/web/layout');
  const moduleLoaded = await backend.moduleLoaded('scheduler');
  await page.locator('a[data-modulemenu-identifier="scheduler"]').click();
  await moduleLoaded();
}

test.describe.serial('Scheduler task groups - lifecycle', () => {
  test.beforeEach(openSchedulerModule);

  test('a task group can be created via the docheader button', async ({ page, backend }) => {
    await backend.contentFrame.locator('.module-docheader .t3js-create-group').click();
    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await dialog.locator('input[name="action[createGroup]"]').fill(groupName);
    await dialog.getByRole('button', { name: 'Create group', exact: true }).click();
    await expect(dialog).not.toBeVisible();

    await expect(
      backend.contentFrame.locator('table td', { hasText: groupName })
    ).toBeVisible();
  });

  test('a new task can be added to the empty group', async ({ page, backend }) => {
    const groupRow = backend.contentFrame.locator('tr', { has: backend.contentFrame.locator('td', { hasText: groupName }) });
    await groupRow.locator('typo3-scheduler-new-task-wizard-button').click();

    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    const wizard = dialog.locator('typo3-backend-new-record-wizard');
    await wizardItemClick(wizard, 'scheduler');
    await wizardItemClick(wizard, 'scheduler_TYPO3_CMS_Scheduler_Task_RecyclerGarbageCollectionTask');

    await expect(backend.formEngine.container).toBeVisible();
    // The group should be pre-selected (second fieldset on first tab)
    const groupSelect = backend.contentFrame.locator(
      '.tab-pane.active > fieldset:nth-of-type(2) div.t3js-formengine-field-item select'
    );
    await expect(groupSelect.locator('option[selected]')).toContainText(groupName);

    await backend.formEngine.save();
    await backend.formEngine.close();

    await expect(
      backend.contentFrame.locator('div.panel-heading', { hasText: groupName })
    ).toBeVisible();
  });

  test('the task group can be hidden, disabling its tasks', async ({ backend }) => {
    const groupHeader = backend.contentFrame.locator('div.panel-heading', { hasText: groupName });
    await groupHeader.locator('button[title*="Disable"]').click();

    await expect(groupHeader.locator('.badge-secondary')).toBeVisible();
    // Task disabled by group
    const groupPanel = backend.contentFrame.locator('div.panel', { has: groupHeader });
    await expect(groupPanel.locator('tbody .badge-secondary').first()).toBeVisible();
  });

  test('group color and description can be edited', async ({ backend }) => {
    const groupDescription = 'This is a test description for the group';
    const groupHeader = backend.contentFrame.locator('div.panel-heading', { hasText: groupName });
    await groupHeader.locator('strong', { hasText: groupName }).click();

    await expect(backend.contentFrame.locator('h1')).toContainText(groupName);

    await backend.contentFrame
      .locator('input[data-formengine-input-name*="data[tx_scheduler_task_group]"][data-formengine-input-name*="[color]"]')
      .click();
    await backend.contentFrame.locator('button[aria-label*="Color swatch: TYPO3 orange"]').click();

    await backend.contentFrame
      .locator('textarea[data-formengine-input-name*="data[tx_scheduler_task_group]"][data-formengine-input-name*="[description]"]')
      .fill(groupDescription);

    await backend.formEngine.save();
    await expect(backend.contentFrame.locator('h1')).toContainText(groupName);
    await backend.formEngine.close();

    await expect(
      backend.contentFrame.locator('div.panel', { hasText: groupName })
    ).toHaveAttribute('style', /border-left.*#ff8700/);
    await expect(
      backend.contentFrame.locator('div.panel-title', { hasText: groupName }).locator('p.text-variant')
    ).toContainText(groupDescription);
  });

  test('the group and its task can be removed', async ({ page, backend }) => {
    const groupPanel = backend.contentFrame.locator('div.panel', {
      has: backend.contentFrame.locator('div.panel-heading', { hasText: groupName })
    });
    await groupPanel.locator('table button[title*="Delete"]').first().click();

    let dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(dialog).not.toBeVisible();

    // Empty group still listed in the summary table
    const groupSummaryRow = backend.contentFrame.locator('tr', {
      has: backend.contentFrame.locator('td', { hasText: groupName })
    });
    await expect(groupSummaryRow).toBeVisible();
    await groupSummaryRow.locator('button[title*="Delete"]').click();

    dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Delete', exact: true }).click();
    await expect(dialog).not.toBeVisible();

    await expect(backend.contentFrame.locator('table td', { hasText: groupName })).toHaveCount(0);
  });
});

test('clicking Edit in the create-group modal opens the form pre-filled', async ({ page, backend }) => {
  await openSchedulerModule({ page, backend });

  const newGroupName = 'Group via Edit';
  await backend.contentFrame.locator('.module-docheader .t3js-create-group').click();
  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  await dialog.locator('input[name="action[createGroup]"]').fill(newGroupName);
  await dialog.getByRole('button', { name: 'Edit', exact: true }).click();

  await expect(backend.contentFrame.locator('h1')).toContainText('Create new Scheduler task group');
  await expect(
    backend.contentFrame.locator(
      'input[data-formengine-input-name*="[tx_scheduler_task_group][NEW"][data-formengine-input-name*="[groupName]"]'
    )
  ).toHaveValue(newGroupName);
});

async function wizardItemClick(wizard: Locator, identifier: string): Promise<void> {
  await wizard.evaluate((el: any, id: string) => {
    el.shadowRoot.querySelector(`[data-identifier="${id}"]`).click();
  }, identifier);
}
