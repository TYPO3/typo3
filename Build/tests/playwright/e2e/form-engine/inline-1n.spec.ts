import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

// The "sorting" test depends on the inline child the "create" test adds.
// Run serially in declared order to preserve that contract.
test.describe.configure({ mode: 'serial' });

const childPanelAny = '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]';
// Anchor closing bracket inside the prefix, otherwise this also matches uid
// 10, 11, 100, ... which a polluted DB (e.g. after a serial-retry) easily has.
const childPanel1 = '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child][1]"]';
const child1InputName = 'data[tx_styleguide_inline_1n_inline_1_child][1][input_1]';

async function openInline1nEditor(backend: BackendPage): Promise<void> {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo', 'inline 1n');

  const formEngineReady = await backend.formEngine.formEngineLoaded();
  await backend.contentFrame
    .locator('#recordlist-tx_styleguide_inline_1n a[aria-label="Edit record"]')
    .first()
    .click();
  await formEngineReady();
}

test.beforeEach(async ({ backend }) => {
  await openInline1nEditor(backend);
});

test('inline_1n panel button toggles child input visibility', async ({ backend }) => {
  const childInput = backend.contentFrame.locator(`input[data-formengine-input-name="${child1InputName}"]`);
  const childCollapsed = backend.contentFrame.locator(`${childPanel1} .panel-collapse.show`);

  await backend.contentFrame.locator(childPanel1).locator('.panel-button').first().click();
  await expect(childInput).toBeVisible();
  await expect(childInput).toHaveValue('lipsum');

  await backend.contentFrame.locator(childPanel1).locator('.panel-button').first().click();
  await expect(childCollapsed).not.toBeVisible();
});

test('inline_1n hides and unhides an inline child', async ({ backend }) => {
  const panel = backend.contentFrame.locator(childPanelAny);
  const hiddenPanel = backend.contentFrame.locator(`${childPanelAny}.panel-hidden`);
  const hideAction = panel.locator('button span[data-identifier="actions-edit-hide"]').first();
  const unhideAction = backend.contentFrame.locator(`${childPanelAny} button span[data-identifier="actions-edit-unhide"]`);

  await hideAction.click();
  await expect(hiddenPanel).toBeAttached();
  await expect(unhideAction).toBeAttached();

  await unhideAction.click();
  await expect(hiddenPanel).not.toBeVisible();
});

test('inline_1n creates a new inline child via newRecord button', async ({ backend, page }) => {
  // The form renders three "Create new" buttons (one per inline relation).
  // The first in DOM order belongs to the inline_1 child relation.
  await backend.contentFrame.locator('button[data-type="newRecord"]').first().click();

  const newChildInput = backend.contentFrame
    .locator('input[data-formengine-input-name^="data[tx_styleguide_inline_1n_inline_1_child]["][data-formengine-input-name$="][input_1]"]')
    .last();
  await expect(newChildInput).toBeVisible();
  await newChildInput.fill('Fo Bar');
  // Trigger validation/blur on the input.
  await page.keyboard.press('Tab');

  await backend.formEngine.save();
  await backend.formEngine.close();

  const recordList = backend.contentFrame.locator('#recordlist-tx_styleguide_inline_1n_inline_1_child');
  await expect(recordList).toContainText('lipsum');
  await expect(recordList).toContainText('Fo Bar');
});

test('inline_1n sorts an inline child down', async ({ backend }) => {
  await backend.contentFrame
    .locator(childPanelAny)
    .locator('button span[data-identifier="actions-move-down"]')
    .first()
    .click();
  await backend.formEngine.save();
  await backend.formEngine.close();

  const tableBody = backend.contentFrame.locator('#recordlist-tx_styleguide_inline_1n_inline_1_child table tbody');
  await expect(tableBody).toBeVisible();
  await expect(tableBody.locator('tr').first()).toContainText('Fo Bar');
  await expect(tableBody.locator('tr').nth(1)).toContainText('lipsum');
});

test('inline_1n changes an inline child input value', async ({ backend }) => {
  const childInput = backend.contentFrame.locator(`input[data-formengine-input-name="${child1InputName}"]`);
  await backend.contentFrame.locator(childPanel1).locator('.panel-button').first().click();
  await expect(childInput).toBeVisible();
  await childInput.fill('hello world');

  await backend.formEngine.save();
  await backend.formEngine.close();

  await expect(backend.contentFrame.locator('body')).toContainText('hello world');
});

test('inline_1n delete dialog cancels and confirms', async ({ backend, page }) => {
  const dialog = page.locator('typo3-backend-modal > dialog');
  const deleteAction = backend.contentFrame.locator(`${childPanel1} button span[data-identifier="actions-edit-delete"]`).first();

  await deleteAction.click();
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'no' });
  await expect(dialog).not.toBeVisible();
  await expect(backend.contentFrame.locator(childPanel1)).toBeAttached();

  await deleteAction.click();
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'yes' });
  await expect(dialog).not.toBeVisible();
  await expect(backend.contentFrame.locator(childPanel1)).not.toBeAttached();
});

test('inline_1n inline_2 tab hides and unhides child without rendered disable field', async ({ backend }) => {
  await backend.contentFrame.getByRole('tab', { name: 'inline_2', exact: true }).click();

  const child2PanelAny = '[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["]';
  const child2InputName = 'data[tx_styleguide_inline_1n_inline_2_child][1][input_1]';
  const panel = backend.contentFrame.locator(child2PanelAny);
  const childInput = backend.contentFrame.locator(`input[data-formengine-input-name="${child2InputName}"]`);

  await panel.locator('.panel-button').first().click();
  await expect(childInput).toBeVisible();

  await panel.locator('button span[data-identifier="actions-edit-hide"]').first().click();
  await expect(backend.contentFrame.locator(`${child2PanelAny}.panel-hidden`)).toBeAttached();
  await expect(backend.contentFrame.locator(`${child2PanelAny} button span[data-identifier="actions-edit-unhide"]`)).toBeAttached();

  await backend.formEngine.save();

  await backend.contentFrame.locator(`${child2PanelAny} button span[data-identifier="actions-edit-unhide"]`).first().click();
  await expect(backend.contentFrame.locator(`${child2PanelAny}.panel-hidden`)).not.toBeVisible();
});
