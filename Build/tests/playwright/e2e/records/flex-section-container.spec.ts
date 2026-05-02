import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo', 'flex');
});

test('Flex form section container with dot in field name expands on click', async ({ backend }) => {
  const formEngineReady = backend.formEngine.formEngineLoaded();
  // Filter excludes the "create new" link in the same records list.
  await backend.contentFrame
    .locator('a[href*="edit%5Btx_styleguide_flex%5D"][href*="%5D=edit"]')
    .first()
    .click();
  await formEngineReady;

  await backend.contentFrame.getByRole('tab', { name: 'section container' }).click();
  await backend.contentFrame.getByRole('tab', { name: 'section2', exact: true }).click();

  const panelButton = backend.contentFrame
    .getByRole('tabpanel', { name: 'section2', exact: true })
    .getByRole('button', { name: 'container_1', exact: true });
  await expect(panelButton).toHaveAttribute('aria-expanded', 'false');
  await panelButton.click();
  await expect(panelButton).toHaveAttribute('aria-expanded', 'true');
});
