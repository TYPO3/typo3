import { test, expect } from '../../fixtures/setup-fixtures';
import { FrameLocator } from '@playwright/test';
import { BackendPage } from '../../fixtures/backend-page';

test.describe('Backend Users module', () => {
  const fixtureUsers = {
    admin: 'Klaus Admin (admin)',
    editor: 'editor',
  };

  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('backend_user_management');

    const contentFrame = backend.contentFrame;
    await contentFrame.locator('.t3js-module-docheader-buttons').locator('.dropdown-toggle', { hasText: 'Module Menu:' }).click();
    const moduleResponse = backend.waitForModuleResponse();
    await contentFrame.locator('.t3js-module-docheader-buttons .dropdown-menu').getByRole('link', { name: 'Backend users' }).click();
    await moduleResponse;

    await expect(contentFrame.locator('h1')).toContainText('Backend users');
    const resetResponse = backend.waitForModuleResponse();
    await contentFrame.locator('button[value="reset-filters"]').click();
    await resetResponse;
    await expectFixtureUsers(contentFrame);

    // Clear compare list if it exists to prevent state leakage between tests
    const clearButton = contentFrame.getByRole('button', { name: 'Clear compare list' });
    if (await clearButton.isVisible()) {
      await clearButton.click();
      // Wait for the compare list table to disappear
      await expect(contentFrame.locator('#typo3-backend-user-list-compare')).not.toBeVisible();
    }
  });


  test('Shows heading and lists backend users', async ({ backend }) => {
    await expectFixtureUsers(backend.contentFrame);
  });

  test('Filter users by username', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    const inputUsername = contentFrame.getByLabel('Username');

    await expectFixtureUsers(contentFrame);

    // Filter the list of user by valid username admin
    await inputUsername.fill('admin');

    const filterButton = contentFrame.getByRole('button', { name: 'Filter' });

    // Wait for filter HTTP response
    const filterResponse1 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse1;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    // We expect exact one fitting Backend User created from the Fixtures
    await checkCountOfUsers(contentFrame, 1);

    // Filter the list of user by valid username administrator
    await inputUsername.fill('administrator');

    // Wait for filter HTTP response
    const filterResponse2 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse2;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    // We expect exact no fitting Backend User created from the Fixtures
    await checkCountOfUsers(contentFrame, 0);
  });

  test('Filter users by admin', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();
    await expectFixtureUsers(contentFrame);

    await contentFrame.locator('#tx_Beuser_usertype').selectOption('Admin');
    const filterButton = contentFrame.getByRole('button', { name: 'Filter' });

    // Wait for filter HTTP response
    const filterResponse1 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse1;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUser(contentFrame, fixtureUsers.admin, true);
    await expectFixtureUser(contentFrame, fixtureUsers.editor, false);

    await contentFrame.locator('#tx_Beuser_usertype').selectOption('Normal user');

    // Wait for filter HTTP response
    const filterResponse2 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse2;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();
    await expectFixtureUser(contentFrame, fixtureUsers.admin, false);
    await expectFixtureUser(contentFrame, fixtureUsers.editor, true);
  });

  test('Filter users by status', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    // Reset filters and wait for completion
    const resetResponse = backend.waitForModuleResponse();
    await contentFrame.locator('button[value="reset-filters"]').click();
    await resetResponse;

    await expectFixtureUsers(contentFrame);

    const filterButton = contentFrame.getByRole('button', { name: 'Filter' });
    await contentFrame.locator('#tx_Beuser_status').selectOption('Enabled');

    // Wait for filter HTTP response
    const filterResponse1 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse1;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUsers(contentFrame);

    await contentFrame.locator('#tx_Beuser_status').selectOption('Disabled');

    // Wait for filter HTTP response
    const filterResponse2 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse2;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUser(contentFrame, fixtureUsers.admin, false);
    await expectFixtureUser(contentFrame, fixtureUsers.editor, false);
  });

  test('Filter users by login', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUsers(contentFrame);

    const filterButton = contentFrame.getByRole('button', { name: 'Filter' });
    await contentFrame.locator('#tx_Beuser_logins').selectOption('Logged in before');

    // Wait for filter HTTP response
    const filterResponse1 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse1;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUsers(contentFrame);

    await contentFrame.locator('#tx_Beuser_logins').selectOption('Never logged in');

    // Wait for filter HTTP response
    const filterResponse2 = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse2;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUser(contentFrame, fixtureUsers.admin, false);
    await expectFixtureUser(contentFrame, fixtureUsers.editor, false);
  });

  test('Filter users by user group', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUsers(contentFrame);

    // Expect one Backend User created from the Fixtures has the usergroup named 'editor-group'
    const filterButton = contentFrame.getByRole('button', { name: 'Filter' });
    const selectGroup = contentFrame.getByLabel('Group');
    await selectGroup.selectOption('editor-group');

    // Wait for filter HTTP response
    const filterResponse = backend.waitForModuleResponse();
    await filterButton.click();
    await filterResponse;

    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    await expectFixtureUser(contentFrame, fixtureUsers.admin, false);
    await expectFixtureUser(contentFrame, fixtureUsers.editor, true);
  });

  test('Can edit users from index list view', async ({ page }) => {
    const contentFrame = page.frameLocator('#typo3-contentIframe');
    await expect(contentFrame.locator('h1')).toContainText('Backend users');
    await expect(contentFrame.locator('#typo3-backend-user-list')).toBeVisible();

    const username = 'admin';
    const adminRow = getFixtureUserRow(contentFrame, fixtureUsers.admin);

    await contentFrame.locator('button[value="reset-filters"]').click();

    await test.step('test the edit button', async () => {
      await adminRow.getByRole('button', { name: 'Edit' }).click();
      await openAndCloseTheEditForm(contentFrame, username);
    });

    await test.step('test the edit link', async () => {
      await contentFrame.getByRole('button', { name: 'Reset' }).click();
      await adminRow.getByRole('link', { name: 'Klaus Admin (admin)' }).click();
      await openAndCloseTheEditForm(contentFrame, username);
    });
  });

  test('Editing BE user records from compare view works', async ({ backend }) => {
    const contentFrame = backend.contentFrame;

    await test.step('Put two users into compare list', async () => {
      await getFixtureUserRow(contentFrame, fixtureUsers.admin).getByText('Compare').click();
      await expect(contentFrame.locator('#typo3-backend-user-list-compare tbody').getByRole('row')).toHaveCount(1);

      await getFixtureUserRow(contentFrame, fixtureUsers.editor).getByText('Compare').click();

      await expect(contentFrame.locator('#typo3-backend-user-list-compare tbody').getByRole('row')).toHaveCount(2);

      await contentFrame.locator('body > div > div.module-body.t3js-module-body .t3js-acceptance-compare').click();
      await expect(contentFrame.locator('table.table-striped-columns')).toBeVisible();
    });

    await test.step('First user can be edited', async () => {
      await contentFrame.getByRole('columnheader', { name: /^admin \[\d+]$/ }).getByRole('link', { name: 'Edit' }).click();

      await expect(contentFrame.locator('#EditDocumentController')).toBeVisible();
      await expect(contentFrame.locator('h1')).toContainText('admin');
    });

    await test.step('Go back to compare view', async () => {
      await backend.formEngine.close();

      await expect(contentFrame.locator('table.table-striped-columns')).toBeVisible();
      await expect(contentFrame.locator('h1')).toContainText('Compare backend users');
    });

    await test.step('Second user can be edited', async () => {
      await contentFrame.getByRole('columnheader', { name: /^editor \[\d+]$/ }).getByRole('link', { name: 'Edit' }).click();

      await expect(contentFrame.locator('#EditDocumentController')).toBeVisible();
      await expect(contentFrame.locator('h1')).toContainText('editor');

      await contentFrame.getByRole('button', { name: 'Go back' });
    });

    await test.step('Remove all users from compare list', async () => {
      await backend.gotoModule('backend_user_management');

      const compareList = contentFrame.locator('#typo3-backend-user-list-compare');
      const editorRow = compareList.locator('tbody > tr', {
        has: contentFrame.getByRole('link', { name: fixtureUsers.editor, exact: true }),
      });
      await editorRow.getByTitle('Remove from compare list').click();
      await expect(editorRow).not.toBeVisible();

      const adminRow = compareList.locator('tbody > tr', {
        has: contentFrame.getByRole('link', { name: fixtureUsers.admin, exact: true }),
      });
      await adminRow.getByTitle('Remove from compare list').click();
      await expect(contentFrame.locator('#typo3-backend-user-list-compare')).not.toBeVisible();
    });
  });

  async function checkCountOfUsers(contentFrame: FrameLocator, countOfUsers: number) {
    // tbody might be hidden when there are no results
    if (countOfUsers > 0) {
      await expect(contentFrame.locator('#typo3-backend-user-list tbody')).toBeVisible();
    }
    await expect(contentFrame.locator('#typo3-backend-user-list tbody > tr')).toHaveCount(countOfUsers);
    await expect(contentFrame.locator('#typo3-backend-user-list tfoot tr')).toHaveCount(1);
    await expect(contentFrame.locator('#typo3-backend-user-list tfoot tr')).toContainText(countOfUsers + ' User');
  }

  async function expectFixtureUsers(contentFrame: FrameLocator) {
    await expectFixtureUser(contentFrame, fixtureUsers.admin, true);
    await expectFixtureUser(contentFrame, fixtureUsers.editor, true);
  }

  async function expectFixtureUser(contentFrame: FrameLocator, name: string, visible: boolean) {
    if (visible) {
      await expect(getFixtureUserRow(contentFrame, name)).toBeVisible();
    } else {
      await expect(getFixtureUserRow(contentFrame, name)).not.toBeVisible();
    }
  }

  function getFixtureUserRow(contentFrame: FrameLocator, name: string) {
    return contentFrame.locator('#typo3-backend-user-list tbody > tr', {
      has: contentFrame.getByRole('link', { name, exact: true }),
    });
  }

  async function openAndCloseTheEditForm(contentFrame: FrameLocator, username: string) {
    await expect(contentFrame.locator('#t3js-ui-block')).not.toBeVisible();
    await expect(contentFrame.locator('h1')).toContainText(username);

    // Wait for FormEngine to be fully loaded by checking for the close button to be visible
    await expect(contentFrame.locator('div.module-docheader .btn.t3js-editform-close')).toBeVisible();
    await contentFrame.locator('div.module-docheader .btn.t3js-editform-close').click();

    // Wait for user list to appear after closing form
    await expect(contentFrame.locator('#typo3-backend-user-list.table.table-striped')).toBeVisible();
    await expect(contentFrame.locator('h1')).toContainText('Backend users');
  }
});

