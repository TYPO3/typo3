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
 * Module: TYPO3/CMS/Backend/Login
 * JavaScript module for the backend login form
 */
define(['jquery', 'TYPO3/CMS/Backend/jquery.clearable', 'bootstrap'], function($) {
  'use strict';

  /**
   *
   * @type {{options: {loginForm: string, interfaceField: string, useridentField: string, submitButton: string, error: string, errorNoCookies: string, formFields: string, submitHandler: null}}}
   * @exports TYPO3/CMS/Backend/Login
   */
  var BackendLogin = {
      options: {
        loginForm: '#typo3-login-form',
        interfaceField: '.t3js-login-interface-field',
        useridentField: '.t3js-login-userident-field',
        submitButton: '.t3js-login-submit',
        error: '.t3js-login-error',
        errorNoCookies: '.t3js-login-error-nocookies',
        formFields: '.t3js-login-formfields',
        submitHandler: null
      }
    },
    options = BackendLogin.options;

  /**
   * Hide all form fields and show a progress message and icon
   */
  BackendLogin.showLoginProcess = function() {
    BackendLogin.showLoadingIndicator();
    $(options.error).addClass('hidden');
    $(options.errorNoCookies).addClass('hidden');
  };

  /**
   * Show the loading spinner in the submit button
   */
  BackendLogin.showLoadingIndicator = function() {
    $(options.submitButton).button('loading');
  };

  /**
   * Pass on to registered submit handler
   *
   * @param {Event} event
   */
  BackendLogin.handleSubmit = function(event) {
    BackendLogin.showLoginProcess();

    if (BackendLogin.options.submitHandler) {
      BackendLogin.options.submitHandler(event);
    }
  };

  /**
   * Store the new selected Interface in a cookie to save it for future visits
   */
  BackendLogin.interfaceSelectorChanged = function() {
    var now = new Date();
    var expires = new Date(now.getTime() + 1000 * 60 * 60 * 24 * 365); // cookie expires in one year
    document.cookie = 'typo3-login-interface=' + $(options.interfaceField).val() + '; expires=' + expires.toGMTString() + ';';
  };

  /**
   * Check if an interface was stored in a cookie and preselect it in the select box
   */
  BackendLogin.checkForInterfaceCookie = function() {
    if ($(options.interfaceField).length) {
      var posStart = document.cookie.indexOf('typo3-login-interface=');
      if (posStart !== -1) {
        var selectedInterface = document.cookie.substr(posStart + 22);
        selectedInterface = selectedInterface.substr(0, selectedInterface.indexOf(';'));
        $(options.interfaceField).val(selectedInterface);
      }
    }
  };

  /**
   * Hides input fields and shows cookie warning
   */
  BackendLogin.showCookieWarning = function() {
    $(options.formFields).addClass('hidden');
    $(options.errorNoCookies).removeClass('hidden');
  };

  /**
   * Hides cookie warning and shows input fields
   */
  BackendLogin.hideCookieWarning = function() {
    $(options.formFields).removeClass('hidden');
    $(options.errorNoCookies).addClass('hidden');
  };

  /**
   * Checks browser's cookie support
   * see http://stackoverflow.com/questions/8112634/jquery-detecting-cookies-enabled
   */
  BackendLogin.checkCookieSupport = function() {
    var cookieEnabled = navigator.cookieEnabled;

    // when cookieEnabled flag is present and false then cookies are disabled.
    if (cookieEnabled === false) {
      BackendLogin.showCookieWarning();
    } else {
      // try to set a test cookie if we can't see any cookies and we're using
      // either a browser that doesn't support navigator.cookieEnabled
      // or IE (which always returns true for navigator.cookieEnabled)
      if (!document.cookie && (cookieEnabled === null || /*@cc_on!@*/false)) {
        document.cookie = 'typo3-login-cookiecheck=1';

        if (!document.cookie) {
          BackendLogin.showCookieWarning();
        } else {
          // unset the cookie again
          document.cookie = 'typo3-login-cookiecheck=; expires=' + new Date(0).toUTCString();
        }
      }
    }
  };

  /**
   * Registers listeners for the Login Interface
   */
  BackendLogin.initializeEvents = function() {
    $(document).ajaxStart(BackendLogin.showLoadingIndicator);
    $(options.loginForm).on('submit', BackendLogin.handleSubmit);

    // The Interface selector is not always present, so this check is needed
    if ($(options.interfaceField).length > 0) {
      $(document).on('change blur', options.interfaceField, BackendLogin.interfaceSelectorChanged);
    }

    $('.t3js-clearable').clearable();

    // carousel news height transition
    $('.t3js-login-news-carousel').on('slide.bs.carousel', function(e) {
      var nextH = $(e.relatedTarget).height();
      $(this).find('div.active').parent().animate({height: nextH}, 500);
    });
  };

  // initialize and return the BackendLogin object
  $(function() {
    BackendLogin.checkCookieSupport();
    BackendLogin.checkForInterfaceCookie();
    BackendLogin.initializeEvents();
  });

  // prevent opening the login form in the backend frameset
  if (top.location.href !== location.href) {
    top.location.href = location.href;
  }

  return BackendLogin;
});
