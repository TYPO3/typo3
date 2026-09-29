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
import { selector } from '@typo3/core/literals';
import type { CopyToClipboard } from '@typo3/backend/copy-to-clipboard';

/**
 * Module: @typo3/backend/form-engine/element/password-element
 *
 * Functionality for the password element
 *
 * @example
 * <typo3-formengine-element-password recordFieldId="some-id" passwordPolicy="some-policy">
 *   ...
 * </typo3-formengine-element-password>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
class PasswordElement extends HTMLElement {
  private element: HTMLInputElement = null;
  private passwordPolicyInfo: HTMLElement|null = null;
  private passwordPolicySet: boolean = false;
  private copyToClipboard: CopyToClipboard|null = null;

  public async connectedCallback(): Promise<void> {
    if (this.element !== null) {
      // Element is already initialized, which means the component has been rendered before. Nothing to do here.
      return;
    }

    const recordFieldId = this.getAttribute('recordFieldId');
    if (recordFieldId === null) {
      return;
    }

    await DocumentService.ready();
    this.element = this.querySelector<HTMLInputElement>(selector`#${recordFieldId}`);
    if (!this.element) {
      return;
    }

    this.passwordPolicyInfo = this.querySelector<HTMLElement>(selector`#password-policy-info-${this.element.id}`);
    this.passwordPolicySet = (this.getAttribute('passwordPolicy') || '') !== '';
    this.copyToClipboard = this.querySelector<CopyToClipboard>('typo3-copy-to-clipboard');

    this.registerEventHandler();
  }

  private registerEventHandler(): void {
    if (this.passwordPolicySet && this.passwordPolicyInfo !== null) {
      this.element.addEventListener('focusin', (): void => {
        this.passwordPolicyInfo.classList.remove('hidden');
      });

      this.element.addEventListener('focusout', (): void => {
        this.passwordPolicyInfo.classList.add('hidden');
      });
    }

    // The field is empty until a password is typed or generated: a stored one is hashed and
    // cannot be copied, so the button is only offered for a plain value. Its wrapper is made
    // an input group along with it, which would otherwise square the corners of the input.
    if (this.copyToClipboard !== null) {
      const offerCopy = (): void => {
        const hasValue = this.element.value !== '';
        this.copyToClipboard.text = this.element.value;
        this.copyToClipboard.hidden = !hasValue;
        this.copyToClipboard.parentElement.classList.toggle('input-group', hasValue);
      };
      this.element.addEventListener('input', offerCopy);
      this.element.addEventListener('change', offerCopy);
    }
  }
}

window.customElements.define('typo3-formengine-element-password', PasswordElement);