test.describe('Backend user group module', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(async ({ backend }) => {
    await backend.gotoModule('backend_user_management');

    const contentFrame = backend.contentFrame;
    await contentFrame.locator('.t3js-module-docheader-buttons .dropdown-toggle', { hasText: 'Module Menu:' }).click();
    await contentFrame.locator('.t3js-module-docheader-buttons').getByRole('link', { name: 'Backend user groups' }).click();

    // Wait for the module to fully load
    await backend.waitForModuleResponse();

    await expect(contentFrame.locator('h1')).toContainText('Backend user groups');

    // Reset filters and wait for the operation to complete
    const resetResponse = backend.waitForModuleResponse();
    await contentFrame.locator('button[value="reset-filters"]').click();
    await resetResponse;

    const clearButton = contentFrame.getByRole('button', { name: 'Clear compare list' });
    if (await clearButton.isVisible()) {
      await clearButton.click();
      // Wait for the compare list table to disappear
      await expect(contentFrame.locator('#typo3-backend-user-list-compare')).not.toBeVisible();
    }
  });

  test('Can edit BE groups from list view', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    const group = contentFrame.locator('table.table-striped > tbody > tr:nth-child(1) > td.col-50 > a');
    const groupName = await group.innerText();

    await group.click();
    await openAndCloseTheEditForm(backend, groupName);

    await contentFrame.locator('table.table-striped > tbody > tr:nth-child(1) > td.col-control > div:nth-child(1) > a:nth-child(1)').click();
    await openAndCloseTheEditForm(backend, groupName);
  });

  test('Can edit sub group from list view', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    const group = contentFrame.locator('table.table-striped > tbody > tr:nth-child(1) > td.col-50 > a');
    const groupName = await group.innerText();
    await group.click();

    await openAndCloseTheEditForm(backend, groupName);
  });

  test('Accessing group compare view works', async ({ backend }) => {
    const contentFrame = backend.contentFrame;
    await addGroupToCompareList(backend, 1);
    await addGroupToCompareList(backend, 2);
    await addGroupToCompareList(backend, 3);

    await expect(contentFrame.locator('.t3js-acceptance-compare')).toBeAttached();

    const wait = backend.waitForModuleResponse();
    await contentFrame.locator('.t3js-acceptance-compare').click();
    await wait;

    await expect(contentFrame.locator('h1')).toContainText('Compare backend user groups');
    await expect(contentFrame.locator('.beuser-comparison-table > thead th')).toHaveCount(3);

    await contentFrame.getByRole('button', { name: 'Go back' }).click();
    await contentFrame.getByRole('button', { name: 'Clear compare list' }).click();

    await expect(contentFrame.locator('#typo3-backend-user-list-compare')).not.toBeAttached();
  });

  async function openAndCloseTheEditForm(backend: BackendPage, username: string) {
    const contentFrame = backend.contentFrame;

    await expect(contentFrame.locator('#t3js-ui-block')).not.toBeVisible();
    await expect(contentFrame.locator('h1')).toContainText(username);

    // Wait for module to reload after closing form
    const moduleResponse = backend.waitForModuleResponse();
    await contentFrame.locator('div.module-docheader .btn.t3js-editform-close').click();
    await moduleResponse;

    await expect(contentFrame.locator('#typo3-backend-user-group-list.table.table-striped')).toBeVisible();
    await expect(contentFrame.locator('h1')).toContainText('Backend user groups');
  }

  async function addGroupToCompareList(backend: BackendPage, count: number) {
    const addToCompare = backend.contentFrame.locator('#typo3-backend-user-group-list [form="form-add-group-to-compare-list"]');

    await expect(addToCompare.getByText('Compare').first()).toBeEnabled();

    const wait = backend.waitForModuleResponse();
    await addToCompare.getByText('Compare').first().click();
    await wait;

    await expect(backend.contentFrame.locator('#typo3-backend-user-list-compare tbody').getByRole('row')).toHaveCount(count);
  }
});
