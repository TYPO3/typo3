import { test, expect } from '../../fixtures/setup-fixtures';
import type { FrameLocator } from '@playwright/test';

test.describe('Indexed Search module', () => {
  test('navigating between statistic views shows the expected content', async ({ page, backend }) => {
    await backend.gotoModule('content_status');
    await backend.pageTree.isReady();
    await page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="0"] .node-contentlabel').click();

    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('h1')).toContainText('Status');

    await contentFrame.locator('a[aria-label="Open Indexing module"]').click();

    await selectDocHeaderAction(contentFrame, 'General statistics');
    const moduleBody = contentFrame.locator('.t3js-module-body');
    await expect(moduleBody).toContainText('General statistics');
    await expect(moduleBody).toContainText('Row count by database table');

    const rowCountCells = await contentFrame
      .locator('.row > .col-md-6').first()
      .locator('table tbody tr > td:nth-child(2)')
      .allInnerTexts();
    expect(rowCountCells.length).toBeGreaterThan(0);
    for (const text of rowCountCells) {
      expect(text.trim()).toMatch(/^\d+$/);
    }

    await selectDocHeaderAction(contentFrame, 'List of indexed pages');
    await expect(moduleBody).toContainText('List of indexed pages');

    await selectDocHeaderAction(contentFrame, 'List of indexed external documents');
    await expect(moduleBody).toContainText('List of indexed external documents');

    await selectDocHeaderAction(contentFrame, 'Detailed statistics');
    await expect(moduleBody).toContainText('Detailed statistics');
    await expect(moduleBody).toContainText('Please select a page in the page tree.');
  });
});

async function selectDocHeaderAction(contentFrame: FrameLocator, label: string): Promise<void> {
  const toggle = contentFrame.getByRole('button', { name: /^Module action:/ });
  const menuId = await toggle.getAttribute('popovertarget');
  await contentFrame.locator(`#${menuId}`).getByText(label).evaluate((el: HTMLAnchorElement) => el.click());
  await expect(contentFrame.locator('#t3js-ui-block')).not.toBeVisible();
}
