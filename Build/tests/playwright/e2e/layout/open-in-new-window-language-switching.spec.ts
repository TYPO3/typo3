import { test, expect } from '../../fixtures/setup-fixtures';
import { DocHeader } from '../../fixtures/doc-header';

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
  const areLanguagesAvailable = await backend.contentFrame.getByRole('button', { name: 'Display mode' }).isVisible();
  test.skip(areLanguagesAvailable === false, 'No additional languages available, skipping language switch test.');

  const standalonePage =
    await test.step('open standalone edit window', async () => {
      // Click "Edit page properties" which opens the contextual record edit panel
      await backend.contentFrame
        .getByRole('button', { name: 'Edit page properties' })
        .click();

      // Wait for modal iframe and navigate to full edit view.
      const contextPanel = page.frameLocator('iframe[name="modal_frame"]');
      await expect(contextPanel.locator('.contextual-record-edit')).toBeVisible();

      // Clicking the "Open full editing view" link races the iframe's
      // DocumentService.ready() handler that attaches the e.preventDefault()
      // logic. When the click wins the race, the modal iframe just follows
      // the href, the parent's content frame never navigates, and the
      // FormEngine assertion below times out. Read the href and replicate
      // both steps the click handler performs on its happy path:
      // navigate the parent content frame, then dismiss the modal so its
      // backdrop does not intercept later clicks on the form.
      const fullEditUrl = await contextPanel.locator('.t3js-contextual-fullscreen').getAttribute('href');
      const formEngineReady = await backend.formEngine.formEngineLoaded();
      await page.evaluate((url) => {
        const w = window as unknown as {
          TYPO3: { Backend: { ContentContainer: { setUrl: (u: string) => void } } };
        };
        w.TYPO3.Backend.ContentContainer.setUrl(url);
        window.postMessage({ actionName: 'typo3:editform:navigate' }, window.location.origin);
      }, fullEditUrl);
      await formEngineReady();

      await expect(page.locator('typo3-backend-modal')).toHaveCount(0);
      await expect(backend.formEngine.container).toBeVisible();
      await expect(
        backend.contentFrame.getByRole('heading', { name: 'staticdata' }),
      ).toBeVisible();

      const openButton = backend.contentFrame.getByRole('button', { name: 'Open in new window' });
      await expect(openButton).toBeVisible();
      const futureStandalonePage = page.context().waitForEvent('page');
      await openButton.click();
      const standalonePage = await futureStandalonePage;
      await standalonePage.waitForLoadState('domcontentloaded');
      return standalonePage;
    });

  try {
    const languageButton = standalonePage.locator('button.dropdown-toggle').filter({ hasText: /Language:/i });

    // docHeader fixture not available on standalone page by default
    const docHeader = new DocHeader(standalonePage);
    docHeader.setContainerLocator(standalonePage.locator('.t3js-module-docheader-navigation'));

    await expect(languageButton).toContainText('English');

    await test.step('first switch to another language', async () => {
      await docHeader.selectItemInDropDownByIndex(/Language:/i, 1);
      await expect(languageButton).not.toContainText('English');
      await expect(languageButton).toContainText('styleguide demo language');

      await test.step('make sure saving works on another language', async () => {
        // FormEngine re-initializes the input after a language switch
        // and would overwrite a value typed before init completes.
        const titleInput = standalonePage.getByLabel('Page Title');
        await expect(titleInput).toHaveAttribute('data-formengine-input-initialized', 'true');
        await titleInput.fill('changed language staticdata');

        // Wait for save response instead of notification
        const saveResponse = standalonePage.waitForResponse(response =>
          response.url().includes('/typo3/record/edit') && response.status() === 200
        );
        await standalonePage.getByRole('button', { name: 'Save' }).click();
        await saveResponse;

        // Verify the save persisted and the form re-rendered before switching languages
        await expect(titleInput).toHaveAttribute('data-formengine-input-initialized', 'true');
        await expect(titleInput).toHaveValue('changed language staticdata');
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
    } catch { /* empty */ }
  }
});
