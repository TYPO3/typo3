import { test, expect } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

test.beforeEach(async ({ page, backend }) => {
  await page.goto('module/web/layout');
  await backend.gotoModule('site_configuration');
});

test('Shows site configuration module', async ({ backend }) => {
  await expect(backend.contentFrame.locator('h1')).toContainText('Setup');
  // we would not expect to have more than one missing site configuration (zero if other tests did not create one)
  expect(await backend.contentFrame.locator('//table//tr//a[contains(text(),"Add new site configuration for this site")]').count()).toBeLessThan(2);
});

test('Edit existing site configuration', async ({ backend, page }) => {
  await backend.contentFrame.getByTitle('Edit site configuration').first().click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Edit Site Configuration');

  await test.step('Check languages tab', async () => {
    await backend.contentFrame.getByRole('tab', { name: 'Languages' }).click();

    await test.step('Update default language fields', async () => {
      const defaultLanguageElement = backend.contentFrame.locator('typo3-formengine-container-sitelanguage > div > div.panel-group > div').first();
      await expect(defaultLanguageElement).toContainText(/English\s.*\[0]\s\(en_US\.UTF-8\)/);

      await defaultLanguageElement.locator('.form-irre-header').click();
      await defaultLanguageElement.getByText('[title]').fill('English Edit');
      await defaultLanguageElement.getByText('[base]').fill('/');
      await defaultLanguageElement.getByText('[locale]').fill('en_US.UTF-8');

      await page.keyboard.press('Tab');
      await backend.formEngine.save();

      await expect(defaultLanguageElement).toContainText('English Edit [0] (en_US.UTF-8)');
    });

    await test.step('Delete non-default language', async (step) => {
      const languageCount = await getLanguageCount(backend);
      step.skip(languageCount === 1, 'No additional language to delete');

      const nonDefaultLanguageElement = backend.contentFrame.locator('typo3-formengine-container-sitelanguage > div > div.panel-group > div').nth(1);
      await expect(nonDefaultLanguageElement).toBeVisible();
      const deleteButton = nonDefaultLanguageElement.getByRole('button', { name: 'Delete' });
      await backend.modal.open(deleteButton);

      const modal = page.locator('typo3-backend-modal > dialog');
      await expect(modal).toBeVisible();
      await expect(modal).toContainText('Delete this record?');

      await backend.modal.click({ name: 'yes' });
      await backend.formEngine.save();

      const newLanguageCount = await getLanguageCount(backend);
      expect(newLanguageCount).toBe(languageCount - 1);
    });

    await test.step('Add a new language', async () => {
      const languageCount = await getLanguageCount(backend);

      await backend.contentFrame.getByRole('button', { name: 'Create new language' }).click();
      const lastLanguageElement = backend.contentFrame.locator('typo3-formengine-container-sitelanguage > div > div.panel-group > div').last();
      await expect(lastLanguageElement).toContainText('New language');
      await lastLanguageElement.getByText('[title]').fill('New Language');
      await lastLanguageElement.getByText('[base]').fill('/new-language/');
      await lastLanguageElement.getByText('[locale]').fill('hr_HR');
      await page.keyboard.press('Tab');
      await backend.formEngine.save();

      await expect(lastLanguageElement).toContainText(/New Language.*\(hr_HR\)/);

      const newLanguageCount = await getLanguageCount(backend);
      expect(newLanguageCount).toBe(languageCount + 1);
    });
  });

  await test.step('Close site configuration', async () => {
    await backend.formEngine.close();
    await expect(backend.contentFrame.locator('h1')).toContainText('Setup');
  });
});

async function getLanguageCount(backend: BackendPage): Promise<number> {
  return backend.contentFrame.locator('typo3-formengine-container-sitelanguage > div > div.panel-group > div').count();
}
