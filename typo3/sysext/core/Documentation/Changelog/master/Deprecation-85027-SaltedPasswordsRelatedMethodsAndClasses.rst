.. include:: ../../Includes.txt

==================================================================
Deprecation: #85027 - Salted passwords related methods and classes
==================================================================

See :issue:`85027`

Description
===========

The following methods of the saltedpasswords extension have been deprecated:

* :php:`TYPO3\CMS\saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled()`, it always returns TRUE


Impact
======

Relying on clear-text password storage has been dropped, passwords are always stored as salted password hashes.


Affected Installations
======================

Instances that use third party authentication mechanisms may be affected by the change. The extension scanner will find usages of the methods and classes mentioned above.


Migration
=========

Use the authentication services documented in the core API to not rely on clear-text password storage and the above methods or classes.

.. index:: Database, Frontend, LocalConfiguration, PHP-API, FullyScanned, ext:saltedpasswords