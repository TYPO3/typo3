import { test, expect } from '../../fixtures/setup-fixtures';

// Editing the root page record makes AddDisabledDocHeaderButtonListener of the
// playwright_helper fixture extension add a deliberately disabled link button.
const recordEditUrl = 'record/edit?edit[pages][1]=edit';
const disabledButtonTitle = 'Playwright disabled button';

test('doc header button bar is blocked as a whole while FormEngine is loading', async ({ backend, page }) => {
  // Keep FormEngine from initializing, so the state rendered by the server stays.
  await page.route(/\/form-engine\.js(\?|$)/, (route) => route.abort());
  await page.goto(recordEditUrl);

  const buttonBar = backend.contentFrame.locator('.t3js-module-docheader-buttons');
  await expect(buttonBar).toHaveAttribute('inert', '');

  // The block must not be expressed through the single buttons, otherwise
  // releasing it can not tell them apart from buttons disabled on purpose.
  await expect(backend.contentFrame.locator('[name="_savedok"]')).toBeEnabled();
});

test('releasing the doc header keeps buttons disabled on purpose disabled', async ({ backend, page }) => {
  await page.goto(recordEditUrl);

  const buttonBar = backend.contentFrame.locator('.t3js-module-docheader-buttons');
  const disabledButton = backend.contentFrame.getByRole('button', { name: disabledButtonTitle });

  // FormEngine releases the button bar once initialization finished.
  await expect(buttonBar).not.toHaveAttribute('inert');
  await expect(backend.contentFrame.locator('[name="_savedok"]')).toBeEnabled();

  // ... and must not touch the state of a button disabled on purpose.
  await expect(disabledButton).toHaveAttribute('aria-disabled', 'true');
  await expect(disabledButton).toHaveClass(/\bdisabled\b/);
});
