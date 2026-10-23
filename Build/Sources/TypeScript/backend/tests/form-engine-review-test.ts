/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { expect } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import DocumentService from '@typo3/core/document-service.js';
import Popover from '@typo3/backend/popover.js';
import { FormEngineReview } from '@typo3/backend/form-engine-review.js';
import type { PostValidationEvent } from '@typo3/backend/form-engine-validation.js';
import type { } from 'mocha';

describe('@typo3/backend/form-engine-review', () => {
  let container: HTMLElement;
  let setOptionsStub: SinonStub;

  const setupForm = (fieldMarkup: string): HTMLFormElement => {
    container = document.createElement('div');
    container.innerHTML = `
      <div class="t3js-module-docheader-buttons">
        <div><div role="toolbar"></div></div>
      </div>
      <form>
        <div class="t3js-formengine-validation-marker">
          <label class="t3js-formengine-label">My field</label>
          ${fieldMarkup}
        </div>
      </form>`;
    document.body.append(container);

    return container.querySelector('form');
  };

  /**
   * Marks the given field as invalid and returns the link the review panel offers for that field.
   * The panel content is captured from the popover options, so that the popover itself needs not be rendered.
   */
  const reviewLinkOfInvalidField = async (form: HTMLFormElement, invalidField: HTMLInputElement): Promise<HTMLElement> => {
    new FormEngineReview(form);
    await DocumentService.ready();

    form.dispatchEvent(new CustomEvent<PostValidationEvent>('t3-formengine-postfieldvalidation', {
      detail: { field: invalidField, isValid: false }
    }));

    const panelContent = setOptionsStub.lastCall.args[1].content as HTMLElement;

    return panelContent.querySelector<HTMLElement>('.list-group-item');
  };

  beforeEach((): void => {
    setOptionsStub = stub(Popover, 'setOptions');
  });

  afterEach((): void => {
    setOptionsStub.restore();
    document.querySelector('.t3js-toggle-review-panel')?.remove();
    container.remove();
  });

  it('focuses the input field of a visible field', async (): Promise<void> => {
    const form = setupForm('<input type="text" data-formengine-validation-rules="[]" data-formengine-input-name="data[tt_content][1][header]">');
    const inputField = form.querySelector<HTMLInputElement>('[data-formengine-validation-rules]');

    (await reviewLinkOfInvalidField(form, inputField)).click();

    expect(document.activeElement).to.equal(inputField);
  });

  it('focuses the date picker input if the real input field is hidden', async (): Promise<void> => {
    const form = setupForm(`
      <input type="hidden" data-formengine-validation-rules="[]" data-formengine-input-name="data[tt_content][1][date]">
      <input type="text" data-formengine-datepicker-real-input-name="data[tt_content][1][date]">`);
    const inputField = form.querySelector<HTMLInputElement>('[data-formengine-validation-rules]');
    const datePickerInput = form.querySelector<HTMLInputElement>('[data-formengine-datepicker-real-input-name]');

    (await reviewLinkOfInvalidField(form, inputField)).click();

    expect(document.activeElement).to.equal(datePickerInput);
  });
});
