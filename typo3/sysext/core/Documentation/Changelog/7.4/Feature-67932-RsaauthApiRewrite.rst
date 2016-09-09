
.. include:: ../../Includes.txt

=================================
Feature: #67932 - New rsaauth API
=================================

See :issue:`67932`

Description
===========

The rsaauth API has been rewritten to be more generic and can now be used easily in more parts of the core as well as
in third party extensions.


Impact
======

Form fields (e.g. password fields) can be encrypted before transmission. This helps to improve the security of your and
your user's data.


Examples
========

Encode
------

Encoding is done automatically via a JavaScript function which gets a public key and encrypts the data.

1) Include JavaScript to parse form fields for encryption. You can either choose to include a RequireJS module or a
plain Javascript file.

.. code-block:: php

	$rsaEncryptionEncoder = GeneralUtility::makeInstance(\TYPO3\CMS\Rsaauth\RsaEncryptionEncoder::class);
	$rsaEncryptionEncoder->enableRsaEncryption(); // Adds plain JavaScript
	$rsaEncryptionEncoder->enableRsaEncryption(TRUE); // Adds RequireJS module

2) Activate encryption for your from fields with the data attribute `data-rsa-encryption`.

.. code-block:: html

	<input type="password" id="pass" name="pass" value="" data-rsa-encryption="" />

If you want the encrypted value to be stored in another field, you have to use the RequiredJS module and you can
pass the id of that form field as value to the data attribute.

.. code-block:: html

	<input type="password" id="t3-password" name="p_field" value="" data-rsa-encryption="t3-field-userident" />
	<input type="hidden" name="userident" id="t3-field-userident" />

Decode
------

To decode your data you can use the method `TYPO3\CMS\Rsaauth\RsaEncryptionDecoder::decrypt` which can
either handle a string or an array as parameter. Data that is handled by \TYPO3\CMS\Core\DataHandling\DataHandler will
be decoded automatically before processing.

Notice: A RSA public key can only be used once to decrypt data. If you encrypt multiple fields in your form
you have to pass an array to the decrypt function with all data you want to decrypt. The function parses the
values for a `rsa:` prefix so you can be sure that non-matching data will not be changed.

.. code-block:: php

	$rsaEncryptionDecoder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Rsaauth\RsaEncryptionDecoder::class);

	// Decrypt a single string
	$password = $loginData['uident'];
	$decryptedPassword = $rsaEncryptionDecoder->decrypt($password);

	// Decrypt an array
	if ($this->isRsaAvailable()) {
		$parameters['be_user_data'] = $this->getRsaEncryptionDecoder()->decrypt($parameters['be_user_data']);
	}
