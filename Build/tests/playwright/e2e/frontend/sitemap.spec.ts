import { test, expect } from '../../fixtures/setup-fixtures';

const sitemapIndexUrl = '/styleguide-demo-242/sitemap.xml';

const slugs = [
  '/bullets', '/div', '/header', '/text', '/textpic', '/textmedia',
  '/image', '/html', '/table', '/felogin-login', '/form-formframework',
  '/indexedsearch-pi2', '/shortcut', '/uploads',
  '/menu-categorized-pages', '/menu-categorized-content',
  '/menu-pages', '/menu-subpages', '/menu-sitemap', '/menu-section',
  '/menu-abstract', '/menu-recently-updated', '/menu-related-pages',
  '/menu-section-pages', '/menu-sitemap-pages',
];

test.describe('Frontend XML sitemap', () => {
  test('sitemap index links to a pages sitemap with priority per page', async ({ page }) => {
    await page.goto(sitemapIndexUrl);

    const body = page.locator('body');
    await expect(body).toContainText('TYPO3 XML Sitemap');
    await expect(body).toContainText('sitemap-type/pages');
    await expect(body).toContainText('sitemap.xml');

    // Drill into the pages sitemap from the index.
    await page.locator('a').first().click();

    for (const slug of slugs) {
      await test.step(`page ${slug} has a numeric priority`, async () => {
        const priority = await page.locator(
          `xpath=//a[contains(text(),"${slug}")]/ancestor::td/following-sibling::td[3]`
        ).first().textContent();
        expect(priority?.trim()).toMatch(/^\d+(\.\d+)?$/);
      });
    }
  });
});
