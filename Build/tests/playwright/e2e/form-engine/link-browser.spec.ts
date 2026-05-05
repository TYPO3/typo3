import { test, expect } from '../../fixtures/setup-fixtures';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'link');
});

test('link browser modal closes on Escape key', async ({ backend, page }) => {
  // Open the link wizard via the field-control button next to the link input.
  await backend.contentFrame.locator('.tab-pane.active .form-wizards-item-aside--field-control').first().click();

  await expect(page.locator('.t3js-modal-iframe')).toBeAttached();
  const modalFrame = page.frameLocator('.t3js-modal-iframe');

  // Press Escape on the iframe input directly. `page.keyboard.press` would
  // target whatever owns focus at the moment the key fires, which can drift
  // between the iframe input and the parent page after fill() and miss the
  // modal close handler.
  const lclass = modalFrame.locator('input[name="lclass"]');
  await lclass.fill('lazy-dave');
  await lclass.press('Escape');

  await expect(page.locator('.t3js-modal-iframe')).not.toBeAttached();
});
