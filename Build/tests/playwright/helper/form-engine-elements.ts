import { BackendPage } from '../fixtures/backend-page';

export type StyleguideTcaEditorOptions = {
  // page tree label, e.g. 'elements basic'
  pageName: string;
  // recordlist id suffix, e.g. 'tx_styleguide_elements_basic'
  listId: string;
  // optional tab to click after the form loaded
  tab?: string;
};

/**
 * Open a styleguide TCA-demo record editor: navigate Records module,
 * select the page in the page tree, click the first edit-record link in
 * the given recordlist, and optionally switch to a tab.
 */
export async function openStyleguideTcaEditor(backend: BackendPage, options: StyleguideTcaEditorOptions): Promise<void> {
  await backend.gotoModule('records');
  await backend.pageTree.open('styleguide TCA demo', options.pageName);

  const formEngineReady = await backend.formEngine.formEngineLoaded();
  await backend.contentFrame
    .locator(`#recordlist-${options.listId} a[aria-label="Edit record"]`)
    .first()
    .click();
  await formEngineReady();

  if (options.tab !== undefined) {
    // `exact: true` so tab name `input` does not also match `inputDateTime`.
    await backend.contentFrame.getByRole('tab', { name: options.tab, exact: true }).click();
  }
}

/**
 * Convenience wrapper for the elements_basic record. `tab` is optional;
 * pass it when a spec exercises a single tab, omit it when each test
 * picks its own tab.
 */
export async function openElementsBasicEditor(backend: BackendPage, tab?: string): Promise<void> {
  await openStyleguideTcaEditor(backend, {
    pageName: 'elements basic',
    listId: 'tx_styleguide_elements_basic',
    tab,
  });
}
