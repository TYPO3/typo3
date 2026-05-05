import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'file',
    listId: 'tx_styleguide_file',
  });
});

// Scope to the active tab pane. `.tab-content` includes inactive panes whose
// children are display:none, breaking `.first()` lookups on cross-tab DOM order.
const activeTab = '.tab-pane.active';

async function firstFalPanelTitle(backend): Promise<string> {
  // panel-title contains the filename followed by the relation type
  // (e.g. "bus_lane.jpg [sys_file_reference]") - split on whitespace to
  // get just the filename.
  const raw = (await backend.contentFrame.locator(`${activeTab} .panel-title`).first().textContent()) ?? '';
  return raw.trim().split(/\s+/)[0];
}

test('FAL relation info modal shows the filename in its title', async ({ backend }) => {
  const filename = await firstFalPanelTitle(backend);

  const modalContent = await backend.modal.open(
    backend.contentFrame.locator(`${activeTab} button[data-action="infowindow"]`).first()
  );

  await expect(modalContent.locator('.card-title')).toContainText(filename);
});

test('FAL relation hide button toggles the panel hidden state', async ({ backend }) => {
  // Hide, then unhide so the DB ends in the original state. Otherwise a retry
  // against the now-hidden record toggles back to visible and the assertion
  // for `.panel-hidden` fails.
  await backend.contentFrame.locator(`${activeTab} .t3js-toggle-visibility-button`).first().click();
  await backend.formEngine.save();
  await expect(backend.contentFrame.locator(`${activeTab} .panel-hidden`).first()).toBeAttached();

  await backend.contentFrame.locator(`${activeTab} .panel-hidden .t3js-toggle-visibility-button`).first().click();
  await backend.formEngine.save();
  await expect(backend.contentFrame.locator(`${activeTab} .panel-hidden`)).toHaveCount(0);
});

test('FAL relation delete removes the inline record', async ({ backend, page }) => {
  const filename = await firstFalPanelTitle(backend);

  await backend.contentFrame.locator(`${activeTab} .t3js-editform-delete-inline-record`).first().click();

  const dialog = page.locator('typo3-backend-modal > dialog');
  await expect(dialog).toBeVisible();
  await backend.modal.click({ name: 'yes' });
  await expect(dialog).not.toBeVisible();

  await backend.formEngine.save();

  await expect(backend.contentFrame.locator(`${activeTab} .form-section`).first()).not.toContainText(filename);
});
