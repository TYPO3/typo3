import { test as base, Locator } from '@playwright/test';
import { BackendPage } from './backend-page';
import { PageTree } from './page-tree';
import {Modal} from "./modal";

// Declare the types of your fixtures.
type BackendFixtures = {
  backend: BackendPage;
  pageTree: PageTree;
  modal: Modal;
};

// Extend base page by providing "backend" fixture.
export const test = base.extend<BackendFixtures>({
  backend: async ({ page }, use) => {
    await use(new BackendPage(page));
  },
  modal: async ({ page }, use) => {
    await use(new BackendPage(page));
  }
});

export { Locator, expect } from '@playwright/test';
