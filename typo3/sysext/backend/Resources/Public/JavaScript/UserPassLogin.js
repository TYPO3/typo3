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
 * Module: TYPO3/CMS/Backend/UserPassLogin
 * JavaScript module for the UsernamePasswordLoginProvider
 */
define(['jquery', 'TYPO3/CMS/Backend/Login'], function($, Login) {
  'use strict';

  /**
   *
   * @type {{options: {usernameField: string, passwordField: string}}}
   * @exports TYPO3/CMS/Backend/UserPassLogin
   */
  var UserPassLogin = {
    options: {
      usernameField: '.t3js-login-username-field',
      passwordField: '.t3js-login-password-field'
    }
  };

  /**
   * Checks whether capslock is enabled (returns TRUE if enabled, false otherwise)
   * thanks to http://24ways.org/2007/capturing-caps-lock
   *
   * @param {Event} e
   * @returns {Boolean}
   */
  UserPassLogin.isCapslockEnabled = function(e) {
    var ev = e ? e : window.event;
    if (!ev) {
      return;
    }
    // get key pressed
    var which = -1;
    if (ev.which) {
      which = ev.which;
    } else if (ev.keyCode) {
      which = ev.keyCode;
    }
    // get shift status
    var shift_status = false;
    if (ev.shiftKey) {
      shift_status = ev.shiftKey;
    } else if (ev.modifiers) {
      shift_status = !!(ev.modifiers & 4);
    }
    return (which >= 65 && which <= 90 && !shift_status)
      || (which >= 97 && which <= 122 && shift_status);
  };

  /**
   * Reset user password field to prevent it from being submitted
   */
  UserPassLogin.resetPassword = function() {
    var $passwordField = $(UserPassLogin.options.passwordField);
    if ($passwordField.val()) {
      $(Login.options.useridentField).val($passwordField.val());
      $passwordField.val('');
    }
  };

  /**
   * To prevent its unintended use when typing the password, the user is warned when Capslock is on
   *
   * @param {Event} event
   */
  UserPassLogin.showCapsLockWarning = function(event) {
    $(this).parent().parent().find('.t3js-login-alert-capslock').toggleClass('hidden', !UserPassLogin.isCapslockEnabled(event));
  };

  // initialize and return the UserPassLogin object
  $(function() {
    // register submit handler
    Login.options.submitHandler = UserPassLogin.resetPassword;

    var $usernameField = $(UserPassLogin.options.usernameField);
    var $passwordField = $(UserPassLogin.options.passwordField);

    $usernameField.on('keypress', UserPassLogin.showCapsLockWarning);
    $passwordField.on('keypress', UserPassLogin.showCapsLockWarning);

    // If the login screen is shown in the login_frameset window for re-login,
    // then try to get the username of the current/former login from opening windows main frame:
    try {
      if (parent.opener && parent.opener.TS && parent.opener.TS.username) {
        $usernameField.val(parent.opener.TS.username);
      }
    } catch (error) {
    } // continue

    if ($usernameField.val() === '') {
      $usernameField.focus();
    } else {
      $passwordField.focus();
    }
  });

  return UserPassLogin;
});
