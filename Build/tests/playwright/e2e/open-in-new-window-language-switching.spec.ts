import { test, expect } from '../fixtures/setup-fixtures';

test('Switch between languages in "Open in new window"', async ({
  page,
  backend,
}) => {
  await backend.gotoModule('web_layout');
  await backend.pageTree.open('styleguide TCA demo');

  const standalonePage =
    await test.step('open standalone edit window', async () => {
      await expect(
        backend.contentFrame.getByLabel('Display mode').getByText('Layout'),
      ).toHaveAttribute('selected');
      await backend.contentFrame
        .getByRole('button', { name: 'Edit page properties' })
        .click();
      await expect(
        backend.contentFrame.getByRole('heading', { name: 'Edit Page' }),
      ).toBeVisible();

      await expect(
        backend.contentFrame.getByLabel('Record language').getByText('English'),
      ).toHaveAttribute('selected');
      const futureStandalonePage = page.context().waitForEvent('page');
      await backend.contentFrame
        .getByRole('button', { name: 'Open in new window' })
        .click();
      return await futureStandalonePage;
    });

  try {
    const recordLanguageSelect = standalonePage.getByLabel('Record language');
    const expectOriginalEnglishText = expect(
      standalonePage
        .locator('.t3-form-original-language')
        .filter({
          has: standalonePage.locator('[data-identifier="flags-us"]'),
          hasText: 'styleguide TCA demo'
        }),
      'Original english text must be shown'
    );

    await expect(recordLanguageSelect.getByText('English')).toHaveAttribute('selected');
    await test.step('first switch to danish', async () => {
      const isDanishNew =
        (await recordLanguageSelect.getByText('styleguide demo language danish').textContent())!
          .endsWith('[NEW]');
      await expect(async () => {
        await recordLanguageSelect.selectOption({
          label: `styleguide demo language danish${isDanishNew ? ' [NEW]' : ''}`
        });
        await expectOriginalEnglishText.toBeVisible({ timeout: 500 });
      }).toPass();
      await expect(recordLanguageSelect.getByText('English')).not.toHaveAttribute('selected');
      await expect(recordLanguageSelect.getByText('danish')).toHaveAttribute('selected');
    });

    await test.step('make sure saving works on danish', async () => {
      await standalonePage.getByLabel('Page Title').fill('dansk stilguide TCA demo');
      await standalonePage.getByRole('button', { name: 'Save' }).click();
      await expect(standalonePage.getByLabel('Record saved')).toBeVisible();
      await expectOriginalEnglishText.toBeVisible();
      await expect(recordLanguageSelect.getByText('English')).not.toHaveAttribute('selected');
      await expect(recordLanguageSelect.getByText('danish')).toHaveAttribute('selected');
    });

    await test.step('switch to english', async () => {
      await recordLanguageSelect.selectOption({ label: 'English' });
      await expectOriginalEnglishText.not.toBeVisible();
      await expect(recordLanguageSelect.getByText('English')).toHaveAttribute('selected');
      await expect(recordLanguageSelect.getByText('danish')).not.toHaveAttribute('selected');
    });

    await test.step('switch to danish', async () => {
      await recordLanguageSelect.selectOption({ label: 'styleguide demo language danish' });
      await expectOriginalEnglishText.toBeVisible();
      await expect(recordLanguageSelect.getByText('English')).not.toHaveAttribute('selected');
      await expect(recordLanguageSelect.getByText('danish')).toHaveAttribute('selected');
      await expect(standalonePage.getByLabel('Page Title')).toHaveValue('dansk stilguide TCA demo');
    });
  } finally {
    try {
      await standalonePage.close();
    } catch { }
  }
});
