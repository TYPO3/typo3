import { test, expect } from '../../fixtures/setup-fixtures';
import {DocHeader} from "../../fixtures/doc-header";

test('Switch between languages in "Open in new window"', async ({
  page,
  backend,
}) => {
  await backend.gotoModule('web_layout');
  await backend.pageTree.isReady();
  await backend.pageTree.open('styleguide TCA demo', 'staticdata');

  // if display mode isn't visible, the language comparison mode is not available (no languages besides the default
  // language is available).
  // Skip test only when running tests multiple times
  let areLanguagesAvailable = await backend.contentFrame.getByRole('button', { name: 'Display mode' }).isVisible()
  test.skip(areLanguagesAvailable === false, 'No additional languages available, skipping language switch test.');

  const standalonePage =
    await test.step('open standalone edit window', async () => {
      await backend.contentFrame
        .getByRole('button', { name: 'Edit page properties' })
        .click();
      await expect(
        backend.contentFrame.getByRole('heading', { name: 'Edit Page' }),
      ).toBeVisible();

      const futureStandalonePage = page.context().waitForEvent('page');
      await backend.contentFrame
        .getByRole('button', { name: 'Open in new window' })
        .click();
      return await futureStandalonePage;
    });

  try {
    const languageButton = standalonePage.locator('button[data-bs-toggle="dropdown"]').filter({ hasText: /Language:/i });

    // docHeader fixture not available on standalone page by default
    let docHeader = new DocHeader(standalonePage);
    docHeader.setContainerLocator(standalonePage.locator('.t3js-module-docheader-navigation'))

    await expect(languageButton).toContainText('English');

    await test.step('first switch to another language', async () => {
      await docHeader.selectItemInDropDownByIndex(/Language:/i, 1);
      await expect(languageButton).not.toContainText('English');
      await expect(languageButton).toContainText('styleguide demo language');

      await test.step('make sure saving works on another language', async () => {
        await standalonePage.getByLabel('Page Title').fill('changed language staticdata');

        // Wait for save response instead of notification
        const saveResponse = standalonePage.waitForResponse(response =>
          response.url().includes('/typo3/record/edit') && response.status() === 200
        );
        await standalonePage.getByRole('button', {name: 'Save'}).click();
        await saveResponse;

        await expect(languageButton).toContainText('styleguide demo language');
      });
    });

    await test.step('switch to english', async () => {
      await docHeader.selectItemInDropDownByIndex(/Language:/i, 0);
      await expect(languageButton).toContainText('English');
    });

    await test.step('switch back to another language', async () => {
      await docHeader.selectItemInDropDownByIndex(/Language:/i, 1);
      await expect(languageButton).not.toContainText('English');
      await expect(languageButton).toContainText('styleguide demo language');
      await expect(standalonePage.getByLabel('Page Title')).toHaveValue('changed language staticdata');
    });
  } finally {
    try {
      await standalonePage.close();
    } catch { }
  }
});
