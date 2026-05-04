import { test, expect } from '../../fixtures/setup-fixtures';
import type { Locator, Page } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

const wizardButtonSelector = 'typo3-scheduler-new-task-wizard-button';

test.describe('Scheduler task wizard', () => {
  test.beforeEach(async ({ page, backend }) => {
    await page.goto('module/web/layout');
    const moduleLoaded = await backend.moduleLoaded('scheduler');
    await page.locator('a[data-modulemenu-identifier="scheduler"]').click();
    await moduleLoaded();
  });

  test('wizard modal opens from the empty task list', async ({ page, backend }) => {
    await expect(backend.contentFrame.locator('body')).toContainText('No tasks found');
    await expect(backend.contentFrame.locator('body')).toContainText(
      'There are currently no configured tasks found. You can create a new one.'
    );

    const dialog = await openWizard(page, backend);
    await expect(dialog.locator('.t3js-modal-title')).toContainText('New task');
    await expect(dialog.locator('typo3-backend-new-record-wizard')).toBeVisible();
  });

  test('wizard shows scheduler task categories', async ({ page, backend }) => {
    const dialog = await openWizard(page, backend);
    const wizard = dialog.locator('typo3-backend-new-record-wizard');
    await wizardItemClick(wizard, 'scheduler');
    await expect(wizard.getByText('Caching framework garbage collection')).toBeVisible();
    await expect(wizard.getByText('File Abstraction Layer').first()).toBeVisible();
    await expect(wizard.getByText('Table garbage collection')).toBeVisible();
  });

  test('selecting a wizard task opens form engine for that task type', async ({ page, backend }) => {
    const dialog = await openWizard(page, backend);
    const wizard = dialog.locator('typo3-backend-new-record-wizard');
    await wizardItemClick(wizard, 'scheduler');
    await wizardItemClick(wizard, 'scheduler_TYPO3_CMS_Scheduler_Task_RecyclerGarbageCollectionTask');

    await expect(backend.formEngine.container).toBeVisible();
    await expect(backend.contentFrame.locator('h1')).toContainText('Create new Fileadmin garbage collection');
    await expect(backend.contentFrame.locator('body')).toContainText('Number of days until removing files');

    await backend.formEngine.save();
    await backend.formEngine.close();

    await expect(backend.contentFrame.locator('body')).toContainText('Fileadmin garbage collection');

    await deleteFirstTask(page, backend);
  });

  test('wizard search filters task list', async ({ page, backend }) => {
    const dialog = await openWizard(page, backend);
    const wizard = dialog.locator('typo3-backend-new-record-wizard');

    await wizardSearchFill(wizard, 'cache');
    await wizardItemClick(wizard, 'scheduler');
    await expect(wizard.getByText('Caching framework garbage collection')).toBeVisible();
    await expect(wizard.getByText('Optimize MySQL database tables')).not.toBeVisible();

    await wizardSearchFill(wizard, '');
    await wizardItemClick(wizard, 'scheduler');
    await expect(wizard.getByText('Caching framework garbage collection')).toBeVisible();
    await expect(wizard.getByText('Optimize MySQL database tables')).toBeVisible();
  });

  test('wizard shows a no-results message when nothing matches', async ({ page, backend }) => {
    const dialog = await openWizard(page, backend);
    const wizard = dialog.locator('typo3-backend-new-record-wizard');
    await wizardSearchFill(wizard, 'nonexistentask');
    await expect(wizard.getByText('Unfortunately no scheduler task matches your query, please try a different one.')).toBeVisible();
  });

  test('creating multiple tasks builds the recently-used list', async ({ page, backend }) => {
    await createWizardTask(page, backend, 'scheduler', 'scheduler_TYPO3_CMS_Scheduler_Task_RecyclerGarbageCollectionTask');
    await expect(backend.contentFrame.locator('body')).toContainText('Fileadmin garbage collection');

    await createWizardTask(page, backend, 'scheduler', 'scheduler_TYPO3_CMS_Scheduler_Task_FileStorageIndexingTask');
    await expect(backend.contentFrame.locator('body')).toContainText('Fileadmin garbage collection');
    await expect(backend.contentFrame.locator('body')).toContainText('File Abstraction Layer: Update storage index');

    await deleteFirstTask(page, backend);
    await expect(backend.contentFrame.locator('body')).not.toContainText('Fileadmin garbage collection');
    await expect(backend.contentFrame.locator('body')).toContainText('File Abstraction Layer: Update storage index');
    await deleteFirstTask(page, backend);

    const dialog = await openWizard(page, backend);
    const wizard = dialog.locator('typo3-backend-new-record-wizard');
    await wizardItemClick(wizard, 'recently-used');
    await expect(wizard.getByText('Fileadmin garbage collection')).toBeVisible();
    await expect(wizard.getByText('File Abstraction Layer: Update storage index')).toBeVisible();
  });
});

async function openWizard(page: Page, backend: BackendPage): Promise<Locator> {
  await backend.contentFrame.locator(`.module-docheader ${wizardButtonSelector}`).click();
  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  return dialog;
}

async function wizardItemClick(wizard: Locator, identifier: string): Promise<void> {
  await wizard.evaluate((el: any, id: string) => {
    el.shadowRoot.querySelector(`[data-identifier="${id}"]`).click();
  }, identifier);
}

async function wizardSearchFill(wizard: Locator, value: string): Promise<void> {
  // Set the value inside the shadow DOM and dispatch the input event the
  // wizard listens for. Playwright's `fill()` does not always reach a
  // search input wrapped in a shadow root reliably here.
  await wizard.evaluate((el: any, v: string) => {
    const input = el.shadowRoot.querySelector('input[type="search"]') as HTMLInputElement;
    input.value = v;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }, value);
}

async function createWizardTask(page: Page, backend: BackendPage, category: string, taskIdentifier: string): Promise<void> {
  const dialog = await openWizard(page, backend);
  const wizard = dialog.locator('typo3-backend-new-record-wizard');
  await wizardItemClick(wizard, category);
  await wizardItemClick(wizard, taskIdentifier);

  await expect(backend.formEngine.container).toBeVisible();
  await backend.formEngine.save();
  await backend.formEngine.close();
}

async function deleteFirstTask(page: Page, backend: BackendPage): Promise<void> {
  await backend.contentFrame.locator('button[title*="Delete"]').first().click();
  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  await dialog.getByRole('button', { name: 'Delete', exact: true }).click();
  await expect(dialog).not.toBeVisible();
}
