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

import DocumentService from '@typo3/core/document-service';
import Icons from './icons';
import RegularEvent from '@typo3/core/event/regular-event';
import { selector } from '@typo3/core/literals';

export type PreSubmitCallback = (e: Event) => boolean;

type SubmitTriggerHTMLElement = HTMLAnchorElement|HTMLButtonElement;

/**
 * Module: @typo3/backend/document-save-actions
 * @deprecated: use @typo3/backend/form/submit-interceptor instead
 */
class DocumentSaveActions {
  private static instance: DocumentSaveActions = null;
  private preventDoubleClick: boolean = false;
  private readonly preSubmitCallbacks: PreSubmitCallback[] = [];

  private constructor() {
    console.warn('The module `@typo3/backend/document-save-actions.js` has been deprecated and will be removed in TYPO3 v14. Please consider migrating to `@typo3/backend/form/submit-interceptor.js` instead.');
    DocumentService.ready().then((): void => {
      this.initializeSaveHandling();
    });
  }

  public static getInstance(): DocumentSaveActions {
    if (DocumentSaveActions.instance === null) {
      DocumentSaveActions.instance = new DocumentSaveActions();
    }

    return DocumentSaveActions.instance;
  }

  public static registerEvents(): void {
    DocumentSaveActions.getInstance();
  }

  /**
   * Adds a callback being executed before submit
   */
  public addPreSubmitCallback(callback: PreSubmitCallback): void {
    if (typeof callback !== 'function') {
      throw 'callback must be a function.';
    }

    this.preSubmitCallbacks.push(callback);
  }

  /**
   * Initializes the save handling
   */
  private initializeSaveHandling(): void {
    const docHeader = document.querySelector('.t3js-module-docheader');
    if (docHeader === null) {
      return;
    }

    const elements = [
      'button[form]',
      'button[name^="_save"]',
      'a[data-name^="_save"]',
      'button[name="CMD"][value^="save"]',
      'a[data-name="CMD"][data-value^="save"]',
    ].join(',');

    new RegularEvent('click', (e: Event, target: SubmitTriggerHTMLElement): void => {
      if (this.preventDoubleClick) {
        return;
      }

      const form = this.getAttachedForm(target);
      if (form === null) {
        return;
      }

      // Run any preSubmit callbacks
      for (const callback of this.preSubmitCallbacks) {
        const callbackResult = callback(e);
        if (!callbackResult) {
          e.preventDefault();
          return;
        }
      }

      this.preventDoubleClick = true;

      // All callbacks were executed, add dummy field for POST action to make clear we're submitting something...
      this.attachSaveFieldToForm(form, target);

      form.addEventListener('submit', (): void => {
        const splitButton = target.closest('.t3js-splitbutton');
        let affectedButton: SubmitTriggerHTMLElement;
        if (splitButton !== null) {
          affectedButton = splitButton.firstElementChild as HTMLButtonElement;
          splitButton.querySelectorAll('button').forEach((button: HTMLButtonElement): void => { button.disabled = true; });
        } else {
          affectedButton = target;
          if (affectedButton instanceof HTMLAnchorElement) {
            affectedButton.classList.add('disabled');
          } else {
            affectedButton.disabled = true;
          }
        }

        Icons.getIcon('spinner-circle', Icons.sizes.small).then((markup: string): void => {
          affectedButton.replaceChild(document.createRange().createContextualFragment(markup), target.querySelector('.t3js-icon'));
        }).catch(() => {
          // Catch error in case the promise was not resolved
          // e.g. loading a new page
        });
      }, { once: true });
    }).delegateTo(docHeader, elements);
  }

  private getAttachedForm(trigger: SubmitTriggerHTMLElement): HTMLFormElement|null {
    let form;
    if (trigger instanceof HTMLAnchorElement) {
      form = document.querySelector(selector`#${trigger.dataset.form}`) as HTMLFormElement|null;
    } else {
      form = trigger.form;
    }

    if (!form) {
      form = trigger.closest('form');
    }

    return form;
  }

  private attachSaveFieldToForm(form: HTMLFormElement, trigger: SubmitTriggerHTMLElement): void {
    const inputId = form.name + '_save_field';
    let saveValueInput = document.getElementById(inputId) as HTMLInputElement|null;
    if (saveValueInput === null) {
      saveValueInput = document.createElement('input');
      saveValueInput.id = inputId;
      saveValueInput.type = 'hidden';

      form.append(saveValueInput);
    }

    saveValueInput.name = trigger instanceof HTMLAnchorElement ? trigger.dataset.name : trigger.name;
    saveValueInput.value = trigger instanceof HTMLAnchorElement ? trigger.dataset.value : trigger.value;
  }
}

export default DocumentSaveActions;
