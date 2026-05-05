import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData, ElementsBasicInputValidationData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'number');
});

// @todo: Some cases (12,335 with comma, "TYPO3", "3TYPO") are intentionally
// missing - the webdriver swallowed the comma / non-numeric chars on those.
const numberCases: ElementsBasicInputTestData[] = [
  {
    label: 'number_1',
    inputValue: '12.335',
    expectedValue: '12.34',
    expectedInternalValue: '12.34',
    expectedValueAfterSave: '12.34',
  },
  {
    label: 'number_1',
    inputValue: '1.1',
    expectedValue: '1.10',
    expectedInternalValue: '1.10',
    expectedValueAfterSave: '1.10',
  },
  {
    label: 'number_2',
    inputValue: '12.335',
    expectedValue: '12',
    expectedInternalValue: '12',
    expectedValueAfterSave: '12',
  },
];

for (const [index, data] of numberCases.entries()) {
  test(`elements_basic number field [${index}] ${data.label}: ${data.inputValue}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldTest(data);
  });
}

const numberValidation: (ElementsBasicInputValidationData & { comment: string })[] = [
  {
    comment: 'Check number field on browser-native validation-error bad-input',
    label: 'number_2',
    testSequence: [
      // Prepare this special test-case: set the input to empty so the next
      // step's `change` event does not fire on the same value.
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      // `1-2` triggers Chrome's native `bad input` for number fields. Chrome
      // still displays `1-2` to the user but reports an empty value.
      // @todo: This should show a FormEngine validation-error
      { inputValue: '1-2', expectedValue: '', expectedInternalValue: '', expectError: false },
      // Empty string should clear the displayed error.
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
    ],
  },
];

for (const data of numberValidation) {
  test(`elements_basic number validation: ${data.label} - ${data.comment}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldValidationTest(data);
  });
}
