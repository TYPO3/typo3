import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'password');
});

// @todo
// + server-side password obfuscation value is `*********` (9 chars)
// + client-side password obfuscation value is `********` (8 chars)
// @todo add other password field variants
const passwordCases: ElementsBasicInputTestData[] = [
  {
    label: 'password_2',
    inputValue: 'Kasper',
    expectedValue: '********',
    expectedInternalValue: 'Kasper',
    // even if `password_2` is not hashed, it never should expose the value
    expectedValueAfterSave: '*********',
  },
];

for (const data of passwordCases) {
  test(`elements_basic password input field: ${data.label}`, async ({ backend }) => {
    await backend.formEngine.runInputFieldTest(data);
  });
}
