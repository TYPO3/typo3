import { test, expect } from '../fixtures/setup-fixtures';

test('Switch between languages in "Open in new window"', async ({
  page,
  backend,
}) => {
  await backend.gotoModule('web_layout');
  await backend.pageTree.isReady();
  await backend.pageTree.open('styleguide TCA demo', 'staticdata');

  const standalonePage =
    await test.step('open standalone edit window', async () => {
      await expect(
        backend.contentFrame.getByRole('button', { name: 'Display mode' })
      ).toContainText('Layout');
      await backend.contentFrame
        .getByRole('button', { name: 'Edit page properties' })
        .click();
      await expect(
        backend.contentFrame.getByRole('heading', { name: 'Edit Page' }),
      ).toBeVisible();

      // Check that the language dropdown shows "English" as the button text
      // The button contains "Language:" (visually hidden) followed by "English"
      await expect(
        backend.contentFrame.getByRole('button', { name: /Language.*English/i })
      ).toBeVisible();
      const futureStandalonePage = page.context().waitForEvent('page');
      await backend.contentFrame
        .getByRole('button', { name: 'Open in new window' })
        .click();
      return await futureStandalonePage;
    });

  try {
    const languageButton = standalonePage.locator('button[data-bs-toggle="dropdown"]').filter({ hasText: /Language/i });
    const expectOriginalEnglishText = expect(
      standalonePage
        .locator('.t3-form-original-language')
        .filter({
          has: standalonePage.locator('[data-identifier="flags-us"]')
        })
        .filter({
          hasText: /staticdata/
        })
        .first(),
      'Original english text must be shown'
    );

    await expect(languageButton).toContainText('English');
    await test.step('first switch to danish', async () => {
      await expect(async () => {
        await languageButton.click();
        await standalonePage.getByRole('link', { name: 'Danish' }).click();
        await expectOriginalEnglishText.toBeAttached({ timeout: 500 });
      }).toPass();
      await expect(languageButton).toContainText('styleguide demo language danish');
    });

    await test.step('make sure saving works on danish', async () => {
      await standalonePage.getByLabel('Page Title').fill('dansk staticdata');

      // Wait for save response instead of notification
      const saveResponse = standalonePage.waitForResponse(response =>
        response.url().includes('/typo3/record/edit') && response.status() === 200
      );
      standalonePage.getByRole('button', { name: 'Save' }).click();
      await saveResponse;

      await expectOriginalEnglishText.toBeAttached();
      await expect(languageButton).toContainText('styleguide demo language danish');
    });

    await test.step('switch to english', async () => {
      await languageButton.click();
      await standalonePage.getByRole('link', { name: 'English' }).click();
      await expectOriginalEnglishText.not.toBeAttached();
      await expect(languageButton).toContainText('English');
    });

    await test.step('switch to danish', async () => {
      await languageButton.click();
      await standalonePage.getByRole('link', { name: 'Danish' }).click();
      await expectOriginalEnglishText.toBeAttached();
      await expect(languageButton).toContainText('styleguide demo language danish');
      await expect(standalonePage.getByLabel('Page Title')).toHaveValue('dansk staticdata');
    });
  } finally {
    try {
      await standalonePage.close();
    } catch { }
  }
});
