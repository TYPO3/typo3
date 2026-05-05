import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  // Open without a tab; each case clicks its own tab below.
  await openElementsBasicEditor(backend);
});

// @todo: case `number_3` with input "Kasper TYPO3" is intentionally
// missing - it likely broke with the type="number" patch.
const rangeAndMd5Cases: (ElementsBasicInputTestData & { tab: string })[] = [
  {
    tab: 'number',
    label: 'number_3',
    inputValue: '2',
    expectedValue: '2',
    expectedInternalValue: '2',
    expectedValueAfterSave: '2',
  },
  {
    tab: 'number',
    label: 'number_3',
    inputValue: '-1',
    expectedValue: '-1',
    expectedInternalValue: '-1',
    expectedValueAfterSave: '-1',
  },
  {
    tab: 'input',
    label: 'input_12',
    inputValue: 'Kasper TYPO3!',
    expectedValue: '748469dd64911af8df8f9a3dcb2c9378',
    expectedInternalValue: '748469dd64911af8df8f9a3dcb2c9378',
    expectedValueAfterSave: '748469dd64911af8df8f9a3dcb2c9378',
  },
  {
    tab: 'input',
    label: 'input_12',
    // Whitespaces are not trimmed.
    inputValue: ' Kasper TYPO3! ',
    expectedValue: '792a085606250c47d6ebb8c98804d5b0',
    expectedInternalValue: '792a085606250c47d6ebb8c98804d5b0',
    expectedValueAfterSave: '792a085606250c47d6ebb8c98804d5b0',
  },
];

for (const [index, data] of rangeAndMd5Cases.entries()) {
  test(`elements_basic range/md5 [${index}] ${data.label}: ${data.inputValue}`, async ({ backend }) => {
    await backend.contentFrame.getByRole('tab', { name: data.tab, exact: true }).click();
    await backend.formEngine.runInputFieldTest(data);
  });
}
