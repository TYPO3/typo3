.. include:: ../../Includes.txt

==================================================================
Deprecation: #84407 - AJAX request methods in RsaEncryptionEncoder
==================================================================

See :issue:`84407`

Description
===========

All methods related to AJAX requests in :php:`\TYPO3\CMS\Rsaauth\RsaEncryptionEncoder` have been
deprecated:

* :php:`getRsaPublicKey()`
* :php:`getRsaPublicKeyAjaxHandler()`

The ``rsa_publickey`` AJAX route has been adapted to use the
:php:`\TYPO3\CMS\Rsaauth\Controller\RsaPublicKeyGenerationController` which was already used for
RSA key retrieval via eID in the frontend.


Impact
======

Calling one of the above methods on an instance of :php:`RsaEncryptionEncoder` will throw a
deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

All extensions that call the deprecated methods are affected.


Migration
=========

Extensions should not use the deprecated methods but directly request a key pair via the RSA
backend API.

.. index:: Backend, Frontend, PHP-API, FullyScanned
