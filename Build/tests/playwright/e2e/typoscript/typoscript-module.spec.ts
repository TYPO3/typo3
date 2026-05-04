import { test, expect } from '../../fixtures/setup-fixtures';
import type { FrameLocator } from '@playwright/test';
import type { BackendPage } from '../../fixtures/backend-page';

async function enterTypoScriptModule({ page, backend, workspace }: { page: any; backend: BackendPage; workspace: any }): Promise<void> {
  await page.goto('module/web/layout');
  await workspace.ensureLiveWorkspace();
  const moduleLoaded = await backend.moduleLoaded('web_ts');
  await page.locator('a[data-modulemenu-identifier="web_ts"]').click();
  await moduleLoaded();
}

test.describe('TypoScript module', () => {
  test.beforeEach(enterTypoScriptModule);

  test('pages without TypoScript record show buttons to create one', async ({ page, backend }) => {
    await page.locator('#typo3-pagetree-tree [role="treeitem"][data-id="0"] .node-contentlabel').click();
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('#ts-overview')).toBeVisible();
    await expect(contentFrame.locator('body')).toContainText(
      'Global overview of all pages with active TypoScript definitions (database records and site sets).'
    );

    await backend.pageTree.open('styleguide TCA demo');
    await selectDocHeaderAction(backend, 'Edit TypoScript Record');
    await expect(contentFrame.locator('body')).toContainText('No TypoScript record on the current page');
    await expect(contentFrame.locator('body')).toContainText(
      'You need to create a TypoScript record in order to edit your configuration.'
    );
  });

  test('closest template button selects a parent record', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await backend.pageTree.open('styleguide frontend demo', 'template records', 'template record subsite');

    await selectDocHeaderAction(backend, 'Constant Editor');
    await expect(contentFrame.locator('body')).toContainText('No TypoScript record on the current page');

    const afterSelect = await backend.moduleLoaded('web_typoscript_constanteditor');
    await contentFrame.getByRole('link', { name: 'Select this TypoScript record' }).click();
    await afterSelect();

    await selectDocHeaderAction(backend, 'Edit TypoScript Record');
    await expect(contentFrame.locator('.table-striped')).toBeVisible();
    for (const label of ['Title', 'Description', 'Constants', 'Setup']) {
      await expect(contentFrame.locator('.table-striped')).toContainText(label);
    }
    await expect(contentFrame.getByRole('link', { name: 'Edit the whole TypoScript record' })).toBeVisible();
  });

  test('extension template can be created on a page', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await backend.pageTree.open('styleguide frontend demo', 'menu_sitemap_pages');

    await selectDocHeaderAction(backend, 'Constant Editor');
    await expect(contentFrame.locator('body')).toContainText('No TypoScript record on the current page');

    const afterCreate = await backend.moduleLoaded('web_typoscript_constanteditor');
    await contentFrame.locator('input[name="createExtension"]').click();
    await afterCreate();

    await selectDocHeaderAction(backend, 'Edit TypoScript Record');
    await expect(contentFrame.locator('.table-striped')).toBeVisible();
    for (const label of ['Title', 'Description', 'Constants', 'Setup']) {
      await expect(contentFrame.locator('.table-striped')).toContainText(label);
    }
    await contentFrame.getByRole('link', { name: 'Edit the whole TypoScript record' }).click();
    await expect(contentFrame.locator('h1')).toContainText('+ext');
  });
});

