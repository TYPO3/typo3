import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('link_management');
  const moduleLoaded = await backend.moduleLoaded('qrcodes');
  await backend.docHeader.selectInDropDown('Module Overview', 'QR Codes');
  await moduleLoaded();
});

test('See QR Code management module', async ({ backend }) => {
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');
});

test('Create a new QR Code', async ({ page, backend }) => {
  const amountOfRedirects = await backend.contentFrame.locator('table > tbody > tr').count();
  const formEngineReady = await backend.formEngine.formEngineLoaded();
  await backend.contentFrame.getByRole('button', { name: 'Add QR Code' }).click();
  await formEngineReady();
  await expect(backend.contentFrame.locator('h1')).toContainText('Create new QR Code');

  await backend.formEngine.container.getByLabel('[source_host]').fill('localhost');
  await backend.formEngine.container.getByLabel('[target]').fill('t3://page?uid=1');
  await backend.contentFrame.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByLabel('Record saved')).toBeVisible();

  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');

  const newAmountOfRedirects = await backend.contentFrame.locator('table > tbody > tr').count();
  expect(newAmountOfRedirects).toBe(amountOfRedirects + 1);
});

test('Can edit a redirect by clicking the edit button', async ({ backend }) => {
  await backend.contentFrame.locator('[title="Edit record"]').first().click();
  await expect(backend.contentFrame.locator('h1')).toContainText('/_redirect/');
  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');
});

test('See and download QR Code', async ({ page, backend }) => {
  const formEngineReady = await backend.formEngine.formEngineLoaded();
  await backend.contentFrame.getByRole('button', { name: 'Add QR Code' }).click();
  await formEngineReady();
  await expect(backend.contentFrame.locator('h1')).toContainText('Create new QR Code');

  await backend.formEngine.container.getByLabel('[source_host]').fill('example.org');
  await backend.formEngine.container.getByLabel('[target]').fill('t3://page?uid=1');

  await backend.contentFrame.getByRole('button', { name: 'Save' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('example.org, ');
  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');

  await backend.contentFrame.locator('typo3-qrcode-modal-button').first().click();

  const modalBody = page.locator('.t3js-modal-body');
  await expect(page.locator('.t3js-modal-title')).toContainText('QR Code');
  await expect(modalBody.locator('svg')).toBeVisible();

  // Download QR Code
  const responsePromise = page.waitForResponse((resp) => {
    return resp.url().includes('/typo3/ajax/qrcode/download');
  });
  await modalBody.locator('[name="qrcode-download"] button[type="submit"]').click();
  const response = await responsePromise;

  expect(response.status()).toBe(200);
  expect(await response.headerValue('content-type')).toContain('image/svg+xml');
});
