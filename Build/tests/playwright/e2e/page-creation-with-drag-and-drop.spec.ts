import {test, expect, Locator, Page} from '@playwright/test';
import config from '../config';

let treeRootElement: Locator;
let newPageToolbarItem: Locator;

test.beforeEach( async ({page}) => {
  await page.goto(`${config.baseUrl}/module/web/layout`);
  await page.waitForLoadState('networkidle');

  treeRootElement = page.locator('#typo3-pagetree-tree [identifier="apps-pagetree-root"]');
  newPageToolbarItem = page.locator('#typo3-pagetree-toolbar [data-tree-icon="apps-pagetree-page-default"]');
})

test('Drag and drop new page in node without children', async ({ page }) => {
  const dummySiteTitle = 'Dummy page tree';
  await newPageToolbarItem.dragTo(treeRootElement);
  await page.fill('.node-edit', dummySiteTitle);
  await page.keyboard.press('Enter');
  const newPageElement = page.getByRole('treeitem', { name: dummySiteTitle });

  await expect(newPageElement.locator('.node-name')).toHaveText(dummySiteTitle);

  await dragDeletePage(newPageElement);
});

test('Drag and drop new page in node with children', async ({ page }) => {
  const dummySiteTitle = 'Dummy page tree';
  await newPageToolbarItem.dragTo(treeRootElement);
  await page.fill('.node-edit', dummySiteTitle);
  await page.keyboard.press('Enter');
  const newRootPageElement = page.getByRole('treeitem', { name: dummySiteTitle });

  const pageTitle = 'Dummy 1';
  newPageToolbarItem.dragTo(newRootPageElement);
  await page.fill('.node-edit', pageTitle);
  await page.keyboard.press('Enter');
  const newPageElement = page.getByRole('treeitem', { name: pageTitle });

  await newPageElement.waitFor({ state: 'visible' });
  await expect(newPageElement.locator('.node-name')).toHaveText(pageTitle);

  await dragDeletePage(newPageElement);
  await dragDeletePage(newRootPageElement);
});

test('Drag and drop new page and quit page creation', async ({ page }) => {
  const pageTitle = 'Dummy quit creation';
  await newPageToolbarItem.dragTo(treeRootElement);

  await page.fill('.node-edit', pageTitle);
  await page.keyboard.press('Escape');

  await expect(page.getByRole('treeitem', { name: pageTitle })).not.toBeAttached();
});

test('Drag and drop new page and leave page title empty', async ({ page }) => {
  let pageTitle = 'Dummy empty title';
  await newPageToolbarItem.dragTo(treeRootElement);

  await page.fill('.node-edit', '');
  await page.keyboard.press('Enter');

  await expect(page.getByRole('treeitem', { name: pageTitle })).not.toBeAttached();
});

async function dragDeletePage(pageToDelete: Locator) {
  const box = await pageToDelete.boundingBox();
  await pageToDelete.dragTo(pageToDelete, {
    sourcePosition: {
      x: 10,
      y: box.height / 2,
    },
    targetPosition: {
      x: box.width - 10,
      y: box.height / 2,
    }
  });
  await pageToDelete.page().locator('typo3-backend-modal button[name="delete"]').click();
  await expect(pageToDelete).not.toBeAttached();
}
