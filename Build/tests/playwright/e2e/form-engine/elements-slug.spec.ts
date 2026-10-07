import { test, expect } from '../../fixtures/setup-fixtures';
import { openStyleguideTcaEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openStyleguideTcaEditor(backend, {
    pageName: 'elements slugs',
    listId: 'tx_styleguide_elements_slugs',
  });
});

test('required slug field is valid after recreating the slug', async ({ backend }) => {
  const formSection = backend.formEngine.elementsBasicFormSection('slug_8');
  const slugInput = formSection.locator('.t3js-form-field-slug-input');
  const hiddenField = formSection.locator('.t3js-form-field-slug-hidden');

  await formSection.locator('.t3js-form-field-slug-toggle').click();
  await slugInput.fill('');
  await expect(slugInput).toHaveClass(/has-error/);

  await formSection.locator('.t3js-form-field-slug-recreate').click();
  await expect(hiddenField).not.toHaveValue('');
  await expect(slugInput).not.toHaveClass(/has-error/);
});
