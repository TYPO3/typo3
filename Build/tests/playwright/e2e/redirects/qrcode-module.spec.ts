import { test, expect } from '../../fixtures/setup-fixtures';

test.beforeEach(async ({ backend }) => {
  await backend.gotoModule('link_management');
  const moduleLoaded = backend.moduleLoaded('qrcodes');
  backend.docHeader.selectInDropDown('Module Overview', 'QR Codes');
  await moduleLoaded;
});

test('See QR Code management module', async ({ backend }) => {
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');
});

test('Create a new QR Code', async ({ page, backend }) => {
  const amountOfRedirects = await backend.contentFrame.locator('table > tbody > tr').count();
  await backend.contentFrame.getByRole('button', { name: 'Add QR Code' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Create new QR Code on root level');

  await backend.formEngine.container.getByLabel('[source_host]').pressSequentially('localhost');
  await backend.formEngine.container.getByLabel('[target]').pressSequentially('t3://page?uid=1');
  await backend.contentFrame.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByLabel('Record saved')).toBeVisible();

  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');

  const newAmountOfRedirects = await backend.contentFrame.locator('table > tbody > tr').count();
  expect(newAmountOfRedirects).toBe(amountOfRedirects + 1);
});

test('Can edit a redirect by clicking the edit button', async ({ backend }) => {
  const sourceHost = await backend.contentFrame.locator('td:nth-child(3)').first().innerText();
  await backend.contentFrame.locator('[title="Edit record"]').first().click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Edit QR Code "' + sourceHost + ', ');
  await backend.contentFrame.getByRole('button', { name: 'Close' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('QR Code Management');
});

test('See and download QR Code', async ({ page, backend }) => {
  await backend.contentFrame.getByRole('button', { name: 'Add QR Code' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Create new QR Code on root level');

  await backend.formEngine.container.getByLabel('[source_host]').pressSequentially('example.org');
  await backend.formEngine.container.getByLabel('[target]').pressSequentially('t3://page?uid=1');

  await backend.contentFrame.getByRole('button', { name: 'Save' }).click();
  await expect(backend.contentFrame.locator('h1')).toContainText('Edit QR Code "example.org, ');
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
