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
import { type Popover as BootstrapPopover, Tab as BootstrapTab } from 'bootstrap';
import type { PostValidationEvent } from '@typo3/backend/form-engine-validation';
import DomHelper from '@typo3/backend/utility/dom-helper';

/**
 * Module: @typo3/backend/form-engine-review
 * Enables interaction with record fields that need review
 * @exports @typo3/backend/form-engine-review
 */
export class FormEngineReview {

  private readonly toggleButtonClass: string = 't3js-toggle-review-panel';
  private readonly labelSelector: string = '.t3js-formengine-label';
  private readonly invalidFields: Set<HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement>;

  /**
   * The constructor, set the class properties default values
   */
  constructor(
    private readonly formElement: HTMLFormElement
  ) {
    this.invalidFields = new Set();
    this.initialize();
  }

  /**
   * Initialize the events
   */
  private initialize(): void {
    this.formElement.addEventListener('t3-formengine-postfieldvalidation', (e: CustomEvent<PostValidationEvent>): void => {
      const field = e.detail.field;
      if (e.detail.isValid) {
        this.invalidFields.delete(field);
      } else {
        this.invalidFields.add(field);
      }
      this.checkForReviewableField();
    });

    DocumentService.ready().then((): void => {
      this.attachButtonToModuleHeader();
      this.checkForReviewableField();
    });
  }

  /**
   * Renders an invisible button to toggle the review panel into the least possible toolbar
   */
  private attachButtonToModuleHeader(): void {
    const leastButtonBar: HTMLElement = document.querySelector('.t3js-module-docheader-bar-buttons').lastElementChild.querySelector('[role="toolbar"]');

    const icon = document.createElement('typo3-backend-icon');
    icon.setAttribute('identifier', 'actions-exclamation-circle');
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
    const toggleButton: HTMLElement = document.querySelector('.' + this.toggleButtonClass);
    if (toggleButton === null) {
      return;
    }

    if (this.invalidFields.size > 0) {
      const erroneousListGroup = document.createElement('div');
      erroneousListGroup.classList.add('list-group');

      for (const invalidField of this.invalidFields) {
        const fieldContainer = invalidField.closest('.t3js-formengine-validation-marker');
        if (fieldContainer === null) {
          console.error(invalidField);
          throw new Error('Could not find an element containing the `t3js-formengine-validation-marker` class for the previously logged input field.');
        }
        const relatedInputField = fieldContainer.querySelector('[data-formengine-validation-rules]') as HTMLElement;
        if (relatedInputField === null) {
          console.error(fieldContainer);
          throw new Error('Could not find an element containing the `data-formengine-validation-rules` attribute for the previously logged container.');
        }
        const link = document.createElement('a');
        link.classList.add('list-group-item');
        link.href = '#';
        link.textContent = fieldContainer.querySelector(this.labelSelector)?.textContent || '';
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
