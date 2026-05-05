import { test, expect, Locator } from '../../fixtures/setup-fixtures';
import { BackendPage } from '../../fixtures/backend-page';

// Tests 2 and 3 reuse the tt_content record created in test 1.
// Run serially in declared order.
test.describe.configure({ mode: 'serial' });

const ttContentTitle = 'tt_content with image';
const ttContentTitleFilledMeta = 'tt_content with image with filled metadata';

async function gotoStyleguidePageModule(backend: BackendPage): Promise<void> {
  await backend.gotoModule('web_layout');
  await backend.pageTree.open('styleguide TCA demo');
  await expect(backend.contentFrame.getByRole('heading', { name: 'styleguide TCA demo' })).toBeVisible();
}

async function pickWizardTextpic(page): Promise<void> {
  // typo3-backend-new-record-wizard renders inside an open shadow root.
  // Locator.click() does not reliably trigger the buttons; use evaluate.
  const wizard = page.locator('typo3-backend-new-record-wizard');
  await expect(wizard).toBeVisible();
  await wizard.evaluate((el: any) => el.shadowRoot.querySelector('button[data-identifier="default"]').click());
  await wizard.evaluate((el: any) => el.shadowRoot.querySelector('button[data-identifier="default_textpic"]').click());
}

async function pickFileFromStyleguideFolder(modalContent: Locator, filename: string): Promise<void> {
  await modalContent.locator('.nodes-container .nodes-list [role="treeitem"][title="styleguide"]').click();
  await expect(modalContent.getByText('fileadmin: /styleguide/')).toBeVisible();
  await modalContent.getByText(filename, { exact: true }).first().click();
}

async function fillTtContentHeader(backend: BackendPage, value: string): Promise<void> {
  const headerInput = backend.contentFrame.locator(
    'xpath=//input[contains(@data-formengine-input-name, "data[tt_content]") and contains(@data-formengine-input-name, "[header]")]'
  );
  await headerInput.fill(value);
}

async function openTtContentInContextPanel(backend: BackendPage, page, recordTitle: string): Promise<void> {
  await backend.contentFrame
    .locator(`xpath=//typo3-backend-contextual-record-edit-trigger[contains(., "${recordTitle}")]`)
    .first()
    .click();
  await expect(page.locator('typo3-backend-modal iframe[name="modal_frame"]')).toBeAttached();
  // The modal iframe's content carries the form. backend.modal.getModalContent
  // already returns this for iframe-style modals.
  const modalContent = await backend.modal.getModalContent();
  await expect(modalContent.getByText(recordTitle).first()).toBeVisible();
}

test.beforeEach(async ({ backend }) => {
  await gotoStyleguidePageModule(backend);
});

test('FAL metadata flow: create CE, edit metadata, propagate to reference', async ({ backend, page }) => {
  // Step 1: create new content element via the page-module wizard.
  await backend.contentFrame.locator('typo3-backend-new-content-element-wizard-button').first().click();
  await pickWizardTextpic(page);

  await expect(backend.contentFrame.getByRole('heading', { name: /Create new Text & Images/ })).toBeVisible();
  await fillTtContentHeader(backend, ttContentTitle);

  // Step 2: switch to Images tab, attach bus_lane.jpg.
  await backend.contentFrame.getByRole('tab', { name: 'Images', exact: true }).click();

  const fileBrowser = await backend.modal.open(
    backend.contentFrame.getByRole('button', { name: 'Add image' }).first()
  );
  await pickFileFromStyleguideFolder(fileBrowser, 'bus_lane.jpg');
  await expect(backend.contentFrame.getByText('bus_lane.jpg').first()).toBeVisible();

  await backend.formEngine.save();
  await backend.formEngine.close();

  // Step 3: navigate to Media module, edit bus_lane.jpg metadata.
  const moduleLoaded = await backend.moduleLoaded('media_management');
  await page.locator('a[data-modulemenu-identifier="media_management"]').click();
  await moduleLoaded();

  await backend.fileTree.open('fileadmin', 'styleguide');
  const fileButton = backend.contentFrame.getByRole('button', { name: 'bus_lane.jpg' }).first();
  await expect(fileButton).toBeVisible();
  const editFormReady = await backend.formEngine.formEngineLoaded();
  await fileButton.click();
  await editFormReady();

  await backend.contentFrame
    .locator('xpath=//input[contains(@data-formengine-input-name, "data[sys_file_metadata]") and contains(@data-formengine-input-name, "[title]")]')
    .fill('Test title');
  await backend.contentFrame
    .locator('xpath=//textarea[contains(@data-formengine-input-name, "data[sys_file_metadata]") and contains(@data-formengine-input-name, "[description]")]')
    .fill('Test description');
  await backend.contentFrame
    .locator('xpath=//input[contains(@data-formengine-input-name, "data[sys_file_metadata]") and contains(@data-formengine-input-name, "[alternative]")]')
    .fill('Test alternative');

  await backend.formEngine.save();
  await backend.formEngine.close();

  // Step 4: re-open the tt_content via the page-module context-edit trigger and
  // verify the metadata propagates as placeholders into sys_file_reference.
  await gotoStyleguidePageModule(backend);
  await openTtContentInContextPanel(backend, page, ttContentTitle);

  const modalContent = await backend.modal.getModalContent();
  await modalContent.getByRole('tab', { name: 'Images', exact: true }).click();
  const collapsedPanel = modalContent.locator('.panel-button.collapsed').first();
  if ((await collapsedPanel.count()) > 0) {
    await collapsedPanel.click();
  }
  await expect(modalContent.locator('.t3js-form-field-eval-null-placeholder-checkbox').first()).toBeAttached();

  for (const metaText of ['Test title', 'Test alternative', 'Test description']) {
    await expect(modalContent.locator('.t3js-form-field-eval-null-placeholder-checkbox', { hasText: `(Default: "${metaText}")` })).toHaveCount(1);
  }
});

