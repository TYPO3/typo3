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

import Login from './login';
import RegularEvent from '@typo3/core/event/regular-event';

/**
 * Module: @typo3/backend/user-pass-login
 * JavaScript module for the UsernamePasswordLoginProvider
 * @exports @typo3/backend/user-pass-login
 */
class UserPassLogin {
  protected options: any;

  constructor() {
    this.options = {
      usernameField: '.t3js-login-username-field',
      passwordField: '.t3js-login-password-field',
      copyrightLink: '.t3js-login-copyright-link',
    };

    const usernameField: HTMLInputElement = document.querySelector(this.options.usernameField);
    const passwordField: HTMLInputElement = document.querySelector(this.options.passwordField);
    const copyrightLink: HTMLInputElement = document.querySelector(this.options.copyrightLink);

    // register submit handler
    Login.options.submitHandler = this.resetPassword;

    [usernameField, passwordField].forEach((field: HTMLInputElement) => new RegularEvent('keypress', this.showCapslockWarning).bindTo(field));
    ['input', 'change'].forEach((eventName: string) => new RegularEvent(eventName, this.showPasswordToggle).bindTo(passwordField));
    new RegularEvent('keydown', this.toggleCopyright).bindTo(copyrightLink);

    // if the login screen is shown in the login_frameset window for re-login,
    // then try to get the username of the current/former login from opening windows main frame:
    if (parent.opener?.TYPO3?.configuration?.username) {
      usernameField.value = parent.opener.TYPO3.configuration.username;
    }

    if (usernameField.value === '') {
      usernameField.focus();
    } else {
      passwordField.focus();
    }
  }


  /**
   * Checks whether capslock is enabled (returns TRUE if enabled, false otherwise)
   * thanks to http://24ways.org/2007/capturing-caps-lock
   *
   * @param {Event} e
   * @returns {boolean}
   */
  public static isCapslockEnabled(e: any): boolean {
    const ev = e ? e : window.event;
    if (!ev) {
      return false;
    }
    // get key pressed
    let pressedKeyAsciiCode = -1;
    if (ev.which) {
      pressedKeyAsciiCode = ev.which;
    } else if (ev.keyCode) {
      pressedKeyAsciiCode = ev.keyCode;
    }
    // get shift status
    let shiftPressed = false;
    if (ev.shiftKey) {
      shiftPressed = ev.shiftKey;
    } else if (ev.modifiers) {
      /* tslint:disable:no-bitwise */
      shiftPressed = !!(ev.modifiers & 4);
    }
    return (pressedKeyAsciiCode >= 65 && pressedKeyAsciiCode <= 90 && !shiftPressed)
      || (pressedKeyAsciiCode >= 97 && pressedKeyAsciiCode <= 122 && shiftPressed);
  }

  /**
   * Reset user password field to prevent it from being submitted
   */
  public resetPassword = (): void => {
    const passwordField: HTMLInputElement = document.querySelector(this.options.passwordField);
    if (passwordField === null || passwordField.value === '') {
      return;
    }
    const userIndent: HTMLInputElement = document.querySelector(Login.options.useridentField);
    if (userIndent) {
      userIndent.value = passwordField.value;
    }
    passwordField.value = '';
  };

  /**
   * Toggle copyright
   */
  public toggleCopyright = (event: KeyboardEvent): void => {
    if (event.key === ' ') {
      (event.target as HTMLLinkElement).click();
    }
  };

