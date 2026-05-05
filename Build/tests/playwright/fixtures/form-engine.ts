import { Page, expect, Locator, FrameLocator } from '@playwright/test';

export type ElementsBasicInputTestData = {
  label: string;
  inputValue: string;
  expectedValue: string;
  expectedInternalValue: string;
  expectedValueAfterSave: string;
  expectedInternalValueAfterSave?: string;
};

export type ElementsBasicInputValidationStep = {
  inputValue: string;
  expectedValue: string;
  expectedInternalValue: string;
  expectError: boolean;
};

export type ElementsBasicInputValidationData = {
  label: string;
  testSequence: ElementsBasicInputValidationStep[];
};

export type ElementsBasicRadioTestData = {
  label: string;
  inputValue: string;
  // `false` means "this radio option does not exist" (no input element with that value).
  expectedValue: true | false;
};

export class FormEngine {
  readonly contentFrame: FrameLocator;
  readonly container: Locator;
  readonly saveButton: Locator;
  readonly closeButton: Locator;
  private readonly page: Page;

  constructor(page: Page) {
    this.page = page;
    this.contentFrame = this.page.frameLocator('#typo3-contentIframe');
    this.container = this.contentFrame.locator('#EditDocumentController');
    this.saveButton = this.contentFrame.locator('[name="_savedok"]');
    this.closeButton = this.contentFrame.locator('.t3js-editform-close');
  }

  /**
   * Click the save button and wait for form engine to be ready
   */
  async save() {
    await expect(this.saveButton).toBeEnabled();
    // TYPO3 toggles a `.disabled` Bootstrap class on the save button to mark
    // a clean form. The class applies `pointer-events: none`, so a click on
    // the button is intercepted by the surrounding `.btn-toolbar` and never
    // lands. Playwright's `toBeEnabled()` only checks the `disabled` attribute
    // and misses this state, so wait for the class to be cleared explicitly.
    await expect(this.saveButton).not.toHaveClass(/\bdisabled\b/);

    const loaded = await this.formEngineLoaded();
    this.saveButton.click();
    await loaded();

    // Wait for TYPO3's "form is dirty" markers to clear. Otherwise a
    // subsequent close() can race the typo3-module-loaded event and trip
    // the unsaved-changes modal that FormEngine.preventExitIfNotSaved
    // raises while .has-change or .is-new is still present.
    await expect(this.contentFrame.locator('.has-change, .is-new')).toHaveCount(0, { timeout: 10000 });
  }

  /**
   * Close the form engine
   */
  async close() {
    const loaded = await this.formEngineLoaded();
    this.closeButton.click();
    await loaded();

    await expect(this.container).not.toBeAttached();
  }

  /**
   * Returns a waiter for the next `typo3-module-loaded` event. Awaiting
   * the call installs the listener; the returned waiter resolves when
   * the event fires. Both awaits are needed so the listener is in
   * place before the triggering action runs:
   *
   *   const ready = await formEngine.formEngineLoaded();
   *   await action();
   *   await ready();
   */
  async formEngineLoaded(): Promise<() => Promise<void>> {
    const initial = await this.page.evaluate(() => {
      const w = window as Window & { __typo3ModuleLoadedCounter?: number };
      if (typeof w.__typo3ModuleLoadedCounter !== 'number') {
        w.__typo3ModuleLoadedCounter = 0;
        document.addEventListener('typo3-module-loaded', () => {
          w.__typo3ModuleLoadedCounter = (w.__typo3ModuleLoadedCounter ?? 0) + 1;
        });
      }
      return w.__typo3ModuleLoadedCounter;
    });
    return async () => {
      await this.page.waitForFunction(
        (initial) => {
          const w = window as Window & { __typo3ModuleLoadedCounter?: number };
          return (w.__typo3ModuleLoadedCounter ?? 0) > initial;
        },
        initial,
      );
    };
  }

  /**
   * Fill the styleguide elements_basic field identified by its TCA label,
   * assert visible and hidden value before save, save, and re-assert after
   * the form re-renders.
   *
   * Pass `{ datepicker: true }` for inputdatetime fields, which carry
   * `data-formengine-datepicker-real-input-name` instead of the regular
   * `data-formengine-input-name` attribute (flatpickr legacy).
   */
  async runInputFieldTest(data: ElementsBasicInputTestData, options: { datepicker?: boolean } = {}): Promise<void> {
    const datepicker = options.datepicker === true;

    await this.waitForElementsBasicFieldInitialized(data.label, datepicker);
    const { visible, hidden } = await this.resolveElementsBasicFields(data.label, datepicker);

    await this.fillElementsBasicField(visible, data.inputValue);

    await expect(visible).toHaveValue(data.expectedValue);
    await expect(hidden).toHaveValue(data.expectedInternalValue);

    await this.save();

    await this.waitForElementsBasicFieldInitialized(data.label, datepicker);
    const expectedVisibleAfterSave = data.expectedInternalValueAfterSave ?? data.expectedValue;
    await expect(visible).toHaveValue(expectedVisibleAfterSave);
    await expect(hidden).toHaveValue(data.expectedValueAfterSave);
  }

