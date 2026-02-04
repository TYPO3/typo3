import { test as base } from '@playwright/test';
import { BackendPage } from './backend-page';
import { PageTree } from './page-tree';
import { Modal } from './modal';
import { Workspace } from './workspace';

// Declare the types of your fixtures.
type BackendFixtures = {
  backend: BackendPage;
  pageTree: PageTree;
  modal: Modal;
  workspace: Workspace;
};

// Extend base page by providing "backend" fixture.
export const test = base.extend<BackendFixtures>({
  backend: async ({ page }, use) => {
    await use(new BackendPage(page));
  },
  modal: async ({ page }, use) => {
    await use(new Modal(page));
  },
  workspace: async ({ page }, use) => {
    await use(new Workspace(page));
  }
});

export { Locator, expect } from '@playwright/test';
