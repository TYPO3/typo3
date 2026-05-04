import { test, expect } from '../../fixtures/setup-fixtures';

const baconText = 'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.';

const contentElements: { slug: string; seeElement: string[]; see: string[] }[] = [
  { slug: '/bullets', seeElement: ['.ce-bullets'], see: ['Another bullet list', 'A bullet list'] },
  { slug: '/div', seeElement: ['hr.ce-div'], see: [] },
  { slug: '/header', seeElement: ['.frame-type-header'], see: [baconText] },
  { slug: '/text', seeElement: ['.frame-type-text', '.content.col a'], see: [baconText] },
  { slug: '/textpic', seeElement: ['.frame-type-textpic', '.content.col a', '.ce-gallery img'], see: [baconText] },
  { slug: '/textmedia', seeElement: ['.frame-type-textmedia', '.content.col a', '.ce-gallery img'], see: [baconText] },
  { slug: '/image', seeElement: ['.frame-type-image', '.ce-gallery img'], see: [baconText] },
  { slug: '/html', seeElement: ['.frame-type-html', '.content.col a', '.content.col strong'], see: [baconText] },
  { slug: '/table', seeElement: ['.frame-type-table', 'table.table'], see: ['row4 col4'] },
  { slug: '/shortcut', seeElement: ['.frame-type-shortcut', '.content.col a'], see: [baconText] },
  { slug: '/uploads', seeElement: ['.frame-type-uploads', '.ce-uploads'], see: ['bus_lane.jpg', 'telephone_box.jpg', 'underground.jpg'] },
  { slug: '/menu-categorized-pages', seeElement: ['.frame-type-menu_categorized_pages ul li'], see: ['Menu categorized pages'] },
  { slug: '/menu-categorized-content', seeElement: ['.frame-type-menu_categorized_content ul li'], see: ['Menu categorized content'] },
  { slug: '/menu-pages', seeElement: ['.frame-type-menu_pages ul li'], see: ['Menu pages'] },
  { slug: '/menu-subpages', seeElement: ['.frame-type-menu_subpages ul li'], see: ['Menu subpages'] },
  { slug: '/menu-sitemap', seeElement: ['.frame-type-menu_sitemap ul li'], see: ['Menu sitemap'] },
  { slug: '/menu-section', seeElement: ['.frame-type-menu_section ul ul li'], see: ['Menu section'] },
  { slug: '/menu-abstract', seeElement: ['.frame-type-menu_abstract ul li a', '.frame-type-menu_abstract ul li p'], see: ['Menu abstract'] },
  { slug: '/menu-recently-updated', seeElement: ['.frame-type-menu_recently_updated ul li'], see: ['Menu recently updated'] },
  { slug: '/menu-related-pages', seeElement: ['.frame-type-menu_related_pages ul li'], see: ['Menu related pages'] },
  { slug: '/menu-section-pages', seeElement: ['.frame-type-menu_section_pages ul ul'], see: ['Menu section pages'] },
  { slug: '/menu-sitemap-pages', seeElement: ['.frame-type-menu_sitemap_pages ul li'], see: ['Menu sitemap pages'] },
];

test.describe('Frontend content elements', () => {
  test('renders every styleguide content element', async ({ page }) => {
    for (const { slug, seeElement, see } of contentElements) {
      await test.step(`page ${slug}`, async () => {
        await page.goto(`/styleguide-demo-242${slug}`);
        for (const selector of seeElement) {
          await expect(page.locator(selector).first()).toBeVisible();
        }
        const content = page.locator('.content.col');
        for (const text of see) {
          await expect(content).toContainText(text);
        }
      });
    }
  });
});
