import { test, expect } from '../../fixtures/setup-fixtures';
import { FrameLocator } from '@playwright/test';

test.beforeEach(async ({ page, backend }) => {
  await page.goto('module/web/layout');
  await backend.gotoModule('system_reports');
});

test.describe('Status report', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.contentFrame.locator('.card-footer').getByRole('link', { name: 'Open Status Report module' }).click();
  });

  test('Shows status report', async ({ backend }) => {
    await expect(backend.contentFrame.locator('h1')).toContainText('Status Report');
    await expect(backend.contentFrame.getByRole('heading', { name: 'TYPO3 System' })).toBeVisible();
  });
});

test.describe('Record statistics', () => {
  test.beforeEach(async ({ backend }) => {
    await backend.contentFrame.locator('.card-footer').getByRole('link', { name: 'Open Record Statistics module' }).click();
  });

  test('Shows record statistics', async ({ backend }) => {
    await expect(backend.contentFrame.locator('h1')).toContainText('Record Statistics');
    const entries = [
      { name: 'Total number of default language pages', count: 84 },
      { name: 'Total number of translated pages', count: 132 },
      { name: 'Hidden pages', count: 1 },
      { name: 'Marked-deleted pages', count: 0 },
      { name: 'Standard', count: 1 },
      { name: 'Backend User Section', count: 0 },
      { name: 'Link', count: 0 },
    ];
    for (const entry of entries) {
      await checkCountOfRecordStatisticEntry(backend.contentFrame, entry.name, entry.count);
    }
  });
});

test.describe('See reports sub modules', () => {
  const modules = ['Status Report', 'Record Statistics'];
  modules.forEach((moduleName) => {
    test('See ' + moduleName + ' module', async ({ backend }) => {
      await expect(backend.contentFrame.locator('.card-title').getByText(moduleName)).toBeVisible();
      await expect(backend.contentFrame.locator('.card-footer').getByRole('link', { name: 'Open ' + moduleName + ' module' })).toBeVisible();
      await backend.contentFrame.locator('.card-footer').getByRole('link', { name: 'Open ' + moduleName + ' module' }).click();
      await expect(backend.contentFrame.locator('h1')).toContainText(moduleName);
    });
  });
});

async function checkCountOfRecordStatisticEntry(contentFrame: FrameLocator, entryName: string, expectedCount: number) {
  const value: number = parseInt(await contentFrame.locator('//td[contains(text(),"' + entryName + '")]/following-sibling::td[1]').innerText(), 10);
  expect(value).toBeGreaterThanOrEqual(expectedCount);
}
