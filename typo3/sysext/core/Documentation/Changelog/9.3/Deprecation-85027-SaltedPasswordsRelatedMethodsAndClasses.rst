.. include:: ../../Includes.txt

==============================================================
Deprecation: #85027 - SaltedPasswordsUtility::isUsageEnabled()
==============================================================

See :issue:`85027`

Description
===========

The following method of the saltedpasswords extension has been marked as deprecated:

* :php:`TYPO3\CMS\saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled()`, it always returns TRUE.


Impact
======

Relying on clear-text password storage has been dropped, passwords are always stored as salted password hashes.


Affected Installations
======================

Instances that use third party authentication mechanisms may be affected by the change.
The extension scanner will find usages.


Migration
=========

Use the authentication services documented in the core API to not rely on clear-text password storage and the method mentioned above.

.. index:: Database, Frontend, LocalConfiguration, PHP-API, FullyScanned, ext:saltedpasswords