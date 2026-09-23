import { test, expect } from '../../fixtures/setup-fixtures';
import { openElementsRteEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsRteEditor(backend);
});

/**
 * rte_1 uses the "Default" RTE preset (no richtextConfiguration override,
 * what most content elements get). Covers the flow from
 * https://forge.typo3.org/issues/101503: type content, switch to HTML
 * source mode, switch back, save, and verify nothing was lost.
 */
test('CKEditor content survives switching to source mode and saving', async ({ backend }) => {
  const field = backend.formEngine.elementsBasicFormSection('rte_1');
  const editable = field.locator('.ck-editor__editable[contenteditable="true"]');
  const sourceButton = field.locator('.ck-source-editing-button');
  const sourceTextarea = field.locator('textarea[aria-label="Source code editing area"]');

  await expect(editable).toBeVisible({ timeout: 30000 });

  await editable.click();
  await editable.pressSequentially('TYPO3 ');
  await editable.press('Control+b');
  await editable.pressSequentially('content');
  await editable.press('Control+b');

  await expect(editable.locator('strong', { hasText: 'content' })).toBeVisible();

  await sourceButton.click();
  await expect(sourceTextarea).toBeVisible();
  await expect(sourceTextarea).toHaveValue(/<strong>content<\/strong>/);

  // Switch back to WYSIWYG mode before saving, like an editor would.
  await sourceButton.click();
  await expect(editable.locator('strong', { hasText: 'content' })).toBeVisible();

  await backend.formEngine.save();

  await expect(editable).toBeVisible({ timeout: 30000 });
  await expect(editable.locator('strong', { hasText: 'content' })).toBeVisible();

  await sourceButton.click();
  await expect(sourceTextarea).toHaveValue(/<strong>content<\/strong>/);
});

/**
 * rte_5 uses the "Full" RTE preset, which configures a "Yellow marker"
 * style (a <span class="yellow-marker">). Covers the "classes from
 * selector" case from https://forge.typo3.org/issues/101503: a class
 * assigned via the Styles dropdown must survive the source-mode round
 * trip and saving.
 */
test('CKEditor preserves a class assigned via the Styles dropdown through source mode and save', async ({ backend }) => {
  const field = backend.formEngine.elementsBasicFormSection('rte_5');
  const editable = field.locator('.ck-editor__editable[contenteditable="true"]');
  const sourceButton = field.locator('.ck-source-editing-button');
  const sourceTextarea = field.locator('textarea[aria-label="Source code editing area"]');
  const stylesDropdown = field.getByRole('button', { name: 'Styles' });

  await expect(editable).toBeVisible({ timeout: 30000 });

  // The styleguide fixture pre-fills the field with placeholder text, so select
  // only the text just typed (not the whole field) via Control+a.
  const typedText = 'highlighted text';
  await editable.click();
  await editable.pressSequentially(typedText);
  for (let i = 0; i < typedText.length; i++) {
    await editable.press('Shift+ArrowLeft');
  }

  await stylesDropdown.click();
  await backend.contentFrame.getByRole('option', { name: 'Yellow marker' }).click();

  await expect(editable.locator('span.yellow-marker', { hasText: 'highlighted text' })).toBeVisible();

  await sourceButton.click();
  await expect(sourceTextarea).toHaveValue(/class="yellow-marker"/);

  await sourceButton.click();
  await expect(editable.locator('span.yellow-marker', { hasText: 'highlighted text' })).toBeVisible();

  await backend.formEngine.save();

  await expect(editable).toBeVisible({ timeout: 30000 });
  await expect(editable.locator('span.yellow-marker', { hasText: 'highlighted text' })).toBeVisible();

  await sourceButton.click();
  await expect(sourceTextarea).toHaveValue(/class="yellow-marker"/);
});
