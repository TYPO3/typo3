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
  private passwordPolicyInfo: HTMLElement = null;
  private passwordPolicySet: boolean = false;

  public connectedCallback(): void {
    this.element = <HTMLInputElement>this.querySelector('#' + (this.getAttribute('recordFieldId') || '' as string));
    this.passwordPolicyInfo = <HTMLElement>this.querySelector('#password-policy-info-' + this.element.id);
    this.passwordPolicySet = (this.getAttribute('passwordPolicy') || '') !== '';

    if (!this.element) {
      return;
    }

    this.registerEventHandler();
  }

  private registerEventHandler(): void {
    if (this.passwordPolicySet) {
      this.element.addEventListener('focusin', (e: Event): void => {
        this.passwordPolicyInfo.classList.remove('hidden');
      });

      this.element.addEventListener('focusout', (e: Event): void => {
        this.passwordPolicyInfo.classList.add('hidden');
      });
    }
  }
}

window.customElements.define('typo3-formengine-element-password', PasswordElement);
