import { test, expect } from '../../fixtures/setup-fixtures';

test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ page }) => {
  // sys_category records live at pid=0. Navigate directly to the records
  // module at the system root - page-tree clicks for the root marker open
  // a context menu overlay that intercepts subsequent module-switch clicks.
  await page.goto('module/content/records?id=0');
  await expect(page.locator('a[data-modulemenu-identifier="records"]')).toHaveClass(/modulemenu-action-active/);
});

test('category records list shows enough categories', async ({ backend }) => {
  await expect(backend.contentFrame.locator('#recordlist-sys_category')).toBeVisible();
  const rows = backend.contentFrame.locator('#recordlist-sys_category table > tbody > tr');
  const count = await rows.count();
  expect(count).toBeGreaterThanOrEqual(5);
  expect(count).toBeLessThanOrEqual(100);
});

test('edit category record changes title and parent in the tree', async ({ backend }) => {
  const editLink = backend.contentFrame.locator('#recordlist-sys_category tr[data-uid="7"] a[aria-label="Edit record"]');
  await expect(editLink).toBeAttached();
  const formEngineReady = await backend.formEngine.formEngineLoaded();
  // Force click bypasses the sticky module-docheader that may overlap rows
  // higher up in the records list.
  await editLink.click({ force: true });
  await formEngineReady();

  await backend.contentFrame.locator('input[data-formengine-input-name="data[sys_category][7][title]"]').fill('level-1-4');

  // Click the category being edited, then a different node to set as parent.
  await backend.contentFrame.locator('.nodes-container [role="treeitem"][data-id="7"] .node-contentlabel').click();
  await backend.contentFrame.locator('.nodes-container [role="treeitem"][data-id="3"] .node-contentlabel').click();

  await backend.formEngine.save();

  await expect(backend.contentFrame.locator('.nodes-container .nodes-list')).toBeAttached();
  await expect(backend.contentFrame.locator('body')).toContainText('level-1-4');

  await backend.formEngine.close();
});
