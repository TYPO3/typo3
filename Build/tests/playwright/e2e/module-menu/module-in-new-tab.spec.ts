import { test, expect } from '../../fixtures/setup-fixtures';

const modules = [
  { identifier: 'records', label: 'Records' },
  { identifier: 'media_management', label: 'Media' },
];

for (const { identifier, label } of modules) {
  test(`${label} module opened in a new tab renders its clipboard`, async ({ page, context }) => {
    await page.goto('module/web/layout');
    const href = await page.locator(`a[data-modulemenu-identifier="${identifier}"]`).getAttribute('href');

    const tab = await context.newPage();
    const errors: string[] = [];
    tab.on('pageerror', (error) => errors.push(error.message));
    await tab.goto(new URL(href, page.url()).toString());

    await expect(tab.locator('[data-clipboard-panel]')).toBeVisible();
    expect(errors).toEqual([]);
  });
}
