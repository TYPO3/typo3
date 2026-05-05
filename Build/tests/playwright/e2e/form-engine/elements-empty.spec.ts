import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicRadioTestData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'radio');
});

const radioCases: (ElementsBasicRadioTestData & { comment: string })[] = [
  { label: 'radio_4', inputValue: 'foo', expectedValue: true, comment: 'Existing radio, selectable' },
  { label: 'radio_4', inputValue: '', expectedValue: true, comment: 'Existing radio, empty selectable' },
  { label: 'radio_4', inputValue: 'foob', expectedValue: false, comment: 'Existing radio, invalid value' },
  { label: 'non_existing_radio_4', inputValue: 'foo', expectedValue: false, comment: 'Non-existing radio' },
];

for (const data of radioCases) {
  test(`elements_basic radio field ${data.label} value=${data.inputValue || '<empty>'}: ${data.comment}`, async ({ backend }) => {
    await backend.formEngine.runRadioFieldTest(data);
  });
}
