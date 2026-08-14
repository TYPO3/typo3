import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';
import type { Page } from '@playwright/test';

// Every test works on the same styleguide record and mutates it, so they must
// not run in parallel and they build on each other in declared order.
//
// A serial group is retried as a whole, and the retry runs against the database
// the failed attempt left behind - the styleguide fixture data is only pristine
// for the very first attempt. Nothing here may therefore rely on fixture uids,
// on the number of children found, or on what an earlier attempt did: the
// baseline below is rebuilt once per attempt instead.
test.describe.configure({ mode: 'serial' });

const childPanelAny = '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child]["]';
// A child which has not been persisted yet carries a "NEW<hash>" identifier
// instead of an uid, which addresses it without counting or positions.
const childPanelNew = '[data-field-name^="[tx_styleguide_inline_1n_inline_1_child][NEW"]';
const childInput = 'input[data-formengine-input-name$="][input_1]"]';
const baselineTitles = ['e2e alpha', 'e2e beta'];

/**
 * Panel selector for one child, addressed by its full field name.
 */
function childPanel(fieldName: string): string {
  return `[data-field-name="${fieldName}"]`;
}

/**
 * Field names of the inline_1 children, in their current rendering order.
 */
async function childOrder(backend: BackendPage): Promise<string[]> {
  return backend.contentFrame
    .locator(childPanelAny)
    .evaluateAll((panels) => panels.map((panel) => panel.getAttribute('data-field-name') ?? ''));
}

/**
 * Record titles of the inline_1 children, in their current rendering order.
 *
 * Read from the panel header, not from the input field: a collapsed child is
 * rendered without its fields and only loads them when it gets expanded, so
 * reading the fields would silently skip every collapsed child. The header
 * carries the table name in a "panel-meta" element while debug information is
 * enabled, which is stripped here to stay independent of that setting.
 */
async function childTitles(backend: BackendPage): Promise<string[]> {
  const titles: string[] = [];
  for (const fieldName of await childOrder(backend)) {
    titles.push(
      await backend.contentFrame
        .locator(`${childPanel(fieldName)} .panel-title`)
        .first()
        .evaluate((title) => {
          const header = title.cloneNode(true) as HTMLElement;
          header.querySelectorAll('.panel-meta').forEach((meta) => meta.remove());
          return (header.textContent ?? '').trim();
        })
    );
  }
  return titles;
}

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

async function deleteChild(backend: BackendPage, page: Page, fieldName: string): Promise<void> {
  const dialog = page.locator('typo3-backend-modal > dialog');
  await backend.contentFrame
    .locator(`${childPanel(fieldName)} button span[data-identifier="actions-edit-delete"]`)
    .first()
    .click();
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'yes' });
  await expect(dialog).not.toBeVisible();
  await expect(backend.contentFrame.locator(childPanel(fieldName))).not.toBeAttached();
}

async function createChild(backend: BackendPage, page: Page, title: string): Promise<void> {
  // The form renders three "Create new" buttons (one per inline relation).
  // The first in DOM order belongs to the inline_1 child relation.
  await backend.contentFrame.locator('button[data-type="newRecord"]').first().click();

  const newChild = backend.contentFrame.locator(childPanelNew);
  await expect(newChild).toHaveCount(1);

  const input = newChild.locator(childInput).first();
  await expect(input).toBeVisible();
  await input.fill(title);
  // Trigger validation/blur on the input.
  await page.keyboard.press('Tab');
}

async function collapseChildren(backend: BackendPage): Promise<void> {
  // The expand state lives in the backend user configuration, so it survives a
  // reload and has to be normalised together with the records themselves.
  for (const fieldName of await childOrder(backend)) {
    const collapse = backend.contentFrame.locator(`${childPanel(fieldName)} .panel-collapse`);
    if (await collapse.evaluate((element) => element.classList.contains('show'))) {
      await backend.contentFrame.locator(`${childPanel(fieldName)} .panel-button`).first().click();
      await expect(collapse).not.toHaveClass(/\bshow\b/);
    }
  }
}

/**
 * Replace the inline_1 children by exactly the baseline ones. Expects an open
 * editor and leaves it open.
 */
async function establishBaseline(backend: BackendPage, page: Page): Promise<void> {
  for (const fieldName of await childOrder(backend)) {
    await deleteChild(backend, page, fieldName);
  }
  for (const title of baselineTitles) {
    await createChild(backend, page, title);
    await backend.formEngine.save();
  }

  expect(await childTitles(backend)).toEqual(baselineTitles);
  await collapseChildren(backend);
}

// Attempt the baseline was last built for, so it is rebuilt once per attempt
// and not once per test: within an attempt the tests build on each other.
let baselineAttempt = -1;

test.beforeEach(async ({ backend, page }, testInfo) => {
  await openInline1nEditor(backend);

  if (baselineAttempt !== testInfo.retry) {
    await establishBaseline(backend, page);
    baselineAttempt = testInfo.retry;
  }
});

