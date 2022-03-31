.. include:: /Includes.rst.txt

=======================================================
Deprecation: #85285 - Deprecated path related constants
=======================================================

See :issue:`85285`

Description
===========

The following constants have been marked as deprecated and should not be used any longer:

* :php:`PATH_thisScript`
  Use :php:`Environment::getCurrentScript()` instead

* :php:`PATH_site`
  Use :php:`Environment::getPublicPath() . '/'` instead

* :php:`PATH_typo3`
  Use :php:`Environment::getPublicPath() . '/typo3/'` instead

* :php:`PATH_typo3conf`
  Use :php:`Environment::getPublicPath() . '/typo3conf'` instead

* :php:`TYPO3_OS`
  Use :php:`Environment::isWindows()` and :php:`Environment::isUnix()` instead


Impact
======

The above constants are still defined in TYPO3 v9, but their definition will be
dropped in v10.


Affected Installations
======================

Constants can not be deprecated as such and using them does not trigger a PHP :php:`E_USER_DEPRECATED` error.
Extensions in v9 should not use them any longer but switch to the alternatives already.

The extension scanner will find usages of the above constants and marks them as strong
matches.



Migration
=========

Usages of the above constants should be switched to the Environment class methods instead.


.. index:: PHP-API, FullyScanned
