import { test, expect } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData, ElementsBasicInputValidationData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'email');
});

const emailCases: ElementsBasicInputTestData[] = [
  {
    label: 'email_1',
    inputValue: 'foo@example.com',
    expectedValue: 'foo@example.com',
    expectedInternalValue: 'foo@example.com',
    expectedValueAfterSave: 'foo@example.com',
  },
];

for (const data of emailCases) {
  test(`elements_basic email field: ${data.label}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldTest(data);
  });
}

const emailValidation: (ElementsBasicInputValidationData & { comment: string })[] = [
  {
    comment: 'email_1 normalisation and invalid syntax',
    label: 'email_1',
    testSequence: [
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      { inputValue: ' ', expectedValue: '', expectedInternalValue: '', expectError: false },
      {
        inputValue: ' spaces-around@example.com  ',
        expectedValue: 'spaces-around@example.com',
        expectedInternalValue: 'spaces-around@example.com',
        expectError: false,
      },
      {
        inputValue: 'invalid-email-syntax',
        expectedValue: 'invalid-email-syntax',
        expectedInternalValue: 'invalid-email-syntax',
        expectError: true,
      },
    ],
  },
];

for (const data of emailValidation) {
  test(`elements_basic email validation: ${data.label} - ${data.comment}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldValidationTest(data);
  });
}

test('elements_basic email with invalid syntax marks its tab when saving from another tab', async ({ backend, page }) => {
  const input = backend.formEngine.elementsBasicFormSection('email_1').locator('input[data-formengine-input-name]').first();
  await input.fill('invalid-email-syntax');
  await input.blur();

  await backend.contentFrame.getByRole('tab', { name: 'input', exact: true }).click();
  await backend.formEngine.saveButton.click();

  await expect(page.getByRole('dialog', { name: 'Alert' })).toBeVisible();
  await backend.modal.click({ name: 'ok' });
  // The tab label gains an "(contains invalid entries)" suffix once it is marked
  const emailTab = backend.contentFrame.locator('.nav-item').filter({
    has: backend.contentFrame.getByRole('tab', { name: /^email\b/ }),
  });
  await expect(emailTab).toHaveClass(/has-validation-error/);
  await expect(input).toHaveClass(/has-error/);
});

test('elements_basic email_5 value picker fills field with chosen option', async ({ backend, page }) => {
  const formSection = backend.formEngine.elementsBasicFormSection('email_5');
  const input = formSection.locator('input[data-formengine-input-name]').first();
  await input.click();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');
  await expect(input).toHaveValue('info@example.org');
});
