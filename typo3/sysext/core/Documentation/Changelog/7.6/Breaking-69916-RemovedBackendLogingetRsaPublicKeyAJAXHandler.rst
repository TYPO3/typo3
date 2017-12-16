
.. include:: ../../Includes.txt

=====================================================================
Breaking: #69916 - Removed BackendLogin::getRsaPublicKey AJAX handler
=====================================================================

See :issue:`69916`

Description
===========

The deprecated AJAX handler `BackendLogin::getRsaPublicKey` has been removed in favor of `rsa_publickey`. As
`getRsaPublicKey` was the only method in this class, the file
:file:`typo3/sysext/rsaauth/Classes/Backend/AjaxLoginHandler.php` has been removed without substitution.


Impact
======

Calling the removed handler will result in an error.


Affected Installations
======================

All 3rd party extensions using the removed handler are affected.


Migration
=========

Use the AJAX handler `rsa_publickey` instead of `BackendLogin::getRsaPublicKey`.
