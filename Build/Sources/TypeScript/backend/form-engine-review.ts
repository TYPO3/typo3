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

import 'bootstrap';
import DocumentService from '@typo3/core/document-service';
import { selector } from '@typo3/core/literals';
import '@typo3/backend/element/icon-element';
import Popover from './popover';
import { Popover as BootstrapPopover, Tab as BootstrapTab } from 'bootstrap';
import DomHelper from '@typo3/backend/utility/dom-helper';

/**
 * Module: @typo3/backend/form-engine-review
 * Enables interaction with record fields that need review
 * @exports @typo3/backend/form-engine-review
 */
export class FormEngineReview {

  /**
   * Class for the toggle button
   */
  private readonly toggleButtonClass: string = 't3js-toggle-review-panel';

  /**
   * Class of FormEngine labels
   */
  private readonly labelSelector: string = '.t3js-formengine-label';

  /**
   * The constructor, set the class properties default values
   */
  constructor(
    private readonly formElement: HTMLFormElement
  ) {
    this.initialize();
  }

  /**
   * Fetches all fields that have a failed validation
   */
  public static findInvalidField(): NodeListOf<HTMLElement> {
    return document.querySelectorAll('.tab-content .has-error');
  }

  /**
   * Initialize the events
   */
  private initialize(): void {
    DocumentService.ready().then((): void => {
      this.attachButtonToModuleHeader();
      this.checkForReviewableField();

      this.formElement.addEventListener('t3-formengine-postfieldvalidation', (): void => {
        this.checkForReviewableField();
      });
    });
  }

  /**
   * Renders an invisible button to toggle the review panel into the least possible toolbar
   */
  private attachButtonToModuleHeader(): void {
    const leastButtonBar: HTMLElement = document.querySelector('.t3js-module-docheader-bar-buttons').lastElementChild.querySelector('[role="toolbar"]');

    const icon = document.createElement('typo3-backend-icon');
    icon.setAttribute('identifier', 'actions-info');
    icon.setAttribute('size', 'small');

    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add('btn', 'btn-danger', 'btn-sm', 'hidden', this.toggleButtonClass);
    button.title = TYPO3.lang['buttons.reviewFailedValidationFields'];
    button.appendChild(icon);

    Popover.popover(button);
    leastButtonBar.prepend(button);
  }

  /**
   * Checks if fields have failed validation. In such case, the markup is rendered and the toggle button is unlocked.
   */
  private checkForReviewableField(): void {
    const invalidFields = FormEngineReview.findInvalidField();
    const toggleButton: HTMLElement = document.querySelector('.' + this.toggleButtonClass);
    if (toggleButton === null) {
      return;
    }

    if (invalidFields.length > 0) {
      const erroneousListGroup = document.createElement('div');
      erroneousListGroup.classList.add('list-group');

      for (const invalidField of invalidFields) {
        const fieldContainer = invalidField.closest('.t3js-formengine-validation-marker');
        const relatedInputField = invalidField.querySelector('[data-formengine-validation-rules]') as HTMLElement;
        const link = document.createElement('a');
        link.classList.add('list-group-item');
        link.href = '#';
        link.textContent = invalidField.querySelector(this.labelSelector)?.textContent || '';
        link.addEventListener('click', (e: Event) => {
          this.switchToField(e, fieldContainer, relatedInputField)
        });

        erroneousListGroup.append(link);
      }

      toggleButton.classList.remove('hidden');
      Popover.setOptions(toggleButton, <BootstrapPopover.Options>{
        html: true,
        content: erroneousListGroup as Element
      });
    } else {
      toggleButton.classList.add('hidden');
      Popover.hide(toggleButton);
    }
  }

  /**
   * Finds the field in the form and focuses it
   */
  private switchToField(e: Event, fieldContainer: Element, inputField: HTMLElement): void {
    e.preventDefault();

    // iterate possibly nested tab panels
    let ref = inputField;
    while (ref) {
      if (ref.matches('[id][role="tabpanel"]')) {
        const tabContainer = document.querySelector(selector`[aria-controls="${ref.id}"]`);
        new BootstrapTab(tabContainer).show();
      }
      ref = ref.parentElement;
    }

    // Check if the field is visible to the user. If this is the case, the field will be focussed, triggering a scroll
    // to the input field. If checkVisibility() returns false, the input field is not visible, therefore scroll the
    // field container into the view instead.
    if (inputField.checkVisibility()) {
      inputField.focus();
    } else {
      DomHelper.scrollIntoViewIfNeeded(fieldContainer);
    }
  }
}
