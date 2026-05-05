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
    comment: 'email_1 normalisation and tolerated invalid syntax',
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
      // @todo: This should show a FormEngine validation-error
      {
        inputValue: 'invalid-email-syntax',
        expectedValue: 'invalid-email-syntax',
        expectedInternalValue: 'invalid-email-syntax',
        expectError: false,
      },
    ],
  },
];

for (const data of emailValidation) {
  test(`elements_basic email validation: ${data.label} - ${data.comment}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldValidationTest(data);
  });
}

test('elements_basic email_5 value picker fills field with chosen option', async ({ backend, page }) => {
  const formSection = backend.formEngine.elementsBasicFormSection('email_5');
  const input = formSection.locator('input[data-formengine-input-name]').first();
  await input.click();
  await page.keyboard.press('ArrowDown');
  await page.keyboard.press('Enter');
  await expect(input).toHaveValue('info@example.org');
});