test('FAL metadata flow: new CE inherits metadata as null-placeholders', async ({ backend, page }) => {
  await backend.contentFrame.locator('typo3-backend-new-content-element-wizard-button').first().click();
  await pickWizardTextpic(page);

  await expect(backend.contentFrame.getByRole('heading', { name: /Create new Text & Images/ })).toBeVisible();
  await fillTtContentHeader(backend, ttContentTitleFilledMeta);

  await backend.contentFrame.getByRole('tab', { name: 'Images', exact: true }).click();
  const fileBrowser = await backend.modal.open(
    backend.contentFrame.getByRole('button', { name: 'Add image' }).first()
  );
  await pickFileFromStyleguideFolder(fileBrowser, 'bus_lane.jpg');

  await expect(backend.contentFrame.getByText('Image Metadata').first()).toBeVisible();

  for (const fieldName of ['title', 'alternative', 'description']) {
    const fieldXpath = fieldName === 'description'
      ? `xpath=//textarea[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[${fieldName}]")]`
      : `xpath=//input[contains(@data-formengine-input-name, "data[sys_file_reference]") and contains(@data-formengine-input-name, "[${fieldName}]")]`;
    await expect(backend.contentFrame.locator(fieldXpath)).toHaveValue('');
  }

  for (const metaText of ['Test title', 'Test alternative', 'Test description']) {
    await expect(
      backend.contentFrame.locator('.t3js-form-field-eval-null-placeholder-checkbox', { hasText: `(Default: "${metaText}")` })
    ).toHaveCount(1);
  }

  for (const fieldName of ['title', 'alternative', 'description']) {
    await expect(
      backend.contentFrame.locator(
        `xpath=//input[contains(@name, "[${fieldName}]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]`
      )
    ).not.toBeChecked();
    await expect(
      backend.contentFrame.locator(
        `xpath=//input[contains(@name, "[${fieldName}]") and @type="hidden" and contains(@name, "control[active][sys_file_reference]")]`
      )
    ).toHaveValue('0');
  }

  // Disabled inputs/textarea show the inherited metadata while the
  // null-placeholder checkboxes are unchecked. Match by value to avoid
  // the order-dependence of `.first()`.
  await expect(backend.contentFrame.locator('input.form-control:disabled[value="Test title"]')).toHaveCount(1);
  await expect(backend.contentFrame.locator('input.form-control:disabled[value="Test alternative"]')).toHaveCount(1);
  await expect(backend.contentFrame.locator('textarea.form-control:disabled', { hasText: 'Test description' })).toHaveCount(1);
});

test('FAL metadata flow: enabling null checkbox focuses the corresponding field', async ({ backend, page }) => {
  await openTtContentInContextPanel(backend, page, ttContentTitle);

  const modalContent = await backend.modal.getModalContent();
  await modalContent.getByRole('tab', { name: 'Images', exact: true }).click();
  const collapsedPanel = modalContent.locator('.panel-button.collapsed').first();
  if ((await collapsedPanel.count()) > 0) {
    await collapsedPanel.click();
  }
  await expect(modalContent.locator('.t3js-form-field-eval-null-placeholder-checkbox').first()).toBeAttached();

  for (const fieldName of ['title', 'alternative', 'description']) {
    const checkbox = modalContent.locator(
      `xpath=//input[contains(@name, "[${fieldName}]") and @type="checkbox" and contains(@name, "control[active][sys_file_reference]")]`
    );
    await checkbox.check();

    const focused = await modalContent.evaluate((_, fieldName) => {
      const referenceUid = (document.querySelector('[data-object-uid]') as HTMLElement | null)?.dataset?.objectUid;
      if (!referenceUid) {
        return false;
      }
      const target = document.querySelector(`[data-formengine-input-name="data[sys_file_reference][${referenceUid}][${fieldName}]"]`);
      return target ? (target as HTMLElement) === document.activeElement : false;
    }, fieldName);

    expect(focused).toBe(true);

    await modalContent.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
  }
});
