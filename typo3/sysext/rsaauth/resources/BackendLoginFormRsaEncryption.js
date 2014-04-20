/**
 * Object that handles RSA encryption and submission of the form
 */
TYPO3RsaBackendLogin = {

	/**
	 * Field in which users enter their password
	 */
	userPasswordField: '',

	/**
	 * Field that is used by TYPO3 to evaluate the password during login process
	 */
	typo3PasswordField: '',

	/**
	 * Replace event handler of submit button
	 */
	initialize: function() {
		this.userPasswordField = document.loginform.p_field;
		this.typo3PasswordField = document.loginform.userident;
		var submitButton = $('t3-login-submit');
		Event.stopObserving(
			submitButton,
			'click',
			TYPO3BackendLogin.showLoginProcess
		);
		Event.observe(
			submitButton,
			'click',
			TYPO3RsaBackendLogin.handleFormSubmitRequest
		);
	},

	/**
	 * Fetches a new public key by Ajax and encrypts the password for transmission
	 *
	 * @param event
	 */
	handleFormSubmitRequest: function(event) {
		event.preventDefault();
		// Call the original event handler
		TYPO3BackendLogin.showLoginProcess();

		Ext.Ajax.request({
			url: TYPO3.settings.ajaxUrls['BackendLogin::getRsaPublicKey'],
			params: {
				'skipSessionUpdate': 1
			},
			method: 'GET',
			success: TYPO3RsaBackendLogin.handlePublicKeyResponse
		});
	},

	/**
	 * Parses the Json response and triggers submission of the form
	 *
	 * @param response Ajax response object
	 */
	handlePublicKeyResponse: function(response) {
		var publicKey = Ext.util.JSON.decode(response.responseText);
		if (publicKey.publicKeyModulus && publicKey.exponent) {
			TYPO3RsaBackendLogin.encryptPasswordAndSubmitForm(publicKey);
		} else {
			alert('No public key could be generated. Please inform your TYPO3 administrator to check the OpenSSL settings.');
		}
	},

	/**
	 * Uses the public key with the RSA library to encrypt the password.
	 *
	 * @param publicKey
	 */
	encryptPasswordAndSubmitForm: function(publicKey) {
		var form, rsa, inputField;

		rsa = new RSAKey();
		rsa.setPublic(publicKey.publicKeyModulus, publicKey.exponent);
		var encryptedPassword = rsa.encrypt(TYPO3RsaBackendLogin.userPasswordField.value);

		// Reset user password field to prevent it from being submitted
		TYPO3RsaBackendLogin.userPasswordField.value = '';
		TYPO3RsaBackendLogin.typo3PasswordField.value = 'rsa:' + hex2b64(encryptedPassword);

		// Create a hidden input field to fake pressing the submit button
		inputField = TYPO3RsaBackendLogin.getHiddenField('commandLI', 'Submit');
		form = $('typo3-login-form');
		form.appendChild(inputField);

		// Submit the form
		form.submit();
	},

	/**
	 * Creates a new hidden field DOM element
	 *
	 * @param name Name attribute of the field
	 * @param value Value attribute of the field
	 * @returns {HTMLElement}
	 */
	getHiddenField: function(name, value) {
		var input = document.createElement("input");
		input.setAttribute("type", "hidden");
		input.setAttribute("name", name);
		input.setAttribute("value", value);
		return input;
	}
};

Ext.onReady(TYPO3RsaBackendLogin.initialize, TYPO3RsaBackendLogin);
