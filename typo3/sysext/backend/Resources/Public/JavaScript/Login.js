/**
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
define('TYPO3/CMS/Backend/Login', ['jquery'], function($) {

	var isWebKit = document.childNodes && !document.all && !navigator.taintEnabled;
	var BackendLogin = {
		options: {
			usernameField: '#t3-username',
			passwordField: '#t3-password',
			useridentField: '#t3-field-userident',
			openIdField: '#openid_url',
			submitButton: '#t3-login-submit',
			clearIconSelector: '.t3-login-clearInputField',
			interfaceSelector: '#t3-interfaceselector'
		}
	},
	options = BackendLogin.options;

	// Checks weather capslock is enabled (returns TRUE if enabled, false otherwise)
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
		return (((which >= 65 && which <= 90) && !shift_status) ||
			((which >= 97 && which <= 122) && shift_status));
	};

	/**
	 * Change to Interface for OpenId login and save the selection to a cookie
	 */
	BackendLogin.switchToOpenId = function() {
		$('t3-login-openIdLogo').show();

		$('#t3-login-form-footer-default').hide();
		$('#t3-login-form-footer-openId').show();

		$('#t3-login-username-section, #t3-login-password-section').hide();
		$('#t3-login-openid_url-section').show();
		$('#t3-login-interface-section').hide();

		$(options.openIdField).trigger('focus');
		if ($(options.usernameField).val() == '') {
			$(options.usernameField).val('openid_url');
		}

		setLogintypeCookie('openid');
	};

	/**
	 * Change to Interface for default login and save the selection to a cookie
	 */
	BackendLogin.switchToDefault = function() {
		$('#t3-login-openIdLogo').hide();

		if ($(options.usernameField).val() == 'openid_url') {
			$(options.usernameField).val('');
		}

		$('#t3-login-form-footer-default').show();
		$('#t3-login-form-footer-openId').hide();
		$('#t3-login-username-section, #t3-login-password-section').show();
		$('#t3-login-openid_url-section').hide();
		$('#t3-login-interface-section').show();

		$(options.usernameField).trigger('focus');

		setLogintypeCookie('username');
	};

	/**
	 * Hide all form fields and show a progress message and icon
	 */
	BackendLogin.showLoginProcess = function() {
		// setting a fixed height (based on the current, calculated height of the browser) for
		// the box with the login form, so it doesn't jump around when the spinner is shown
		var loginBoxHeight = $('#t3-login-form-fields').height();
		$('#t3-login-process').height(loginBoxHeight).show();
		$('#t3-login-error').hide();
		$('#t3-login-form-fields').hide();
		$('#t3-nocookies-error').hide();
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
		document.cookie = 'typo3-login-interface=' + $(options.interfaceSelector).val() + '; expires=' + expires.toGMTString() + ';';
	};

	/**
	 * Shows up the clear icon for a field which is not empty, and hides it otherwise
	 */
	BackendLogin.setVisibilityOfClearIcon = function($formFieldElement) {
		if ($formFieldElement.val().length > 0) {
			$formFieldElement.next(options.clearIconSelector).find('a').show();
		} else {
			$formFieldElement.next(options.clearIconSelector).find('a').hide();
		}
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
		if ($(options.interfaceSelector).length) {
			var posStart = document.cookie.indexOf('typo3-login-interface=');
			if (posStart != -1) {
				var selectedInterface = document.cookie.substr(posStart + 22);
				selectedInterface = selectedInterface.substr(0, selectedInterface.indexOf(';'));
			}
			$(options.interfaceSelector).val(selectedInterface);
		}
	};

	/**
	 * To prevent its unintented use when typing the password, the user is warned when Capslock is on
	 */
	BackendLogin.showCapsLockWarning = function($alertIconElement, event) {
		if (BackendLogin.isCapslockEnabled(event) === true) {
			$alertIconElement.show();
		} else {
			$alertIconElement.hide();
		}
	};

	/**
	 * Hides input fields and shows cookie warning
	 */
	BackendLogin.showCookieWarning = function() {
		$('#t3-login-form-fields').hide();
		$('#t3-nocookies-error').show();
	};

	/**
	 * Hides cookie warning and shows input fields
	 */
	BackendLogin.hideCookieWarning = function() {
		$('#t3-nocookies-error').hide();
		$('#t3-login-form-fields').show();
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
		$(document).on('click', '#t3-login-switchToOpenId', BackendLogin.switchToOpenId);
		$(document).on('click', '#t3-login-switchToDefault', BackendLogin.switchToDefault);
		$(document).on('click', options.submitButton, BackendLogin.showLoginProcess);

			// The Interface selector is not always present, so this check is needed
		if ($(options.interfaceSelector).length > 0) {
			$(document).on('change blur', options.interfaceSelector, BackendLogin.interfaceSelectorChanged);
		}

		$(document).on('click', options.clearIconSelector, function() {
			BackendLogin.clearInputField($(this).prev());
		});
		$(document).on('focus blur keypress', options.usernameField + ', ' + options.passwordField + ', ' + options.openIdField, function() {
			BackendLogin.setVisibilityOfClearIcon($(this));
		});
		if (!isWebKit) {
			$(document).on('keypress', options.usernameField + ', ' + options.passwordField + ', ' + options.openIdField, function(evt) {
				BackendLogin.showCapsLockWarning($(this).siblings('.t3-login-alert-capslock'), evt);
			});
		}
	};
	// intialize and return the BackendLogin object
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
			} catch(error) {} // continue

			BackendLogin.setVisibilityOfClearIcon($(options.usernameField));
			BackendLogin.setVisibilityOfClearIcon($(options.passwordField));

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