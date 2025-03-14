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
import { Resizable } from './modifier/resizable';
import { Tabbable } from './modifier/tabbable';

/**
 * Module: @typo3/backend/form-engine/element/json-element
 *
 * Functionality for the json element
 *
 * @example
 * <typo3-formengine-element-json recordFieldId="some-id">
 *   ...
 * </typo3-formengine-element-json>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class JsonElement extends HTMLElement {
  private element: HTMLTextAreaElement = null;

  public async connectedCallback(): Promise<void> {
    await DocumentService.ready();
    this.element = document.getElementById((this.getAttribute('recordFieldId') || '' as string)) as HTMLTextAreaElement;

    if (!this.element) {
      return;
    }

    Resizable.enable(this.element);
    Tabbable.enable(this.element);
  }
}

window.customElements.define('typo3-formengine-element-json', JsonElement);
