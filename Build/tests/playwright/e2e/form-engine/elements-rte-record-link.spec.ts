import { FrameLocator, Locator, Page } from '@playwright/test';
import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

/**
 * Record links are offered by the "Styleguide records" link handler, see
 * Build/tests/packages/playwright_helper/Configuration/page.tsconfig.
 *
 * Covers https://forge.typo3.org/issues/97069: additional link parameters
 * of a record link must survive saving and must be offered again when the
 * link is edited.
 */
const recordLinkWithParameters = /href="t3:\/\/record\?identifier=styleguide&amp;uid=\d+&amp;L=1"/;

async function selectPageInLinkBrowser(page: Page, modalFrame: FrameLocator, ...pages: Array<string>): Promise<void> {
  const tree = modalFrame.locator('typo3-backend-component-page-browser-tree');
  let level = 1;
  for (const pageTitle of pages) {
    level++;
    const node = tree.locator(`[aria-level="${level}"]`, {
      has: page.locator('.node-contentlabel', { hasText: new RegExp(`^${pageTitle}$`) })
    }).first();
    await expect(node).toBeVisible();
    if (pageTitle !== pages[pages.length - 1]) {
      const toggle = node.locator('.node-toggle [identifier="actions-chevron-end"]');
      if (await toggle.count() === 1) {
        await node.locator('.node-toggle').click();
      }
    } else {
      await node.locator('.node-contentlabel').click();
    }
  }
}

async function readSource(field: Locator): Promise<string> {
  const sourceButton = field.locator('.ck-source-editing-button');
  const sourceTextarea = field.locator('textarea[aria-label="Source code editing area"]');
  await sourceButton.click();
  await expect(sourceTextarea).toBeVisible();
  const source = await sourceTextarea.inputValue();
  await sourceButton.click();
  return source;
}

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, { pageName: 'elements rte', listId: 'tx_styleguide_elements_rte' });
});

test('record link keeps additional link parameters when saved and edited', async ({ backend, page }) => {
  const field = backend.formEngine.elementsBasicFormSection('rte_2');
  const editable = field.locator('.ck-editor__editable[contenteditable="true"]');
  await expect(editable).toBeVisible({ timeout: 30000 });

  await editable.click();
  await editable.press('ControlOrMeta+a');
  await editable.press('Delete');
  await editable.pressSequentially('record link');
  await editable.press('ControlOrMeta+a');
  await field.getByRole('button', { name: 'Link', exact: true }).click();

  await expect(page.locator('.t3js-modal-iframe')).toBeAttached();
  let modalFrame = page.frameLocator('.t3js-modal-iframe');
  await modalFrame.getByRole('tab', { name: 'Styleguide records' }).click();
  await selectPageInLinkBrowser(page, modalFrame, 'styleguide TCA demo', 'elements basic');
  await modalFrame.locator('input[name="lparams"]').fill('&L=1');
  await modalFrame.locator('#recordlist-tx_styleguide_elements_basic a[data-close]').first().click();
  await expect(page.locator('.t3js-modal-iframe')).not.toBeAttached();

  expect(await readSource(field)).toMatch(recordLinkWithParameters);

  await backend.formEngine.save();
  await expect(editable).toBeVisible({ timeout: 30000 });
  expect(await readSource(field)).toMatch(recordLinkWithParameters);

  // Editing the link offers the stored parameters again, and keeping the
  // current link must not add them a second time.
  await editable.locator('a', { hasText: 'record link' }).click();
  await backend.contentFrame.getByRole('button', { name: 'Edit link', exact: true }).click();
  await expect(page.locator('.t3js-modal-iframe')).toBeAttached();
  modalFrame = page.frameLocator('.t3js-modal-iframe');
  await expect(modalFrame.getByRole('tab', { name: 'Styleguide records' })).toHaveAttribute('aria-selected', 'true');
  await expect(modalFrame.locator('input[name="lparams"]')).toHaveValue('&L=1');
  await modalFrame.locator('button.t3js-linkCurrent').click();
  await expect(page.locator('.t3js-modal-iframe')).not.toBeAttached();

  expect(await readSource(field)).toMatch(recordLinkWithParameters);
});
