import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'elements select',
    listId: 'tx_styleguide_elements_select',
    tab: 'renderType=selectMultipleSideBySide',
  });
});

const wizardWrap = '.tab-pane.active > fieldset:nth-of-type(1) .form-wizards-item-element';
const selectAvailable = `${wizardWrap} > div:nth-of-type(1) > div:nth-of-type(2) select`;
const selectSelected = `${wizardWrap} > div:nth-of-type(1) > div:nth-of-type(1) select`;

test('elements_select selectMultipleSideBySide adds option via Enter key', async ({ backend, page }) => {
  await backend.contentFrame.locator(selectAvailable).focus();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');

  await expect(backend.contentFrame.locator(`${selectSelected} > option:nth-child(2)`)).toContainText('foo 1');
});

test('elements_select selectMultipleSideBySide removes option via Delete key', async ({ backend, page }) => {
  await backend.contentFrame.locator(selectSelected).focus();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Delete');

  // The select may end up empty after Delete; asserting no option carries
  // the removed label survives the empty state (toHaveCount(0) matches both
  // "no options at all" and "options exist but none has this text").
  await expect(backend.contentFrame.locator(`${selectSelected} option`, { hasText: 'foo 2' })).toHaveCount(0);
});
