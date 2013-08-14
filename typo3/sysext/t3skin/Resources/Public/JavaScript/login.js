var isWebKit = document.childNodes && !document.all && !navigator.taintEnabled;

TYPO3BackendLogin = {
	currentForm: '',

	/**
	 *  Initializing the Login Interface
	 */
	start: function() {
		TYPO3BackendLogin.preloadImages();
		TYPO3BackendLogin.registerEventListeners();
		$$('.clearIcon').each(function(clearIcon) {
			var name = clearIcon.id.substring(0, clearIcon.id.length - 10);
			TYPO3BackendLogin.setVisibilityOfClearIcon($(name), clearIcon);
		});

		// disable non-first-form elements so they are not submitted
		$$('.t3-login-form').each(function(form, index) {
			if (index == 0) {
				return;
			}
			$$('#' + form.id + ' .disableme').each(function(elem) {
				elem.disable();
			});
		});
		TYPO3BackendLogin.checkCookieSupport();
		TYPO3BackendLogin.checkForLogintypeCookie();
		TYPO3BackendLogin.checkForInterfaceCookie();
	},

	/**
	 * Preload the login process image, so it can show up immediatelly after submitting
	 */
	preloadImages: function() {
		var image = new Image();
		image.src = 'sysext/t3skin/icons/login-submit-progress.gif';
	},

	/**
	 * Registers listeners for the Login Interface (e.g. to toggle OpenID and Default login)
	 */
	registerEventListeners: function() {
		$$('.t3-login-form-footer-switch').each(function(elem) {
			Event.observe(
				elem,
				'click',
				function() {
					TYPO3BackendLogin.switchTo(elem.id.substr(21));
				}
			);
		});
		Event.observe(
			$('t3-login-submit'),
			'click',
			TYPO3BackendLogin.showLoginProcess
		);

			// The Interface selector is not always present, so this check is needed
		if (Object.isElement($('t3-interfaceselector'))) {
			TYPO3BackendLogin.observeEvents(
				$('t3-interfaceselector'),
				['change', 'blur'],
				TYPO3BackendLogin.interfaceSelectorChanged
			);
		}

		$$('.clearIcon').each(function(clearIcon) {
			var name = clearIcon.id.substring(0, clearIcon.id.length - 10);
			Event.observe(
				clearIcon,
				'click',
				function() { TYPO3BackendLogin.clearInputField($(name)); }
			);
			TYPO3BackendLogin.observeEvents(
				$(name),
				['focus', 'blur', 'keypress'],
				function() { TYPO3BackendLogin.setVisibilityOfClearIcon($(name), clearIcon); }
			);
			if (!isWebKit && $(name + '-alert-capslock')) {
				Event.observe(
					$(name),
					'keypress',
					function(event) { TYPO3BackendLogin.showCapsLockWarning($(name + '-alert-capslock'), event); }
				);
			}
		});
	},

	/**
	 * Wrapper for Event.observe that takes an array with events, instead of only one event
	 */
	observeEvents: function(element, events, handler) {
		events.each(function(event) {
			Event.observe(
				element,
				event,
				handler
			);
		});
	},

	/**
	 * Shows up the clear icon for a field which is not empty, and hides it otherwise
	 */
	setVisibilityOfClearIcon: function(formField, clearIcon) {
		if (formField.value) {
			clearIcon.show();
		} else {
			clearIcon.hide();
		}
	},

	/**
	 * To prevent its unintented use when typing the password, the user is warned when Capslock is on
	 */
	showCapsLockWarning: function(alertIcon, event) {
		if (isCapslock(event)) {
			alertIcon.show();
		} else {
			alertIcon.hide();
		}
	},

	/**
	 * Clears an input field and sets focus to it
	 */
	clearInputField: function(formField) {
		formField.value = '';
		formField.focus();
	},

	/**
	 * Change to different login form
	 */
	switchTo: function(formName) {
		if (!$('t3-login-form-' + formName)) {
			return;
		}
		if (TYPO3BackendLogin.currentForm == '') {
			// simply use the first one
			TYPO3BackendLogin.currentForm = $$('.t3-login-form')[0].id.substr(14);
		}
		TYPO3BackendLogin.hideForm(TYPO3BackendLogin.currentForm);
		TYPO3BackendLogin.showForm(formName);
		TYPO3BackendLogin.currentForm = formName;
	},

	/**
	 * Hide the login form with the given name
	 */
	hideForm: function(formName) {
		$('t3-login-form-' + formName).setStyle({'display': 'none'});
		$('t3-login-form-footer-' + formName).setStyle({'display': ''});
		$('t3-login-form-footer-' + formName).removeClassName('hidden');

		// disable form elements so they are not submitted
		$$('#t3-login-form-' + formName + ' .disableme').each(
			function(elem) {
				elem.disable();
			}
		);
	},

	/**
	 * Show the login form with the given name
	 */
	showForm: function(formName) {
		$('t3-login-form-' + formName).setStyle({'display': ''});
		$('t3-login-form-footer-' + formName).setStyle({'display': 'none'});
		$('t3-login-form-footer-' + formName).addClassName('hidden');

		// enable form elements again
		$$('#t3-login-form-' + formName + ' .disableme').each(
			function(elem) {
				elem.enable();
			}
		);

		$$('#t3-login-form-' + formName + ' input')[0].activate();
		TYPO3BackendLogin.setLogintypeCookie(formName);
	},

	/**
	 * Checks browser's cookie support
	 */
	checkCookieSupport: function() {
		Ext.util.Cookies.set('typo3-login-cookiecheck', true);
		cookieEnabled = Ext.util.Cookies.get('typo3-login-cookiecheck');

		if (!cookieEnabled) {
			TYPO3BackendLogin.showCookieWarning()
		}

		Ext.util.Cookies.clear('typo3-login-cookiecheck');
	},

	/**
	 * Hides input fields and shows cookie warning
	 */
	showCookieWarning: function() {
		Ext.get('t3-login-form-fields').setVisibilityMode(Ext.Element.DISPLAY).hide();
		Ext.get('t3-nocookies-error').show();
	},

	/**
	 * Hides cookie warning and shows input fields
	 */
	hideCookieWarning: function() {
		Ext.get('t3-nocookies-error').setVisibilityMode(Ext.Element.DISPLAY).hide();
		Ext.get('t3-login-form-fields').show();
	},

	/**
	 * Store a login type in a cookie to save it for future visits
	 * Login type means wether you login by username/password or OpenID
	 */
	setLogintypeCookie: function(type) {
		var now = new Date();
		var expires = new Date(now.getTime() + 1000*60*60*24*365); // cookie expires in one year
		document.cookie = 'typo3-login-method=' + type + '; expires=' + expires.toGMTString() + ';';
	},

	/**
	 * Check if a login type was stored in a cookie and change the Interface accordingly
	 */
	checkForLogintypeCookie: function() {
		var activeForm = Ext.util.Cookies.get('typo3-login-method');
		if (activeForm) {
			TYPO3BackendLogin.switchTo(activeForm);
		}
	},

	/**
	 * Store the new selected Interface in a cookie to save it for future visits
	 */
	interfaceSelectorChanged: function(event) {
		var now = new Date();
		var expires = new Date(now.getTime() + 1000*60*60*24*365); // cookie expires in one year
		document.cookie = 'typo3-login-interface=' + $('t3-interfaceselector').getValue() + '; expires=' + expires.toGMTString() + ';';
	},

	/**
	 * Check if an interface was stored in a cookie and preselect it in the select box
	 */
	checkForInterfaceCookie: function() {
		if (Object.isElement($('t3-interfaceselector'))) {
			var posStart = document.cookie.indexOf('typo3-login-interface=');
			if (posStart != -1) {
				var selectedInterface = document.cookie.substr(posStart + 22);
				selectedInterface = selectedInterface.substr(0, selectedInterface.indexOf(';'));
			}
			$('t3-interfaceselector').setValue(selectedInterface);
		}
	},

	/**
	 * Hide all form fields and show a progress message and icon
	 */
	showLoginProcess: function() {
		if ($('t3-login-error')) {
			$('t3-login-error').hide();
		}

		$('t3-login-form-fields').hide();
		$('t3-nocookies-error').hide();

		// setting a fixed height (based on the current, calculated height of the browser) for
		// the box with the login form, so it doesn't jump around when the spinner is shown
		var loginBoxHeight = $('t3-login-form-fields').getHeight();
		$('t3-login-process').setStyle({height: loginBoxHeight + 'px'}).show();
	}
};

Ext.onReady(TYPO3BackendLogin.start, TYPO3BackendLogin);