  /**
   * Drive a field through a sequence of inputs without saving and check
   * visible value, hidden value, and the has-error class at each step.
   */
  async runInputFieldValidationTest(data: ElementsBasicInputValidationData): Promise<void> {
    await this.waitForElementsBasicFieldInitialized(data.label, false);
    const { visible, hidden } = await this.resolveElementsBasicFields(data.label, false);

    for (const step of data.testSequence) {
      await this.fillElementsBasicField(visible, step.inputValue);

      await expect(visible).toHaveValue(step.expectedValue);
      await expect(hidden).toHaveValue(step.expectedInternalValue);

      if (step.expectError) {
        await expect(visible).toHaveClass(/has-error/);
      } else {
        await expect(visible).not.toHaveClass(/has-error/);
      }
    }
  }

  /**
   * Radio groups are wrapped in `<fieldset>` with
   * `<legend><code>[label]</code></legend>`, not `<label><code>` like
   * input fields. Each option is a separate `<input type="radio" value="X">`.
   *
   * Pass `expectedValue: false` to assert the radio option does not
   * exist (unknown label or invalid value).
   */
  async runRadioFieldTest(data: ElementsBasicRadioTestData): Promise<void> {
    const groupBase = `xpath=(//legend/code[contains(text(),"[${data.label}]")]/..)`;
    const radioXpath = `${groupBase}[1]/parent::*//*/input[@value="${data.inputValue}"]`;
    const radio = this.contentFrame.locator(radioXpath).first();

    if (data.expectedValue === false) {
      await expect(this.contentFrame.locator(radioXpath)).toHaveCount(0);
      return;
    }

    await expect(this.contentFrame.locator(`${groupBase}[1]`).first()).toBeAttached({ timeout: 30000 });

    // Radio buttons may be visually hidden (Bootstrap 5 styles the label).
    // Use force:true to mirror Selenium's tolerant click semantics.
    await radio.click({ force: true });
    await this.page.keyboard.press('Tab');
    await this.page.keyboard.press('Escape');
    await expect(this.contentFrame.locator('#t3js-ui-block')).not.toBeVisible();

    await expect(radio).toBeChecked();

    await this.save();

    await expect(this.contentFrame.locator(`${groupBase}[1]`).first()).toBeAttached({ timeout: 30000 });
    await expect(this.contentFrame.locator(radioXpath).first()).toBeChecked();
  }

  /**
   * Locator for the ancestor `fieldset.form-section` of the styleguide
   * field whose label `<code>` reads `[label]`. Public so spec-level
   * helpers (e.g. table wizard tests) can scope queries to the section.
   */
  elementsBasicFormSection(label: string): Locator {
    return this.contentFrame.locator(
      `xpath=(//code[contains(text(),"[${label}]")]/..)[1]/ancestor::fieldset[@class="form-section"][1]`
    );
  }

  private async resolveElementsBasicFields(label: string, datepicker: boolean): Promise<{ visible: Locator, hidden: Locator }> {
    const visibleAttr = datepicker ? 'data-formengine-datepicker-real-input-name' : 'data-formengine-input-name';
    const formSection = this.elementsBasicFormSection(label);
    const visible = formSection.locator(`input[${visibleAttr}]`).first();
    const inputName = await visible.getAttribute(visibleAttr);
    if (inputName === null) {
      throw new Error(`Field "${label}" missing ${visibleAttr}`);
    }
    const hidden = formSection.locator(`input[name="${inputName}"]`);
    return { visible, hidden };
  }

  private async waitForElementsBasicFieldInitialized(label: string, datepicker: boolean): Promise<void> {
    const xpath = datepicker
      ? `xpath=(//label/code[contains(text(),"[${label}]")]/..)[1]/parent::*//*/input[@data-formengine-datepicker-real-input-name]`
      : `xpath=(//label/code[contains(text(),"[${label}]")]/..)[1]/parent::*//*/input[@data-formengine-input-name][@data-formengine-input-initialized]`;
    const initialized = this.contentFrame.locator(xpath).first();
    await expect(initialized).toBeAttached({ timeout: 30000 });
  }

  private async fillElementsBasicField(visible: Locator, value: string): Promise<void> {
    // Use clear-then-type-char-by-char to mirror Selenium's `fillField`.
    // `Locator.fill()` sets `.value` directly, which bypasses HTML5
    // maxlength enforcement and breaks the validation cases that rely on
    // the browser blocking the n+1th character.
    await visible.fill('');
    if (value !== '') {
      await visible.pressSequentially(value);
    }
    // Tab moves focus to trigger validation; Escape dismisses any popup the
    // adjacent field might have raised on focus (date picker etc.). Use the
    // page-level keyboard so the keys go to whatever just received focus,
    // not back to the input we filled.
    await this.page.keyboard.press('Tab');
    await this.page.keyboard.press('Escape');
    await expect(this.contentFrame.locator('#t3js-ui-block')).not.toBeVisible();
  }
}