  /**
   * Capslock handling
   */
  private readonly attachCapslockWarning = (element: HTMLInputElement, label: string, message: string) => {
    const targetContainer = element.closest('.input-group');
    if (!targetContainer) {
      return;
    }

    if (targetContainer.querySelector('.input-group-text-warning-capslock')) {
      return;
    }

    const parentElement = element.parentElement;

    // create icon
    const warningIcon = `
      <span class="icon icon-size-small icon-state-default">
          <span class="icon-markup">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="m8 5.414 3.536 3.536.707-.707L8 4 3.757 8.243l.707.707L8 5.414zM4 11h8v1H4z"/></g></svg>
          </span>
      </span>
    `;

    // create message
    const warningMessage = document.createElement('span');
    warningMessage.classList.add('visually-hidden');
    warningMessage.textContent = message;

    // create element
    const warningElement = document.createElement('span');
    warningElement.classList.add('input-group-text', 'input-group-text-warning' ,'input-group-text-warning-capslock');
    warningElement.role = 'status';
    warningElement.innerHTML = warningIcon;
    warningElement.title = label;
    warningElement.appendChild(warningMessage);

    if (parentElement.classList.contains('form-control-clearable-wrapper')) {
      parentElement.insertAdjacentElement('afterend', warningElement);
      return;
    }

    element.insertAdjacentElement('afterend', warningElement);
  };

  private readonly removeCapslockWarning = (element: HTMLInputElement) => {
    const targetContainer = element.closest('.input-group');
    if (!targetContainer) {
      return;
    }

    const warningElement = targetContainer.querySelector('.input-group-text-warning-capslock');
    if (!warningElement) {
      return;
    }

    warningElement.remove();
  };

  private readonly showCapslockWarning = (event: Event): void => {
    const targetElement = (event.target as HTMLInputElement);
    const title = targetElement.dataset.capslockwarningTitle;
    const message = targetElement.dataset.capslockwarningMessage;

    if (UserPassLogin.isCapslockEnabled(event)) {
      this.attachCapslockWarning(targetElement, title, message);
    } else {
      this.removeCapslockWarning(targetElement);
    }
  };

  /**
   * Password toggle handling
   */
  private readonly attachPasswordToggle = (element: HTMLInputElement) => {
    const container = element.closest('.input-group');
    if (!container) {
      return;
    }

    if (container.querySelector('.t3js-login-toggle-password')) {
      return;
    }

    // icon
    const icon = `
      <span class="icon icon-size-small icon-state-default">
        <span class="icon-markup">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="currentColor"><path d="M8.07 3C4.112 3 1 5.286 1 8s2.97 5 7 5c3.889 0 7-2.286 7-4.93C15 5.285 11.889 3.142 8.212 3h-.141Zm-.025 1.127c.141 0 .423.141.423.282s-.14.282-.423.282c-.845 0-1.69.704-1.69 1.55 0 .14-.141.282-.423.282-.282 0-.423-.141-.423-.282.141-1.127 1.268-2.114 2.536-2.114ZM2 8.03c0-1.298 1.017-2.591 2.647-3.312-.296.432-.296 1.01-.296 1.587 0 2.02 1.63 3.606 3.703 3.606 2.074 0 3.704-1.587 3.704-3.606 0-.577-.148-1.01-.296-1.443C12.943 5.582 14 6.875 14 8.029c-.148 2.02-2.841 3.924-6 3.971-3.36-.047-6-1.95-6-3.97Z"/></g></svg>
        </span>
      </span>
    `;

    // button
    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add('btn', 'btn-default', 't3js-login-toggle-password');
    button.ariaLabel = element.dataset.passwordtoggleLabel ?? '';
    button.innerHTML = icon;

    button.addEventListener('click', () => {
      if (button.classList.contains('active')) {
        button.classList.remove('active');
        element.type = 'password';
      } else {
        button.classList.add('active');
        element.type = 'text';
      }
    });

    container.insertAdjacentElement('beforeend', button);
  };

  private readonly removePasswordToggle = (element: HTMLInputElement) => {
    const container = element.closest('.input-group');
    if (!container) {
      return;
    }

    const button = container.querySelector('.t3js-login-toggle-password');
    if (!button) {
      return;
    }

    button.remove();
    element.type = 'password';
  };

  private readonly showPasswordToggle = (event: Event): void => {
    const passwordField: HTMLInputElement = (event.target as HTMLInputElement);
    if (passwordField.value === '') {
      this.removePasswordToggle(passwordField);
      return;
    } else {
      this.attachPasswordToggle(passwordField);
    }
  };
}

export default new UserPassLogin();
