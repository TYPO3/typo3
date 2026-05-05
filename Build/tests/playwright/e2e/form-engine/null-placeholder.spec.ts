import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'file',
    listId: 'tx_styleguide_file',
  });
});

test('null placeholder checkboxes mark fields as changed when activated', async ({ backend }) => {
  await backend.contentFrame.getByRole('tab', { name: 'typical fal', exact: true }).click();
  // Scope to active pane so `.first()` cannot land in a hidden tab pane.
  await backend.contentFrame.locator('.tab-pane.active .form-irre-object .panel-button').first().click();
  await expect(backend.contentFrame.locator('typo3-backend-progress-bar')).not.toBeVisible();

  for (const fieldName of ['title', 'alternative', 'description']) {
    const checkbox = backend.contentFrame.locator(
      `xpath=//input[contains(@name, "[${fieldName}]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]`
    );
    await checkbox.check();
    await expect(checkbox).toHaveClass(/has-change/);

    // Blur this field so the next checkbox is reachable.
    await backend.contentFrame.locator('.form-irre-object .form-section').first().click();
  }
});