test.describe.serial('TypoScript module - site template lifecycle', () => {
  test.beforeEach(enterTypoScriptModule);

  test('a new site template can be created and edited', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await backend.pageTree.open('styleguide TCA demo');

    await selectDocHeaderAction(backend, 'Constant Editor');
    const newWebsiteBtn = contentFrame.locator('input[name="newWebsite"]');
    if (await newWebsiteBtn.count() > 0) {
      // First run: page has no template yet - create one
      await expect(contentFrame.locator('body')).toContainText('Root TypoScript record');
      const afterCreate = await backend.moduleLoaded('web_typoscript_constanteditor');
      await newWebsiteBtn.click();
      await afterCreate();
    }
    // Else (retry / re-run): template already exists, proceed straight to edit

    await selectDocHeaderAction(backend, 'Edit TypoScript Record');
    await expect(contentFrame.locator('.table-striped')).toBeVisible();
    for (const label of ['Title', 'Description', 'Constants', 'Setup']) {
      await expect(contentFrame.locator('.table-striped')).toContainText(label);
    }
    const editLink = contentFrame.getByRole('link', { name: 'Edit the whole TypoScript record' });
    await expect(editLink).toBeVisible();
    await editLink.click();
    await expect(contentFrame.locator('#EditDocumentController')).toBeVisible();

    await contentFrame
      .locator('input[data-formengine-input-name*="data[sys_template]"][data-formengine-input-name*="[title]"]')
      .fill('Acceptance Test Site');

    const codeMirror = contentFrame.locator('typo3-t3editor-codemirror[name$="[config]"]');
    await expect(codeMirror).toBeVisible();
    await codeMirror.scrollIntoViewIfNeeded();
    await expect.poll(
      () => codeMirror.evaluate((el: any) => el.editorView !== null),
      { timeout: 30000 }
    ).toBe(true);
    await codeMirror.evaluate((el: any) => {
      el.setContent(el.getContent().replace('HELLO WORLD!', 'Hello Acceptance Test!'));
    });

    await backend.formEngine.save();
    await backend.formEngine.close();

    await expect(contentFrame.locator('.table-striped')).toContainText('Acceptance Test Site');

    await selectDocHeaderAction(backend, 'Active TypoScript');

    await contentFrame.locator('#panel-tree-heading-setup').click();
    await expect(contentFrame.locator('body')).toContainText('page = PAGE');

    await openTypoScriptTreeNode(contentFrame, 'page');
    await expect(contentFrame.locator('body')).toContainText('10 = TEXT');
    await openTypoScriptTreeNode(contentFrame, '10', 'page');
    await expect(contentFrame.locator('body')).toContainText('value = Hello Acceptance Test!');

    await contentFrame
      .locator('//span[@class="treelist-label"]/a[text()="10"]/../../../div/ul//span[@class="treelist-label"]/a[text()="value"]')
      .click();
    await expect(contentFrame.locator('body')).toContainText('page.10.value =');
    await contentFrame.locator('input[name="value"]').fill('HELLO WORLD!');
    await contentFrame.locator('input[name="updateValue"]').click();

    await expect(contentFrame.locator('body')).toContainText('Line added to current TypoScript record');
    await expect(contentFrame.locator('body')).toContainText('page.10.value = HELLO WORLD!');
    await expect(contentFrame.locator('body')).toContainText('value = HELLO WORLD!');
  });

  test('searching in active TypoScript highlights matches', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await backend.pageTree.open('styleguide TCA demo');

    await selectDocHeaderAction(backend, 'Active TypoScript');
    await expect(contentFrame.locator('body')).toContainText('Active TypoScript for record');

    await contentFrame.locator('#searchValue').fill('styles');
    await expect(contentFrame.locator('body')).toContainText('Setup');
    await expect(contentFrame.locator('body')).toContainText('one match');
    expect(await contentFrame.locator('span[data-markjs="true"].text-highlight', { hasText: 'styles' }).count()).toBeGreaterThan(0);
  });
});

async function selectDocHeaderAction(backend: BackendPage, label: string): Promise<void> {
  const contentFrame = backend.contentFrame;
  const toggle = contentFrame.getByRole('button', { name: /^Module action:/ });
  await expect(toggle).toBeVisible();
  const menuId = await toggle.getAttribute('popovertarget');
  const moduleLoaded = await backend.moduleLoaded('any');
  await contentFrame.locator(`#${menuId}`).getByText(label).evaluate((el: HTMLAnchorElement) => el.click());
  await moduleLoaded();
}

async function openTypoScriptTreeNode(contentFrame: FrameLocator, nodeName: string, parentName?: string): Promise<void> {
  const xpath = parentName
    ? `//span[@class="treelist-label"]/a[text()="${parentName}"]/../../../div/ul//span[@class="treelist-label"]/a[text()="${nodeName}"]/../../../typo3-backend-tree-node-toggle`
    : `//span[@class="treelist-label"]/a[text()="${nodeName}"]/../../../typo3-backend-tree-node-toggle`;
  await contentFrame.locator(xpath).click();
}
