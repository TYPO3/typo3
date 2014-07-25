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

/**
 * Object that handles RSA encryption and submission of the FE login form
 */
TYPO3FrontendLoginFormRsaEncryption = function() {

	var rsaFrontendLogin = function(form, publicKeyEndpointUrl) {

		/**
		 * Submitted form element
		 */
		this.form = form;

		/**
		 * XMLHttpRequest
		 */
		this.xhr = null;

		/**
		 * Endpoint URL to fetch the public key for encryption
		 */
		this.publicKeyEndpointUrl = publicKeyEndpointUrl;

		/**
		 * Field in which users enter their password
		 */
		this.userPasswordField = form.pass;

		/**
		 * Fetches a new public key by Ajax and encrypts the password for transmission
		 */
		this.handleFormSubmitRequest = function() {
			var rsaFrontendLogin = this;
			this.ajaxCall(
				this.publicKeyEndpointUrl,
				function(response) {
					rsaFrontendLogin.handlePublicKeyResponse(response, rsaFrontendLogin);
				}
			);
		};

		/**
		 * Do Ajax call to fetch RSA public key
		 */
		this.ajaxCall = function(url, callback) {

			// abort previous request, only last request/generated key pair can be used
			if (this.xhr) {
				this.xhr.abort();
			}

			if (typeof XMLHttpRequest !== 'undefined') {
				this.xhr = new XMLHttpRequest();
			} else {
				var versions = [
					"MSXML2.XmlHttp.5.0",
					"MSXML2.XmlHttp.4.0",
					"MSXML2.XmlHttp.3.0",
					"MSXML2.XmlHttp.2.0",
					"Microsoft.XmlHttp"
				];
				for (var i = 0, len = versions.length; i < len; i++) {
					try {
						this.xhr = new ActiveXObject(versions[i]);
						break;
					} catch(e) {}
				}
			}

			this.xhr.onreadystatechange = function() {
				// only process requests that are ready and have a status (not aborted)
				if (this.readyState === 4 && this.status > 0) {
					callback(this);
				}
			};

			this.xhr.open('GET', url, true);
			this.xhr.send('');
		};

		/**
		 * Parses the response and triggers submission of the form
		 *
		 * @param response Ajax response object
		 * @param rsaFrontendLogin current processed object
		 */
		this.handlePublicKeyResponse = function(response, rsaFrontendLogin) {
			var publicKey = response.responseText.split(':');
			if (publicKey[0] && publicKey[1]) {
				rsaFrontendLogin.encryptPasswordAndSubmitForm(publicKey[0], publicKey[1]);
			} else {
				alert('No public key could be generated. Please inform your TYPO3 administrator to check the OpenSSL settings.');
			}
		};

		/**
		 * Uses the public key with the RSA library to encrypt the password.
		 *
		 * @param publicKeyModulus
		 * @param exponent
		 */
		this.encryptPasswordAndSubmitForm = function(publicKeyModulus, exponent) {
			var rsa, encryptedPassword;

			rsa = new RSAKey();
			rsa.setPublic(publicKeyModulus, exponent);
			encryptedPassword = rsa.encrypt(this.userPasswordField.value);

			// replace password value with encrypted password
			this.userPasswordField.value = 'rsa:' + hex2b64(encryptedPassword);

			// Submit the form again but now with encrypted pass
			document.createElement("form").submit.call(this.form);
		};
	};

	/**
	 * Encrypt password on submit
	 *
	 * @param form
	 * @param publicKeyEndpointUrl
	 * @return boolean
	 */
	this.submitForm = function(form, publicKeyEndpointUrl) {

		if (!form.rsaFrontendLogin) {
			form.rsaFrontendLogin = new rsaFrontendLogin(form, publicKeyEndpointUrl);
		}

		// if pass is not encrypted yet fetch public key and encrypt pass
		if (!form.pass.value.match(/^rsa:/) ) {
			form.rsaFrontendLogin.handleFormSubmitRequest();
			return false;

		// pass is encrypted so form can be submitted
		} else {
			return true;
		}
	};

	return this;
}();
