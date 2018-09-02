.. include:: ../../Includes.txt

===============================================
Deprecation: #85796 - Salted passwords cleanups
===============================================

See :issue:`85796`

Description
===========

These methods have been marked as deprecated:

:php:`TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance()`
   Use :php:`SaltFactory->get()` to retrieve a hash instance of for a given password hash.
   Use :php:`SaltFactory->getDefaultHashInstance()` to retrieve an instance of the configured default hash algorithm
   for a given context. See the method comments for usage details.

:php:`TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::determineSaltingHashingMethod()`
   Use :php:`SaltFactory->getDefaultHashInstance()` instead.

:php:`TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::setPreferredHashingMethod()`
   This method was only used for unit testing and has been marked as deprecated without substitution since
   object instances of :php:`SaltFactory` can  now be properly mocked.
   Use :php:`Prophecy` to do that in unit tests that have :php:`SaltFactory` as dependency.

:php:`TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility->getNumberOfBackendUsersWithInsecurePassword()`
   This internal method is unused and there is no new implementation to substitute it.


Impact
======

Calling one of the above methods will trigger a PHP :php:`E_USER_DEPRECATED` error and a fatal PHP error in TYPO3 v10.


Affected Installations
======================

Most instances are not affected by this change if they don't have custom authentication
services loaded that add magic with stored local password hashes, and if they don't use
the :php:`SaltFactory` in own extension which is a seldom use case.

The extension scanner will find usages in extensions.


Migration
=========

Use the new factory methods as outlined in the description section.

.. index:: PHP-API, FullyScanned, ext:saltedpasswords
