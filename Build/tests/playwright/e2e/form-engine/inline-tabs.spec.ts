import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';
import type { BackendPage } from '../../fixtures/backend-page';

const childToggle = '.tab-pane.active .form-irre-object > .panel-heading .panel-button';

async function openInline1nEditor(backend: BackendPage): Promise<void> {
  await openStyleguideTcaEditor(backend, {
    pageName: 'inline 1n',
    listId: 'tx_styleguide_inline_1n',
  });
}

test('tabs of an inline child loaded on expand are wrapped in the tab scroller', async ({ backend, page }) => {
  await openInline1nEditor(backend);

  // The expand state is remembered per user. An expanded child is rendered
  // with the form instead of being loaded on expand, so collapse it first.
  const toggle = backend.contentFrame.locator(childToggle).first();
  if (await toggle.getAttribute('aria-expanded') === 'true') {
    const persisted = page.waitForResponse((response) => response.url().includes('/record/inline/expandcollapse'));
    await toggle.click();
    await persisted;
    await openInline1nEditor(backend);
  }

  const child = backend.contentFrame.locator('.tab-pane.active .form-irre-object').first();
  await expect(child).toHaveClass(/t3js-not-loaded/);

  await toggle.click();
  const childTabs = child.locator('.nav-tabs');
  await expect(childTabs).toBeVisible();

  const scroller = child.locator('typo3-backend-tab-scroller');
  await expect(scroller).toHaveCount(1);
  await expect(scroller.locator(':scope > .nav-tabs')).toHaveCount(1);
  await expect(scroller.locator(':scope > .nav-tabs-scroll')).toHaveCount(2);
});
