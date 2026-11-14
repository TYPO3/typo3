import AxeBuilder from '@axe-core/playwright';
import { test, expect } from '../../fixtures/setup-fixtures';

// The form definition with uid 1 is provided by BackendEnvironment.csv
const formEditorUrl = 'module/form/editor?formPersistenceIdentifier=1';

test.describe('form editor stage', () => {
  test.beforeEach(async ({ page, backend }) => {
    await page.goto(formEditorUrl);
    await expect(
      backend.contentFrame.locator('[data-identifier="stageArea"] typo3-form-form-element-stage-item').first()
    ).toBeVisible();
  });

  test('stage elements pass the accessibility scan', async ({ page }) => {
    const accessibilityScanResults = await new AxeBuilder({ page })
      .include(['#typo3-contentIframe', '[data-identifier="stageArea"]'])
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .disableRules(['color-contrast'])
      .analyze();
    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test('stage elements are labelled groups', async ({ backend }) => {
    const element = backend.contentFrame.locator('[data-element-identifier-path="stageAccessibility/page-1/text-1"]');
    await expect(element).toHaveAttribute('role', 'group');
    await expect(element).toHaveAccessibleName('Name (Text)');
  });

  test('edit button selects the element', async ({ backend }) => {
    const element = backend.contentFrame.locator('[data-element-identifier-path="stageAccessibility/page-1/fieldset-1/text-2"]');
    await expect(element).not.toHaveClass(/\bselected\b/);

    await element.getByRole('button', { name: 'Edit' }).click();

    await expect(element).toHaveClass(/\bselected\b/);
  });

  test('edit button is operable by keyboard', async ({ backend }) => {
    const element = backend.contentFrame.locator('[data-element-identifier-path="stageAccessibility/page-1/text-1"]');
    await expect(element).not.toHaveClass(/\bselected\b/);

    await element.getByRole('button', { name: 'Edit' }).press('Enter');

    await expect(element).toHaveClass(/\bselected\b/);
  });

  test('other toolbar buttons keep their own keyboard action', async ({ page, backend }) => {
    const element = backend.contentFrame.locator('[data-element-identifier-path="stageAccessibility/page-1/text-1"]');

    await element.getByRole('link', { name: 'Remove' }).press('Enter');

    const dialog = page.locator('typo3-backend-modal > dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog.locator('.t3js-modal-title')).toContainText('Remove element?');
  });
});
