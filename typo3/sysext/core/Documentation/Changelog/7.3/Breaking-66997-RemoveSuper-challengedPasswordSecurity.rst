
.. include:: ../../Includes.txt

=============================================================
Breaking: #66997 - Remove super-/challenged password security
=============================================================

See :issue:`66997`

Description
===========

TYPO3 CMS supports four possibilities how passwords can be sent from the browser to the server:

- "normal": Plain text
- "challenged": md5 hashed
- "superchallenged": md5 hashed
- "rsa": asymmetric encryption

Since TYPO3 CMS 6.2 the password transmission is protected by the rsaauth-extension by default ("rsa"),
which renders the old protection mechanisms "superchallenged" and "challenged" useless.

If the Backend login is accessed via HTTPS protocol, the "rsa" protection is redundant and can be disabled in general.

The super-/challenged options are removed, as "rsa" and "normal" are sufficient.
If rsaauth was not installed the default has been "superchallenged". The new default is "normal" now.


Impact
======

If an installation has rsaauth disabled, the password transfer is now **Plain Text**.

Any code relying on or checking for the "superchallenged" or "challenged" option
of `[BE][loginSecurityLevel]` or `[FE][loginSecurityLevel]`, will not work as expected.


Affected Installations
======================

Any installation having set `[BE][loginSecurityLevel]` or `[FE][loginSecurityLevel]` to an empty string or to
either of "superchallenged" or "challenged".


Migration
=========

Make sure you access the Backend via HTTPS or install the rsaauth system extension.

Also refer to the `TYPO3 Security Guide`_

.. _TYPO3 Security Guide: https://docs.typo3.org/typo3cms/SecurityGuide/GuidelinesAdministrators/EncryptedCommunication/Index.html
