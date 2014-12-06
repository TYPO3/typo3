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
 * Object that handles RSA encryption and submission of the form
 */
define('TYPO3/CMS/Rsaauth/BackendLoginFormRsaEncryption', ['jquery', 'TYPO3/CMS/Backend/Login'], function($, BackendLogin) {

	var RsaBackendLogin = {

		/**
		 * Field in which users enter their password
		 */
		userPasswordField: false,

		/**
		 * Field that is used by TYPO3 to evaluate the password during login process
		 */
		typo3PasswordField: false,

		/**
		 * Replace event handler of submit button
		 */
		initialize: function() {
			this.userPasswordField = BackendLogin.options.passwordField;
			this.typo3PasswordField = BackendLogin.options.useridentField;

			$(document).off('click', BackendLogin.options.submitButton, BackendLogin.showLoginProcess);
			$(document).on('click', BackendLogin.options.submitButton, this.handleFormSubmitRequest);
			return this;
		},

		/**
		 * Fetches a new public key by Ajax and encrypts the password for transmission
		 *
		 * @param event
		 */
		handleFormSubmitRequest: function(event) {
			event.preventDefault();

			BackendLogin.showLoginProcess();

			$.ajax({
				url: TYPO3.settings.ajaxUrls['BackendLogin::getRsaPublicKey'],
				data: {'skipSessionUpdate': 1},
				success: RsaBackendLogin.handlePublicKeyResponse,
				dataType: 'json'
			});
		},

		/**
		 * Parses the Json response and triggers submission of the form
		 *
		 * @param publicKey Ajax response object
		 */
		handlePublicKeyResponse: function(publicKey) {
			if (publicKey.publicKeyModulus && publicKey.exponent) {
				RsaBackendLogin.encryptPasswordAndSubmitForm(publicKey);
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
			var rsa = new RSAKey();
			rsa.setPublic(publicKey.publicKeyModulus, publicKey.exponent);
			var encryptedPassword = rsa.encrypt($(RsaBackendLogin.userPasswordField).val());

			// Reset user password field to prevent it from being submitted
			$(RsaBackendLogin.userPasswordField).val('');
			$(RsaBackendLogin.typo3PasswordField).val('rsa:' + hex2b64(encryptedPassword));

			var $formElement = $('form:first');

			// Create a hidden input field to fake pressing the submit button
			$formElement.append('<input type="hidden" name="commandLI" value="Submit">');

			// Submit the form
			$formElement.trigger('submit');
		}
	};
	RsaBackendLogin.initialize();
});
