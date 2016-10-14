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
 * Module: TYPO3/CMS/Rsaauth/RsaEncryptionModule
 * Object that handles RSA encryption and submission of the form
 */
define(['jquery', './RsaLibrary'], function($) {
	'use strict';

	/**
	 * @type {{$currentForm: null, fetchedRsaKey: boolean, initialize: Function, registerForm: Function, handleFormSubmitRequest: Function, handlePublicKeyResponse: Function}}
	 * @exports TYPO3/CMS/Rsaauth/RsaEncryptionModule
	 */
	var RsaEncryption = {

		/**
		 * Remember the form which was submitted
		 */
		$currentForm: null,

		/**
		 * Remember if we fetched the RSA key already
		 */
		fetchedRsaKey: false,

		/**
		 * Replace event handler of submit button for given form
		 *
		 * @param {Form} form Form DOM object
		 */
		registerForm: function(form) {
			var $form = $(form);

			if ($form.data('rsaRegistered')) {
				// Do not register form twice
				return;
			}
			// Mark form as registered
			$form.data('rsaRegistered', true);

			// Store the original submit handler that is executed later
			$form.data('original-onsubmit', $form.attr('onsubmit'));

			// Remove the original submit handler and register RsaEncryption.handleFormSubmitRequest instead
			$form.removeAttr('onsubmit').on('submit', RsaEncryption.handleFormSubmitRequest);

			// Bind submit event first (this is a dirty hack with jquery internals, but there is no way around that)
			var handlers = $._data(form, 'events').submit;
			var handler = handlers.pop();
			handlers.unshift(handler);
		},

		/**
		 * Fetches a new public key by Ajax and encrypts the password for transmission
		 *
		 * @param {Event} event
		 */
		handleFormSubmitRequest: function(event) {
			if (!RsaEncryption.fetchedRsaKey) {
				event.stopImmediatePropagation();

				RsaEncryption.fetchedRsaKey = true;
				RsaEncryption.$currentForm = $(this);

				$.ajax({
					url: TYPO3.settings.ajaxUrls['rsa_publickey'],
					data: {'skipSessionUpdate': 1},
					success: RsaEncryption.handlePublicKeyResponse
				});

				return false;
			} else {
				// we come here again when the submit is triggered below
				// reset the variable to fetch a new key for next attempt
				RsaEncryption.fetchedRsaKey = false;
			}
		},

		/**
		 * Parses the Json response and triggers submission of the form
		 *
		 * @param {Object} response Ajax response object
		 */
		handlePublicKeyResponse: function(response) {
			var publicKey = response.split(':');
			if (!publicKey[0] || !publicKey[1]) {
				alert('No public key could be generated. Please inform your TYPO3 administrator to check the OpenSSL settings.');
				return;
			}

			var rsa = new RSAKey();
			rsa.setPublic(publicKey[0], publicKey[1]);
			RsaEncryption.$currentForm.find(':input[data-rsa-encryption]').each(function() {
				var $this = $(this);
				var encryptedValue = rsa.encrypt($this.val());
				var dataAttribute = $this.data('rsa-encryption');
				var rsaValue = 'rsa:' + hex2b64(encryptedValue);

				if (!dataAttribute) {
					$this.val(rsaValue);
				} else {
					var $typo3Field = $('#' + dataAttribute);
					$typo3Field.val(rsaValue);
					// Reset user password field to prevent it from being submitted
					$this.val('');
				}
			});

			// Try to fetch the field which submitted the form
			var $currentField = RsaEncryption.$currentForm.find('input[type=submit]:focus,input[type=image]:focus');
			if ($currentField.length === 1) {
				$currentField.trigger('click');
			} else {
				// Create a hidden input field to fake pressing the submit button
				RsaEncryption.$currentForm.append('<input type="hidden" name="commandLI" value="Submit">');

				// Restore the original submit handler
				var originalOnSubmit = RsaEncryption.$currentForm.data('original-onsubmit');
				if (typeof originalOnSubmit === 'string' && originalOnSubmit.length > 0) {
					RsaEncryption.$currentForm.attr('onsubmit', originalOnSubmit);
					RsaEncryption.$currentForm.removeData('original-onsubmit');
				}

				// Submit the form
				RsaEncryption.$currentForm.trigger('submit');
			}
		}
	};

	/**
	 * Search for forms and add event handler
	 */
	RsaEncryption.initialize = function() {
		$(':input[data-rsa-encryption]').closest('form').each(function() {
			RsaEncryption.registerForm(this);
		});
		rng_seed_time();
	};

	$(RsaEncryption.initialize);

	return RsaEncryption;
});
