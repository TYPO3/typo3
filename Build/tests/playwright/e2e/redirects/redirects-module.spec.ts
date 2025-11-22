import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('site_redirects');
});

test('See redirect management module', async ({ backend }) => {
  await expect(backend.contentFrame.locator('h1')).toContainText('Redirect Management');
});

test('Create a new redirect', async ({ page, backend }) => {
  const amountOfRedirects = await backend.contentFrame.locator('table > tbody > tr').count();

  const formEngineReady = backend.formEngine.formEngineLoaded();
  await backend.contentFrame.getByRole('button', { name: 'Add redirect' }).click();
  await formEngineReady;
  await expect(backend.contentFrame.locator('h1')).toContainText('Create new Redirect on root level');

  await backend.contentFrame.getByLabel('Source Path [source_path]').pressSequentially('/my-path/');
  await backend.contentFrame.getByLabel('Target [target]').pressSequentially('t3://page?uid=1');
  await page.keyboard.press('Tab');
  await backend.formEngine.save();
  await backend.formEngine.close();
  await expect(backend.contentFrame.locator('h1')).toContainText('Redirect Management');
  const newAmountOfRedirects = await backend.contentFrame.locator('table > tbody > tr').count();
  expect(newAmountOfRedirects).toBe(amountOfRedirects + 1);
});

test('Can edit a redirect by clicking on source path', async ({ backend }) => {
  const sourceHost = await backend.contentFrame.locator('td:nth-child(3)').first().innerText();
  const sourcePath = await backend.contentFrame.locator('.col-path > a').first().innerText();
  await backend.contentFrame.locator('.col-path > a').first().click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Edit Redirect "' + sourceHost + ', ' + sourcePath + '" on root level');
  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Redirect Management');
});


test('Can edit a redirect by clicking the edit button', async ({ backend }) => {
  const sourceHost = await backend.contentFrame.locator('td:nth-child(3)').first().innerText();
  const sourcePath = await backend.contentFrame.locator('.col-path > a').first().innerText();
  await backend.contentFrame.locator('.col-control > div > a').first().click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Edit Redirect "' + sourceHost + ', ' + sourcePath + '" on root level');
  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Redirect Management');
});

test('See all possible status codes when creating new redirect', async ({ backend }) => {
  await backend.contentFrame.getByRole('button', { name: 'Add redirect' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Create new Redirect on root level');

  const statusCodes = [301, 302, 303, 307, 308];
  for (const statusCode of statusCodes) {
    await test.step('Status code ' + statusCode + ' is available', async () => {
      await expect(backend.contentFrame.locator('//select[contains(@name, "data[sys_redirect]") and contains(@name, "[target_statuscode]")]//option[@value="' + statusCode + '"]')).toBeDefined();
    });
  }
});
