import { test, expect } from '../fixtures/setup-fixtures';

test.beforeEach( async ({page}) => {
  await page.goto('module/web/layout');
})

test('Drag and drop new page in node without children', async ({ backend }) => {
  const dummySiteTitle = 'Dummy page tree';
  await backend.pageTree.create(await backend.pageTree.root, dummySiteTitle);
  const newPageElement = await backend.pageTree.open(dummySiteTitle);

  await expect(newPageElement.locator('.node-name')).toHaveText(dummySiteTitle);

  await backend.pageTree.dragDeletePage(newPageElement);
});

test('Drag and drop new page in node with children', async ({ backend}) => {
  const dummySiteTitle = 'Dummy page tree with children';
  const pageTitle = 'Dummy page tree child';

  // Create root page
  await backend.pageTree.create(backend.pageTree.root, dummySiteTitle);
  const newRootPageElement = await backend.pageTree.open(dummySiteTitle);

  // Create child page under newRootPageElement
  await backend.pageTree.create(newRootPageElement, pageTitle);
  const newPageElement = backend.pageTree.container.locator('[role="treeitem"]', { hasText: pageTitle });

  // Validate page creation
  await backend.pageTree.open(dummySiteTitle, pageTitle);
  await expect(newPageElement.locator('.node-name')).toHaveText(pageTitle);

  // Delete pages
  await backend.pageTree.dragDeletePage(newPageElement);
  await backend.pageTree.dragDeletePage(newRootPageElement);
});

test('Drag and drop new page and quit page creation', async ({ page, backend }) => {
  const pageTitle = 'Dummy quit creation';
  await backend.pageTree.dragNewPageTo(backend.pageTree.root);
  await page.fill('.node-edit', pageTitle);
  await page.keyboard.press('Escape');

  await expect(page.getByRole('treeitem', { name: pageTitle })).not.toBeAttached();
});

test('Drag and drop new page and leave page title empty', async ({ page, backend }) => {
  let pageTitle = 'Dummy empty title';
  await backend.pageTree.dragNewPageTo(backend.pageTree.root);
  await page.keyboard.press('Enter');

  await expect(page.getByRole('treeitem', { name: pageTitle })).not.toBeAttached();
});
