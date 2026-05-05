import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData, ElementsBasicInputValidationData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'input');
});

const simpleCases: ElementsBasicInputTestData[] = [
  {
    label: 'input_1',
    inputValue: 'This is a demo text',
    expectedValue: 'This is a demo text',
    expectedInternalValue: 'This is a demo text',
    expectedValueAfterSave: 'This is a demo text',
  },
  {
    label: 'input_2',
    inputValue: 'This is a demo text with numbers and other characters 42 #!',
    expectedValue: 'This is a demo text with numbers and other characters 42 #!',
    expectedInternalValue: 'This is a demo text with numbers and other characters 42 #!',
    expectedValueAfterSave: 'This is a demo text with numbers and other characters 42 #!',
  },
  {
    label: 'input_3',
    inputValue: 'Kasper',
    expectedValue: 'Kasp',
    expectedInternalValue: 'Kasp',
    expectedValueAfterSave: 'Kasp',
  },
  {
    label: 'input_4',
    inputValue: 'Kasper = TYPO3',
    expectedValue: 'KasperTYPO',
    expectedInternalValue: 'KasperTYPO',
    expectedValueAfterSave: 'KasperTYPO',
  },
  {
    label: 'input_4',
    inputValue: 'Non-latin characters: ŠĐŽĆČ',
    expectedValue: 'Nonlatincharacters',
    expectedInternalValue: 'Nonlatincharacters',
    expectedValueAfterSave: 'Nonlatincharacters',
  },
  {
    label: 'input_5',
    inputValue: 'Kasper = TYPO3',
    expectedValue: 'KasperTYPO3',
    expectedInternalValue: 'KasperTYPO3',
    expectedValueAfterSave: 'KasperTYPO3',
  },
  {
    label: 'input_10',
    inputValue: 'abcd1234',
    expectedValue: 'abc123',
    expectedInternalValue: 'abc123',
    expectedValueAfterSave: 'abc123',
  },
  {
    label: 'input_10',
    inputValue: 'Kasper TYPO3',
    expectedValue: 'a3',
    expectedInternalValue: 'a3',
    expectedValueAfterSave: 'a3',
  },
  {
    label: 'input_11',
    inputValue: 'Kasper TYPO3!',
    expectedValue: 'kasper typo3!',
    expectedInternalValue: 'kasper typo3!',
    expectedValueAfterSave: 'kasper typo3!',
  },
  {
    label: 'input_13',
    inputValue: ' Kasper TYPO3! ',
    expectedValue: 'KasperTYPO3!',
    expectedInternalValue: 'KasperTYPO3!',
    expectedValueAfterSave: 'KasperTYPO3!',
  },
  {
    label: 'input_19',
    inputValue: ' Kasper ',
    expectedValue: 'Kasper',
    expectedInternalValue: 'Kasper',
    expectedValueAfterSave: 'Kasper',
  },
  {
    label: 'input_19',
    inputValue: ' Kasper TYPO3 ',
    expectedValue: 'Kasper TYPO3',
    expectedInternalValue: 'Kasper TYPO3',
    expectedValueAfterSave: 'Kasper TYPO3',
  },
  {
    label: 'input_23',
    inputValue: 'Kasper TYPO3!',
    expectedValue: 'KASPER TYPO3!',
    expectedInternalValue: 'KASPER TYPO3!',
    expectedValueAfterSave: 'KASPER TYPO3!',
  },
];

for (const [index, data] of simpleCases.entries()) {
  test(`elements_basic simple input field [${index}] ${data.label}: ${data.inputValue}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldTest(data);
  });
}

// @todo: Implement special test for read-only field input_40, as it is not testable by runInputFieldValidationTest.
const validationCases: (ElementsBasicInputValidationData & { comment: string })[] = [
  {
    comment: 'Check simple field',
    label: 'input_1',
    testSequence: [
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      { inputValue: 'abcdef', expectedValue: 'abcdef', expectedInternalValue: 'abcdef', expectError: false },
    ],
  },
  {
    comment: 'Check field: size=10',
    label: 'input_2',
    testSequence: [
      { inputValue: '1234567890', expectedValue: '1234567890', expectedInternalValue: '1234567890', expectError: false },
      { inputValue: '1234567890a', expectedValue: '1234567890a', expectedInternalValue: '1234567890a', expectError: false },
    ],
  },
  {
    comment: 'Check validation: max=4',
    label: 'input_3',
    testSequence: [
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      { inputValue: ' ', expectedValue: ' ', expectedInternalValue: ' ', expectError: false },
      // browser blocks input of 5th character
      { inputValue: '     ', expectedValue: '    ', expectedInternalValue: '    ', expectError: false },
      { inputValue: 'a', expectedValue: 'a', expectedInternalValue: 'a', expectError: false },
      { inputValue: 'abc', expectedValue: 'abc', expectedInternalValue: 'abc', expectError: false },
      { inputValue: 'abcd', expectedValue: 'abcd', expectedInternalValue: 'abcd', expectError: false },
      // browser blocks input of 5th character
      { inputValue: 'abcde', expectedValue: 'abcd', expectedInternalValue: 'abcd', expectError: false },
    ],
  },
  {
    comment: 'Check validation: min=4',
    label: 'input_41',
    testSequence: [
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      { inputValue: ' ', expectedValue: ' ', expectedInternalValue: ' ', expectError: true },
      { inputValue: '    ', expectedValue: '    ', expectedInternalValue: '    ', expectError: false },
      { inputValue: 'a', expectedValue: 'a', expectedInternalValue: 'a', expectError: true },
      { inputValue: 'abc', expectedValue: 'abc', expectedInternalValue: 'abc', expectError: true },
      { inputValue: 'abcd', expectedValue: 'abcd', expectedInternalValue: 'abcd', expectError: false },
      { inputValue: 'abcde', expectedValue: 'abcde', expectedInternalValue: 'abcde', expectError: false },
    ],
  },
  {
    comment: 'Check validation: min=4, max=8',
    label: 'input_42',
    testSequence: [
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      { inputValue: 'abc', expectedValue: 'abc', expectedInternalValue: 'abc', expectError: true },
      { inputValue: 'abcd', expectedValue: 'abcd', expectedInternalValue: 'abcd', expectError: false },
      { inputValue: 'abcde', expectedValue: 'abcde', expectedInternalValue: 'abcde', expectError: false },
      { inputValue: 'abcdefg', expectedValue: 'abcdefg', expectedInternalValue: 'abcdefg', expectError: false },
      { inputValue: 'abcdefgh', expectedValue: 'abcdefgh', expectedInternalValue: 'abcdefgh', expectError: false },
      // browser blocks input of 9th character
      { inputValue: 'abcdefghi', expectedValue: 'abcdefgh', expectedInternalValue: 'abcdefgh', expectError: false },
    ],
  },
  {
    comment: 'Check validation: min=4, max=4',
    label: 'input_43',
    testSequence: [
      { inputValue: '', expectedValue: '', expectedInternalValue: '', expectError: false },
      { inputValue: 'abc', expectedValue: 'abc', expectedInternalValue: 'abc', expectError: true },
      { inputValue: 'abcd', expectedValue: 'abcd', expectedInternalValue: 'abcd', expectError: false },
      // browser blocks input of 5th character
      { inputValue: 'abcde', expectedValue: 'abcd', expectedInternalValue: 'abcd', expectError: false },
    ],
  },
];

for (const data of validationCases) {
  test(`elements_basic simple input validation: ${data.label} - ${data.comment}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldValidationTest(data);
  });
}
