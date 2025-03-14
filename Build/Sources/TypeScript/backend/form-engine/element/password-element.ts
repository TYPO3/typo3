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

  public async connectedCallback(): Promise<void> {
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
  }
}

window.customElements.define('typo3-formengine-element-password', PasswordElement);
