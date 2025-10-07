import { test, expect } from '@playwright/test';
import AxeBuilder from "@axe-core/playwright";
import config from '../config';

test.describe('modules', () => {
  const dataprovider = {
    'mod_page': {
      'label': 'the page module',
      'route': 'module/web/layout?id=204',
    },
    'mod_list': {
      'label': 'the list module',
      'route': 'module/web/list?id=204',
    },
    'mod_workspaces': {
      'label': 'the workspace module',
      'route': 'module/manage/workspaces',
    },
    'mod_info': {
      'label': 'the info module',
      'route': 'module/web/info',
    },
    'mod_site_settings': {
      'label': 'the site settings module',
      'route': 'module/site/settings',
    },
    'mod_reports': {
      'label': 'the reports module',
      'route': 'module/system/reports?action=detail&report=status',
    },
    'mod_indexed_search': {
      'label': 'the index engine statistics module',
      'route': 'module/manage/search-index?id=1',
    },
    'mod_recycler': {
      'label': 'the recycler module',
      'route': 'module/web/recycler',
    },
  }
  const defaultDisableRules = ['color-contrast'];
  for (let [key, data] of Object.entries(dataprovider)) {
    test(key + ':' + data.label, async ({ page }) => {
      const url = `${data.route}`;
      await page.goto(url);
      await expect(page).toHaveURL(url);
      await page.waitForLoadState('networkidle');
      const iframeElement = await page.$('#typo3-contentIframe');
      const frame = await iframeElement.contentFrame();
      const disableRules = data.disableRules ? defaultDisableRules.concat(data.disableRules) : defaultDisableRules;
      const accessibilityScanResults = await new AxeBuilder({ page: frame.page() })
        .include('#typo3-contentIframe')
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .disableRules(disableRules)
        .analyze();
      expect(accessibilityScanResults.violations).toEqual([]);
    });
  }
});
