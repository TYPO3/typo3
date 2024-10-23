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
import FormEngine from '@typo3/backend/form-engine';
import { selector } from '@typo3/core/literals';
import Modal from '@typo3/backend/modal';
import { FormEngineLinkBrowserSetLinkEvent } from '@typo3/backend/event/form-engine-link-browser-set-link-event';

/**
 * This module is used for the field control "Link popup"
 */
class LinkPopup {
  private controlElement: HTMLElement = null;

  constructor(controlElementId: string) {
    DocumentService.ready().then((): void => {
      this.controlElement = document.querySelector<HTMLElement>(controlElementId);
      if (this.controlElement === null) {
        return;
      }
      this.controlElement.addEventListener('click', this.handleControlClick);
    });
  }

  /**
   * @param {Event} e
   */
  private readonly handleControlClick = (e: Event): void => {
    e.preventDefault();

    const itemName = this.controlElement.dataset.itemName;

    const valueField = document.querySelector<HTMLInputElement>(selector`[name="${itemName}"]`);
    const humanReadableField = document.querySelector<HTMLInputElement>(selector`[data-formengine-input-name="${itemName}"]`);

    const url = this.controlElement.getAttribute('href')
      + '&P[currentValue]=' + encodeURIComponent(document.forms.namedItem('editform')[itemName].value)
      + '&P[currentSelectedValues]=' + encodeURIComponent(valueField.value);

    const modal = Modal.advanced({
      type: Modal.types.iframe,
      content: url,
      size: Modal.sizes.large,
    });

    modal.addEventListener(FormEngineLinkBrowserSetLinkEvent.eventName, (e: FormEngineLinkBrowserSetLinkEvent): void => {
      const { value, onFieldChangeItems } = e;

      humanReadableField.value = value;
      humanReadableField.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));

      if (Array.isArray(onFieldChangeItems)) {
        FormEngine.processOnFieldChange(onFieldChangeItems);
      }

      modal.hideModal();
    });
  };
}

export default LinkPopup;
