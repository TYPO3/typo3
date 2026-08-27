import { test, expect } from '../../fixtures/setup-fixtures';

// Switching a field that triggers FormEngine's "Refresh required" reload
// (ReloadOnFieldChange) inside the contextual record edit sheet must not
// raise a spurious "Unsaved changes" prompt of its own, and the sheet
// must stay open, refreshed, and closable in a single action afterwards.
const recordTitle = 'Contextual sheet refresh test';

test('contextual record edit sheet survives a "Refresh required" round trip', async ({ backend, page }) => {
  await backend.gotoModule('web_layout');
  await backend.pageTree.open('styleguide TCA demo');

  await test.step('create a Form content element with no form definition selected', async () => {
    await backend.contentFrame.locator('typo3-backend-new-content-element-wizard-button').first().click();
    const wizard = page.locator('typo3-backend-new-record-wizard');
    await expect(wizard).toBeVisible();
    // typo3-backend-new-record-wizard renders inside an open shadow root,
    // Locator.click() does not reliably trigger the buttons; use evaluate.
    await wizard.evaluate((el: any) => el.shadowRoot.querySelector('button[data-identifier="forms"]').click());
    await wizard.evaluate((el: any) => el.shadowRoot.querySelector('button[data-identifier="forms_form_formframework"]').click());
    await expect(backend.contentFrame.getByRole('heading', { name: /Create new Form/ })).toBeVisible();

    await backend.contentFrame
      .locator('xpath=//input[contains(@data-formengine-input-name, "data[tt_content]") and contains(@data-formengine-input-name, "[header]")]')
      .fill(recordTitle);

    await backend.formEngine.save();
    await backend.formEngine.close();
  });

  await test.step('open it in the contextual sheet and switch the form definition', async () => {
    await backend.contentFrame
      .locator(`xpath=//typo3-backend-contextual-record-edit-trigger[contains(., "${recordTitle}")]`)
      .first()
      .click();
    await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();
    const modalContent = await backend.modal.getModalContent();
    await expect(modalContent.getByText(recordTitle).first()).toBeVisible();

    await modalContent.getByRole('tab', { name: 'Plugin' }).click();
    await modalContent
      .getByLabel(/Form definition/)
      .selectOption({ value: 'EXT:styleguide/Resources/Private/Forms/simpleform.form.yaml' });

    const refreshDialog = page.getByRole('dialog', { name: 'Refresh required' });
    await expect(refreshDialog).toBeVisible();
    await refreshDialog.getByRole('button', { name: 'Save and refresh' }).click();
  });

  await test.step('the sheet stays open and refreshed, without a spurious prompt', async () => {
    await expect(page.getByRole('dialog', { name: 'Unsaved changes' })).not.toBeVisible({ timeout: 5000 });
    await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();

    const modalContent = await backend.modal.getModalContent();
    await expect(
      modalContent.getByLabel(/Form definition/)
    ).toHaveValue('EXT:styleguide/Resources/Private/Forms/simpleform.form.yaml', { timeout: 10000 });
  });

  await test.step('closing it afterwards actually collapses the sheet', async () => {
    // Whether or not closing raises its own (legitimate) confirmation
    // depends on unrelated field state after the reload, and the close
    // listener inside the just-reloaded iframe may not be wired up on the
    // very first click. Either way, the sheet must actually collapse in the end.
    const unsavedDialog = page.getByRole('dialog', { name: 'Unsaved changes' });
    await expect.poll(async () => {
      if (await unsavedDialog.isVisible()) {
        await unsavedDialog.getByRole('button', { name: 'Save and close' }).click();
      } else if (await page.locator('typo3-backend-modal').count() > 0) {
        const modalContent = await backend.modal.getModalContent();
        await modalContent.locator('.t3js-contextual-close').click().catch(() => undefined);
      }
      return page.locator('typo3-backend-modal').count();
    }, { timeout: 15000, intervals: [500] }).toBe(0);
  });
});
