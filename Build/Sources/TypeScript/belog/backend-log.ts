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

import Modal from '@typo3/backend/modal';
import DocumentService from '@typo3/core/document-service';
import DateTimePicker from '@typo3/backend/date-time-picker';
import '@typo3/backend/input/clearable';
import {MessageUtility} from '@typo3/backend/utility/message-utility';

/**
 * Module: @typo3/belog/backend-log
 * JavaScript for backend log
 * @exports @typo3/belog/backend-log
 */
class BackendLog {
  private clearableElements: NodeListOf<HTMLInputElement> = null;
  private dateTimePickerElements: NodeListOf<HTMLInputElement> = null;
  private elementBrowserElements: NodeListOf<HTMLAnchorElement> = null

  constructor() {
    DocumentService.ready().then((): void => {
      this.clearableElements = document.querySelectorAll('.t3js-clearable');
      this.dateTimePickerElements = document.querySelectorAll('.t3js-datetimepicker');
      this.elementBrowserElements = document.querySelectorAll('.t3js-element-browser');
      this.initializeClearableElements();
      this.initializeDateTimePickerElements();
      this.initializeElementBrowserElements();
      this.initializeElementBrowserEventListener();
    });
  }

  private initializeClearableElements(): void {
    this.clearableElements.forEach(
      (clearableField: HTMLInputElement) => clearableField.clearable()
    );
  }

  private initializeDateTimePickerElements(): void {
    this.dateTimePickerElements.forEach(
      (dateTimePickerElement: HTMLInputElement) => DateTimePicker.initialize(dateTimePickerElement)
    );
  }

  private initializeElementBrowserElements(): void {
    this.elementBrowserElements.forEach((element: HTMLAnchorElement): void => {
      const triggerField = <HTMLInputElement>document.getElementById(element.dataset.triggerFor);
      element.dataset.params = triggerField.name + '|||pages';
      element.addEventListener('click', (event: Event): void => {
        event.preventDefault();
        const target = <HTMLAnchorElement>event.currentTarget;
        Modal.advanced({
          type: Modal.types.iframe,
          content: target.dataset.target + '&mode=' + target.dataset.mode + '&bparams=' + target.dataset.params,
          size: Modal.sizes.large
        });
      });
    });
  }

  private initializeElementBrowserEventListener(): void {
    window.addEventListener('message', (e: MessageEvent): void => {
      if (!MessageUtility.verifyOrigin(e.origin)
        || e.data.actionName !== 'typo3:elementBrowser:elementAdded'
        || typeof e.data.fieldName !== 'string'
        || typeof e.data.value !== 'string'
      ) {
        return;
      }

      const field = <HTMLInputElement>document.querySelector('input[name="' + e.data.fieldName + '"]');
      if (field) {
        field.value = e.data.value.split('_').pop();
      }
    });
  }
}

export default new BackendLog();
