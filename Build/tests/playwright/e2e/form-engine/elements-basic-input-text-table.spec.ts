import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'text');
});

function tableSection(backend): Locator {
  return backend.formEngine.elementsBasicFormSection('text_17');
}

test('elements_basic text_17 table wizard shows seeded content', async ({ backend }) => {
  const expectedRows = [
    ['row1 col1', 'row1 col2'],
    ['row2 col1', 'row2 col2'],
    ['row3 col1', 'row3 col2'],
  ];
  const section = tableSection(backend);
  for (const [rowIndex, row] of expectedRows.entries()) {
    for (const [colIndex, value] of row.entries()) {
      await expect(section.locator(`input[data-row="${rowIndex}"][data-col="${colIndex}"]`)).toHaveValue(value);
    }
  }
});

test('elements_basic text_17 table wizard adds and removes columns and rows', async ({ backend }) => {
  const section = tableSection(backend);
  const cellInputs = section.locator('typo3-formengine-table-wizard td input');

  await section.locator('tr > th:nth-child(2) button[title="Add column to the right"]').click();
  await expect(cellInputs).toHaveCount(9);

  await section.locator('tr > th:nth-child(2) button[title="Remove column"]').click();
  await expect(cellInputs).toHaveCount(6);

  await section.locator('tbody tr:first-child > td button[title="Add row below"]').click();
  await expect(cellInputs).toHaveCount(8);

  await section.locator('tbody tr:first-child > td button[title="Remove row"]').click();
  await expect(cellInputs).toHaveCount(6);
});

test('elements_basic text_17 table wizard moves columns and rows', async ({ backend }) => {
  const section = tableSection(backend);
  const cell = (row: number, col: number) => section.locator(`input[data-row="${row}"][data-col="${col}"]`);

  await cell(0, 0).fill('Test Column 1');
  await cell(0, 1).fill('Test Column 2');

  // Move column right: column 1's content should now sit in position 2.
  const beforeRight = await cell(0, 0).inputValue();
  await section.locator('tr > th:nth-child(2) button[title="Move right"]').click();
  await backend.formEngine.save();
  await expect(cell(0, 1)).toHaveValue(beforeRight);

  // Move column left: column 3's (now position 2) content should return to position 1.
  const beforeLeft = await cell(0, 1).inputValue();
  await section.locator('tr > th:nth-child(3) button[title="Move left"]').click();
  await backend.formEngine.save();
  await expect(cell(0, 0)).toHaveValue(beforeLeft);

  // Move row down: row 0 swaps with row 1.
  const beforeDown = await cell(0, 0).inputValue();
  await section.locator('tbody tr:first-child > td button[title="Move down"]').click();
  await backend.formEngine.save();
  await expect(cell(1, 0)).toHaveValue(beforeDown);

  // Move row up: row 2's content moves to row 1.
  const beforeUp = await cell(2, 0).inputValue();
  await section.locator('tbody tr:nth-child(3) > td button[title="Move up"]').click();
  await backend.formEngine.save();
  await expect(cell(1, 0)).toHaveValue(beforeUp);
});

test('elements_basic text_17 table wizard toggles small fields between input and textarea', async ({ backend }) => {
  const section = tableSection(backend);
  const smallFieldsButton = section.locator('typo3-formengine-table-wizard button[title="Small fields"]');

  await smallFieldsButton.click();
  await expect(section.locator('typo3-formengine-table-wizard td textarea')).toHaveCount(6);

  await smallFieldsButton.click();
  await expect(section.locator('typo3-formengine-table-wizard td input')).toHaveCount(6);
});

test('elements_basic text_17 table wizard reduces seeded grid', async ({ backend }) => {
  const section = tableSection(backend);
  // Styleguide creates 3 rows × 2 columns (= 6 inputs). Removing 1 row and 1
  // column leaves 2 rows × 1 column (= 2 inputs).
  await section.locator('button[title="Remove column"]').first().click();
  await section.locator('button[title="Remove row"]').first().click();
  await expect(section.locator('typo3-formengine-table-wizard input')).toHaveCount(2);
});
