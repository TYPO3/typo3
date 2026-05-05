import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'input');
});

const evalCases: ElementsBasicInputTestData[] = [
  {
    label: 'input_15',
    inputValue: '12.335',
    expectedValue: '12335',
    expectedInternalValue: '12335',
    expectedValueAfterSave: '12335',
  },
  {
    label: 'input_15',
    inputValue: '12,9',
    expectedValue: '129',
    expectedInternalValue: '129',
    expectedValueAfterSave: '129',
  },
  {
    label: 'input_15',
    inputValue: 'TYPO3',
    expectedValue: '3',
    expectedInternalValue: '3',
    expectedValueAfterSave: '3',
  },
  {
    label: 'input_15',
    inputValue: '3TYPO',
    expectedValue: '3',
    expectedInternalValue: '3',
    expectedValueAfterSave: '3',
  },
];

for (const [index, data] of evalCases.entries()) {
  test(`elements_basic eval input [${index}] ${data.label}: ${data.inputValue}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldTest(data);
  });
}
