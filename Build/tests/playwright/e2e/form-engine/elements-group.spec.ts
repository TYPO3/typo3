import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'elements group',
    listId: 'tx_styleguide_elements_group',
  });
});

const fieldset = '.tab-pane.active > fieldset:nth-of-type(1)';
const formWizardsWrap = `${fieldset} > div:nth-of-type(1) div.t3js-formengine-field-item > div:nth-of-type(1)`;
const select = `${formWizardsWrap} > div:nth-of-type(2) > select`;
const groupDbSelect = 'select[data-formengine-input-name="data[tx_styleguide_elements_group][1][group_db_1]"]';

const user1 = 'styleguide demo user 1';
const user2 = 'styleguide demo user 2';

/**
 * Pick `<option>` elements by partial text and select them by value.
 * Playwright's `selectOption({ label })` requires an exact match, but
 * TYPO3 renders foreign-table options as `<label> [<uid>]` and the uid
 * varies per fixture. Tolerate missing labels - the remove test below
 * re-selects an already-removed label and expects it to be a no-op.
 */
async function pickOptionsByText(select: Locator, ...texts: string[]): Promise<void> {
  const values: string[] = [];
  for (const text of texts) {
    const optionLocator = select.locator('option').filter({ hasText: text }).first();
    if (await optionLocator.count() === 0) {
      continue;
    }
    const value = await optionLocator.getAttribute('value');
    if (value !== null) {
      values.push(value);
    }
  }
  await select.selectOption(values);
}

test('elements_group sortElementsInGroup moves options via toolbar buttons', async ({ backend }) => {
  const selectLocator = backend.contentFrame.locator(select);
  const button = (kind: 'top' | 'up' | 'down' | 'bottom') =>
    backend.contentFrame.locator(`${formWizardsWrap} > div:nth-of-type(3) > div > button.t3js-btn-moveoption-${kind}`);

  await pickOptionsByText(selectLocator, user1);
  await button('top').click();
  await expect(selectLocator.locator('option:nth-child(1)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1);
  await button('down').click();
  await expect(selectLocator.locator('option:nth-child(2)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1);
  await button('bottom').click();
  await expect(selectLocator.locator('option:nth-last-child(1)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1);
  await button('up').click();
  await expect(selectLocator.locator('option:nth-last-child(2)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1, user2);
  await button('top').click();
  await expect(selectLocator.locator('option:nth-child(1)')).toContainText(user1);
  await expect(selectLocator.locator('option:nth-child(2)')).toContainText(user2);

  await pickOptionsByText(selectLocator, user1, user2);
  await button('down').click();
  await expect(selectLocator.locator('option:nth-child(2)')).toContainText(user1);
  await expect(selectLocator.locator('option:nth-child(3)')).toContainText(user2);
});

test('elements_group sortElementsInGroup moves options via Alt+Arrow keyboard shortcuts', async ({ backend, page }) => {
  const selectLocator = backend.contentFrame.locator(select);
  const press = async (combo: string) => {
    await selectLocator.focus();
    await page.keyboard.press(combo);
  };

  await pickOptionsByText(selectLocator, user1);
  await press('Alt+Shift+ArrowUp');
  await expect(selectLocator.locator('option:nth-child(1)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1);
  await press('Alt+ArrowDown');
  await expect(selectLocator.locator('option:nth-child(2)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1);
  await press('Alt+Shift+ArrowDown');
  await expect(selectLocator.locator('option:nth-last-child(1)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1);
  await press('Alt+ArrowUp');
  await expect(selectLocator.locator('option:nth-last-child(2)')).toContainText(user1);

  await pickOptionsByText(selectLocator, user1, user2);
  await press('Alt+Shift+ArrowUp');
  await expect(selectLocator.locator('option:nth-child(1)')).toContainText(user1);
  await expect(selectLocator.locator('option:nth-child(2)')).toContainText(user2);

  await pickOptionsByText(selectLocator, user1, user2);
  await press('Alt+ArrowDown');
  await expect(selectLocator.locator('option:nth-child(2)')).toContainText(user1);
  await expect(selectLocator.locator('option:nth-child(3)')).toContainText(user2);
});

test('elements_group removeElementInGroupWithDeleteKey removes selected options', async ({ backend, page }) => {
  const selectLocator = backend.contentFrame.locator(select);

  await pickOptionsByText(selectLocator, user1);
  await selectLocator.focus();
  await page.keyboard.press('Delete');
  await expect(selectLocator.locator('option').filter({ hasText: user1 })).toHaveCount(0);

  await pickOptionsByText(selectLocator, user1, user2);
  await selectLocator.focus();
  await page.keyboard.press('Delete');
  await expect(selectLocator.locator('option').filter({ hasText: user1 })).toHaveCount(0);
  await expect(selectLocator.locator('option').filter({ hasText: user2 })).toHaveCount(0);
});

test('elements_group addARecordWithRecordBrowserGroup adds one record via DB browser', async ({ backend }) => {
  const dbSelectOptions = backend.contentFrame.locator(`${groupDbSelect} option`);
  await expect(dbSelectOptions).toHaveCount(4);

  const modalContent = await backend.modal.open(
    backend.contentFrame.locator(`${formWizardsWrap} > div:nth-of-type(4) > div > a:nth-of-type(1)`)
  );
  await modalContent
    .locator('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)')
    .click();

  await expect(dbSelectOptions).toHaveCount(5);
});

test('elements_group addTwoRecordWithRecordBrowserGroup adds two records via DB browser', async ({ backend }) => {
  const dbSelectOptions = backend.contentFrame.locator(`${groupDbSelect} option`);
  await expect(dbSelectOptions).toHaveCount(4);

  const modalContent = await backend.modal.open(
    backend.contentFrame.locator(`${formWizardsWrap} > div:nth-of-type(4) > div > a:nth-of-type(1)`)
  );
  await modalContent
    .locator('#recordlist-be_groups > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)')
    .click();
  await modalContent
    .locator('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)')
    .click();

  await expect(dbSelectOptions).toHaveCount(6);
});

test('elements_group searchForARecordWithRecordBrowserGroup searches and picks a record', async ({ backend }) => {
  const dbSelectOptions = backend.contentFrame.locator(`${groupDbSelect} option`);
  await expect(dbSelectOptions).toHaveCount(4);

  const modalContent = await backend.modal.open(
    backend.contentFrame.locator(`${formWizardsWrap} > div:nth-of-type(4) > div > a:nth-of-type(1)`)
  );

  // No-result search keeps the recordlist wrapper attached and only swaps
  // in a "no records found" callout, so assert no rows instead.
  await modalContent.locator('#recordsearchbox-searchterm').fill('foo');
  await modalContent.locator('button[name="search"]').click();
  await expect(modalContent.locator('#recordlist-be_users tbody tr')).toHaveCount(0);

  await modalContent.locator('#recordsearchbox-searchterm').fill('admin');
  await modalContent.locator('button[name="search"]').click();
  await expect(modalContent.locator('#recordlist-be_users')).toBeVisible();
  await expect(modalContent.locator('#recordlist-be_users')).toContainText('admin');

  await modalContent
    .locator('#recordlist-be_users > div:nth-child(1) > table:nth-child(1) > tbody:nth-child(2) > tr:nth-child(1) > td:nth-child(2) > span:nth-child(1) > a:nth-child(1)')
    .click();

  await backend.modal.element.locator('.t3js-modal-close').click();
  await expect(backend.modal.element).not.toBeVisible();

  await expect(backend.contentFrame.locator(groupDbSelect)).toContainText('admin');

  await backend.formEngine.save();
});
