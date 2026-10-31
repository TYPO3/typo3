import { test, expect } from '../../fixtures/setup-fixtures';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test('color picker shows the labels of presets next to the colors', async ({ backend }) => {
  await openElementsBasicEditor(backend, 'color');

  await backend.contentFrame.locator('input[data-formengine-input-name$="[color_2]"]').click();

  const swatch = backend.contentFrame.locator('.alwan__swatch[title="typo3 orange (#FF8700)"]');
  await expect(swatch).toBeVisible();
  // The label is rendered by CSS from the title attribute, it is no text node.
  const label = await swatch.evaluate((el) => getComputedStyle(el, '::after').content);
  expect(label).toBe('"typo3 orange (#FF8700)"');
});
