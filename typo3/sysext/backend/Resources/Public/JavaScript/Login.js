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
 * JavaScript module for the backend login form
 */
define('TYPO3/CMS/Backend/Login', ['jquery', 'TYPO3/CMS/Backend/jquery.clearable', 'bootstrap'], function($) {
	var BackendLogin = {
		options: {
			loginForm: '#typo3-login-form',
			interfaceField: '.t3js-login-interface-field',
			interfaceSection: '.t3js-login-interface-section',
			usernameField: '.t3js-login-username-field',
			usernameSection: '.t3js-login-username-section',
			passwordField: '.t3js-login-password-field',
			passwordSection: '.t3js-login-password-section',
			openIdField: '.t3js-login-openid-field',
			openIdSection: '.t3js-login-openid-section',
			useridentField: '.t3js-login-userident-field',
			submitButton: '.t3js-login-submit',
			error: '.t3js-login-error',
			errorNoCookies: '.t3js-login-error-nocookies',
			formFields: '.t3js-login-formfields',
			switchOpenIdSelector: '.t3js-login-switch-to-openid',
			switchDefaultSelector: '.t3js-login-switch-to-default',
			submitHandler: null
		}
	},
	options = BackendLogin.options;

	// Checks whether capslock is enabled (returns TRUE if enabled, false otherwise)
	// thanks to http://24ways.org/2007/capturing-caps-lock
	BackendLogin.isCapslockEnabled = function(e) {
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
	 * Change to Interface for OpenId login and save the selection to a cookie
	 */
	BackendLogin.switchToOpenId = function(e) {
		if (!$(this).hasClass("disabled")) {
			$(options.switchOpenIdSelector).addClass('hidden');
			$(options.switchDefaultSelector).removeClass('hidden');
			$(options.interfaceSection).addClass('hidden');
			$(options.passwordSection + ', ' + options.usernameSection).addClass('hidden');
			$(options.openIdSection).removeClass('hidden');
			$(options.openIdField).trigger('focus');
			$(options.usernameField).val('openid_url');
			$(options.passwordField).val('');
			BackendLogin.setLogintypeCookie('openid');
		} else {
			return false;
		}
	};

	/**
	 * Change to Interface for default login and save the selection to a cookie
	 */
	BackendLogin.switchToDefault = function(e) {
		if (!$(this).hasClass("disabled")) {
			$(options.switchOpenIdSelector).removeClass('hidden');
			$(options.switchDefaultSelector).addClass('hidden');
			$(options.interfaceSection).removeClass('hidden');
			$(options.passwordSection + ', ' + options.usernameSection).removeClass('hidden');
			$(options.openIdSection).addClass('hidden');
			$(options.usernameField).trigger('focus');
			$(options.openIdField).val('');
			$(options.usernameField).val('');
			BackendLogin.setLogintypeCookie('username');
		} else {
			return false;
		}
	};

	/**
	 * Hide all form fields and show a progress message and icon
	 */
	BackendLogin.showLoginProcess = function() {
		$(options.submitButton).button('loading');
		$(options.error).addClass('hidden');
		$(options.errorNoCookies).addClass('hidden');

		$(options.switchOpenIdSelector).addClass('disabled');
		$(options.switchDefaultSelector).addClass('disabled');
	};

	/**
	 * Pass on to registered submit handler
	 *
	 * @param event
	 */
	BackendLogin.handleSubmit = function(event) {
		"use strict";

		BackendLogin.showLoginProcess();

		if (BackendLogin.options.submitHandler) {
			BackendLogin.options.submitHandler(event);
		}
	};

	/**
	 * Reset user password field to prevent it from being submitted
	 */
	BackendLogin.resetPassword = function() {
		"use strict";

		var $passwordField = $(BackendLogin.options.passwordField);
		$(BackendLogin.options.useridentField).val($passwordField.val());
		$passwordField.val('');
	};

	/**
	 * Store a login type in a cookie to save it for future visits
	 * Login type means whether you login by username/password or OpenID
	 */
	BackendLogin.setLogintypeCookie = function(type) {
		var now = new Date();
		var expires = new Date(now.getTime() + 1000*60*60*24*365); // cookie expires in one year
		document.cookie = 'typo3-login-method=' + type + '; expires=' + expires.toGMTString() + ';';
	};

	/**
	 * Check if a login type was stored in a cookie and change the Interface accordingly
	 */
	BackendLogin.checkForLogintypeCookie = function() {
		if (document.cookie.indexOf('typo3-login-method=openid') >- 1) {
			BackendLogin.switchToOpenId();
		}
	};

	/**
	 * Store the new selected Interface in a cookie to save it for future visits
	 */
	BackendLogin.interfaceSelectorChanged = function() {
		var now = new Date();
		var expires = new Date(now.getTime() + 1000*60*60*24*365); // cookie expires in one year
		document.cookie = 'typo3-login-interface=' + $(options.interfaceField).val() + '; expires=' + expires.toGMTString() + ';';
	};

	/**
	 * Clears an input field and sets focus to it
	 */
	BackendLogin.clearInputField = function($formFieldElement) {
		$formFieldElement.val('').focus();
	};

	/**
	 * Check if an interface was stored in a cookie and preselect it in the select box
	 */
	BackendLogin.checkForInterfaceCookie = function() {
		if ($(options.interfaceField).length) {
			var posStart = document.cookie.indexOf('typo3-login-interface=');
			if (posStart != -1) {
				var selectedInterface = document.cookie.substr(posStart + 22);
				selectedInterface = selectedInterface.substr(0, selectedInterface.indexOf(';'));
			}
			$(options.interfaceField).val(selectedInterface);
		}
	};

	/**
	 * To prevent its unintented use when typing the password, the user is warned when Capslock is on
	 */
	BackendLogin.showCapsLockWarning = function($alertIconElement, event) {
		$alertIconElement.toggleClass('hidden', !BackendLogin.isCapslockEnabled(event));
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
	 * Registers listeners for the Login Interface (e.g. to toggle OpenID and Default login)
	 */
	BackendLogin.initializeEvents = function() {
		// register default submit handler
		BackendLogin.options.submitHandler = BackendLogin.resetPassword;

		$(options.switchOpenIdSelector).on('click', BackendLogin.switchToOpenId);
		$(options.switchDefaultSelector).on('click', BackendLogin.switchToDefault);
		$(options.loginForm).on('submit', BackendLogin.handleSubmit);

		// The Interface selector is not always present, so this check is needed
		if ($(options.interfaceField).length > 0) {
			$(document).on('change blur', options.interfaceField, BackendLogin.interfaceSelectorChanged);
		}

		$(document).on('keypress', options.usernameField + ', ' + options.passwordField + ', ' + options.openIdField, function(evt) {
			BackendLogin.showCapsLockWarning($(this).parent().parent().find('.t3js-login-alert-capslock'), evt);
		});

		$('.t3js-clearable').clearable();

		// carousel news height transition
		$('.t3js-login-news-carousel').on('slide.bs.carousel', function(e) {
			var nextH = $(e.relatedTarget).height();
			$(this).find('div.active').parent().animate({ height: nextH }, 500);
		});
	};
	// initialize and return the BackendLogin object
	return function() {
		$(document).ready(function() {
			BackendLogin.checkForInterfaceCookie();
			BackendLogin.checkForLogintypeCookie();
			BackendLogin.checkCookieSupport();
			BackendLogin.initializeEvents();

			// previously named "startUp"

			// If the login screen is shown in the login_frameset window for re-login,
			// then try to get the username of the current/former login from opening windows main frame:
			try {
				if (parent.opener && parent.opener.TS && parent.opener.TS.username) {
					$(options.usernameField).val(parent.opener.TS.username);
				}
			} catch (error) {} // continue

			// previously named "check focus"
			if ($(options.usernameField).val() == '') {
				$(options.usernameField).focus();
			} else if ($(options.passwordField).attr('type') !== 'hidden') {
				$(options.passwordField).focus();
			}

		});

		// prevent opening the login form in the backend frameset
		if (top.location.href != self.location.href) {
			top.location.href = self.location.href;
		}

		TYPO3.BackendLogin = BackendLogin;
		return BackendLogin;
	}();
});
