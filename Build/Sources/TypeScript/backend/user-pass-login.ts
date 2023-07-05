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
      passwordField: '.t3js-login-password-field',
      usernameField: '.t3js-login-username-field',
      copyrightLink: '.t3js-login-copyright-link',
      togglePassword: '.t3js-login-toggle-password',
    };

    const usernameField: HTMLInputElement = document.querySelector(this.options.usernameField);
    const passwordField: HTMLInputElement = document.querySelector(this.options.passwordField);
    const togglePassword: HTMLInputElement = document.querySelector(this.options.togglePassword);
    const copyrightLink: HTMLInputElement = document.querySelector(this.options.copyrightLink);

    // register submit handler
    Login.options.submitHandler = this.resetPassword;

    [usernameField, passwordField].forEach((field: HTMLInputElement) => new RegularEvent('keypress', this.showCapsLockWarning).bindTo(field));
    ['input', 'change'].forEach((eventName: string) => new RegularEvent(eventName, this.togglePasswordRevealer).bindTo(passwordField));
    new RegularEvent('keydown', this.toggleCopyright).bindTo(copyrightLink);
    new RegularEvent('click', (): void => this.togglePasswordVisibility()).bindTo(togglePassword);

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
    if (passwordField.value !== '') {
      const userIndent: HTMLInputElement = document.querySelector(Login.options.useridentField);
      if (userIndent) {
        userIndent.value = passwordField.value;
      }
      passwordField.value = '';
    }
  };

  public showCapsLockWarning = (event: Event): void => {
    (event.target as HTMLInputElement)?.parentElement?.parentElement.querySelector('.t3js-login-alert-capslock')?.classList.toggle('hidden', !UserPassLogin.isCapslockEnabled(event));
  };

  public toggleCopyright = (event: KeyboardEvent): void => {
    if (event.key === ' ') {
      (event.target as HTMLLinkElement).click();
    }
  };

  private togglePasswordRevealer = (event: Event): void => {
    const passwordField: HTMLInputElement = (event.target as HTMLInputElement);
    const togglePassword: HTMLButtonElement = document.querySelector(this.options.togglePassword);
    togglePassword.classList.toggle('hidden', passwordField.value === '');
    if (passwordField.value === '') {
      this.togglePasswordVisibility(true);
    }
  }

  private togglePasswordVisibility(forcePassword?: boolean): void {
    const passwordField: HTMLInputElement = document.querySelector(this.options.passwordField);
    const togglePassword: HTMLButtonElement = document.querySelector(this.options.togglePassword);
    if (forcePassword) {
      passwordField.type = 'password';
      togglePassword.classList.remove('active');
    } else {
      const isPasswordType = passwordField.type === 'password';
      passwordField.type = isPasswordType ? 'text' : 'password';
      togglePassword.classList.toggle('active', isPasswordType);
    }
  }
}

export default new UserPassLogin();