test('inline_1n panel button toggles child input visibility', async ({ backend }) => {
  const [firstChild] = await childOrder(backend);
  const panel = backend.contentFrame.locator(childPanel(firstChild));
  const input = panel.locator(childInput).first();
  const collapsed = backend.contentFrame.locator(`${childPanel(firstChild)} .panel-collapse.show`);

  await panel.locator('.panel-button').first().click();
  await expect(input).toBeVisible();
  await expect(input).toHaveValue(baselineTitles[0]);

  await panel.locator('.panel-button').first().click();
  await expect(collapsed).not.toBeVisible();
});

test('inline_1n hides and unhides an inline child', async ({ backend }) => {
  const [firstChild] = await childOrder(backend);
  const panel = backend.contentFrame.locator(childPanel(firstChild));
  const hiddenPanel = backend.contentFrame.locator(`${childPanel(firstChild)}.panel-hidden`);
  const unhideAction = backend.contentFrame.locator(
    `${childPanel(firstChild)} button span[data-identifier="actions-edit-unhide"]`
  );

  await panel.locator('button span[data-identifier="actions-edit-hide"]').first().click();
  await expect(hiddenPanel).toBeAttached();
  await expect(unhideAction).toBeAttached();

  await unhideAction.click();
  await expect(hiddenPanel).not.toBeVisible();
});

test('inline_1n creates a new inline child via newRecord button', async ({ backend, page }) => {
  await createChild(backend, page, 'e2e gamma');
  await backend.formEngine.save();

  expect(await childTitles(backend)).toEqual([...baselineTitles, 'e2e gamma']);

  await backend.formEngine.close();

  const recordList = backend.contentFrame.locator('#recordlist-tx_styleguide_inline_1n_inline_1_child');
  await expect(recordList).toContainText('e2e gamma');
});

test('inline_1n sorts an inline child down', async ({ backend }) => {
  const orderBefore = await childOrder(backend);
  const [firstChild] = orderBefore;
  // Assert what the click needs instead of clicking blindly: the form renders
  // the "move down" control of the last child invisible, so a mismatch would
  // stall until the test times out rather than report the actual problem.
  expect(orderBefore.length).toBeGreaterThan(1);

  const moveDown = backend.contentFrame
    .locator(`${childPanel(firstChild)} button span[data-identifier="actions-move-down"]`)
    .first();
  await expect(moveDown).toBeVisible();
  await moveDown.click();

  // Sorting is applied client side, the child changed place with its successor.
  const orderAfterClick = await childOrder(backend);
  expect(orderAfterClick[0]).toBe(orderBefore[1]);
  expect(orderAfterClick[1]).toBe(firstChild);

  await backend.formEngine.save();
  await backend.formEngine.close();

  // The record list of the page holds the children of every parent record and
  // can not tell the order within one relation, so re-read the record itself.
  await openInline1nEditor(backend);
  expect(await childOrder(backend)).toEqual(orderAfterClick);
});

test('inline_1n changes an inline child input value', async ({ backend }) => {
  const [firstChild] = await childOrder(backend);
  const panel = backend.contentFrame.locator(childPanel(firstChild));
  const input = panel.locator(childInput).first();

  await panel.locator('.panel-button').first().click();
  await expect(input).toBeVisible();
  await input.fill('hello world');

  await backend.formEngine.save();
  await backend.formEngine.close();

  await expect(backend.contentFrame.locator('body')).toContainText('hello world');
});

test('inline_1n delete dialog cancels and confirms', async ({ backend, page }) => {
  const [firstChild] = await childOrder(backend);
  const dialog = page.locator('typo3-backend-modal > dialog');
  const deleteAction = backend.contentFrame
    .locator(`${childPanel(firstChild)} button span[data-identifier="actions-edit-delete"]`)
    .first();

  await deleteAction.click();
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'no' });
  await expect(dialog).not.toBeVisible();
  await expect(backend.contentFrame.locator(childPanel(firstChild))).toBeAttached();

  await deleteAction.click();
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'yes' });
  await expect(dialog).not.toBeVisible();
  await expect(backend.contentFrame.locator(childPanel(firstChild))).not.toBeAttached();
});

test('inline_1n inline_2 tab hides and unhides child without rendered disable field', async ({ backend }) => {
  await backend.contentFrame.getByRole('tab', { name: 'inline_2', exact: true }).click();

  const child2PanelAny = '[data-field-name^="[tx_styleguide_inline_1n_inline_2_child]["]';
  const child2InputName = 'data[tx_styleguide_inline_1n_inline_2_child][1][input_1]';
  const panel = backend.contentFrame.locator(child2PanelAny);
  const child2Input = backend.contentFrame.locator(`input[data-formengine-input-name="${child2InputName}"]`);

  await panel.locator('.panel-button').first().click();
  await expect(child2Input).toBeVisible();

  await panel.locator('button span[data-identifier="actions-edit-hide"]').first().click();
  await expect(backend.contentFrame.locator(`${child2PanelAny}.panel-hidden`)).toBeAttached();
  await expect(backend.contentFrame.locator(`${child2PanelAny} button span[data-identifier="actions-edit-unhide"]`)).toBeAttached();

  await backend.formEngine.save();

  await backend.contentFrame.locator(`${child2PanelAny} button span[data-identifier="actions-edit-unhide"]`).first().click();
  await expect(backend.contentFrame.locator(`${child2PanelAny}.panel-hidden`)).not.toBeVisible();
});
