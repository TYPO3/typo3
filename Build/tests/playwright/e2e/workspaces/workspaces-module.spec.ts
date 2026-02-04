import { test, expect } from '../../fixtures/setup-fixtures';

test.describe.serial('Workspace Module', () => {
  let testPageTitle: string;
  let modifiedPageTitle: string;

  test('Create test page in LIVE workspace', async ({ page, backend, workspace }) => {
    testPageTitle = `Workspace Test ${backend.getUnixTimestamp()}`;
    modifiedPageTitle = `${testPageTitle} modified`;

    await page.goto('module/web/layout');
    await workspace.ensureLiveWorkspace();

    await backend.pageTree.isReady();
    await backend.pageTree.create(backend.pageTree.root, testPageTitle);
    await backend.pageTree.refresh();
    await backend.pageTree.open(testPageTitle);
  });

  test('Switch to workspace and verify indicator', async ({ page, workspace }) => {
    await page.goto('module/web/layout');

    await test.step('Verify LIVE workspace is active', async () => {
      await workspace.expectWorkspaceToBe('LIVE');
      await workspace.expectDropdownSelectionToBe('LIVE');
      await workspace.closeDropdown();
    });

    await test.step('Switch to Test Workspace', async () => {
      await workspace.switchTo('Test Workspace');
      await workspace.expectWorkspaceToBe('Test Workspace');
      await workspace.expectDropdownSelectionToBe('Test Workspace');
    });
  });

  test('Edit page title in workspace', async ({ page, backend, workspace }) => {
    await backend.gotoModule('web_layout');
    await backend.pageTree.open(testPageTitle);

    await test.step('Rename page via editable page title', async () => {
      const editablePageTitle = backend.contentFrame.locator('typo3-backend-editable-page-title');
      await expect(editablePageTitle).toBeAttached({ timeout: 10000 });

      // Click edit button
      await editablePageTitle.evaluate((el: Element) => {
        (el.shadowRoot?.querySelector('[data-action="edit"]') as HTMLElement)?.click();
      });

      // Wait for input and fill new title
      await page.waitForTimeout(300);
      await editablePageTitle.evaluate((el: Element, title: string) => {
        const input = el.shadowRoot?.querySelector('input') as HTMLInputElement;
        if (input) {
          input.value = title;
        }
      }, modifiedPageTitle);

      // Click save button
      await editablePageTitle.evaluate((el: Element) => {
        (el.shadowRoot?.querySelector('[data-action="save"]') as HTMLElement)?.click();
      });

      await page.waitForLoadState('networkidle');
    });

    await test.step('Verify change in Workspaces module', async () => {
      await backend.gotoModule('workspaces_publish');
      await backend.pageTree.open(modifiedPageTitle);

      await workspace.expectIndicatorToShow('Test Workspace');
      await expect(backend.contentFrame.locator('#workspace-panel')).toContainText(modifiedPageTitle);
    });
  });

  test('Publish changes via mass action', async ({ page, backend }) => {
    await backend.gotoModule('workspaces_publish');
    await backend.pageTree.open(modifiedPageTitle);

    await test.step('Select and confirm mass publish', async () => {
      const massActionSelect = backend.contentFrame.locator('select[name=mass-action]');
      await expect(massActionSelect).toBeVisible();
      await massActionSelect.selectOption('Publish');

      const modal = page.locator('typo3-backend-modal[modaltitle="Prepare to start mass action"] > dialog');
      await expect(modal).toBeVisible();
      await modal.locator('button', { hasText: 'Publish' }).click();
      await expect(modal).not.toBeVisible();
    });

    await test.step('Verify changes published', async () => {
      await expect(backend.contentFrame.locator('#workspace-panel')).not.toContainText(modifiedPageTitle);
    });
  });
});
