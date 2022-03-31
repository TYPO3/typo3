.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #85793 - Several constants from SystemEnvironmentBuilder
=====================================================================

See :issue:`85793`

Description
===========

The following constants have been deprecated and should not be used any longer:

* :php:`NUL` (Use :php:`"\0"` instead)

* :php:`TAB` (Use :php:`"\t"` instead)

* :php:`SUB` (Use :php:`chr(26)` instead)

* :php:`TYPO3_URL_MAILINGLISTS`

* :php:`TYPO3_URL_DOCUMENTATION`

* :php:`TYPO3_URL_DOCUMENTATION_TSREF`

* :php:`TYPO3_URL_DOCUMENTATION_TSCONFIG`

* :php:`TYPO3_URL_CONSULTANCY`

* :php:`TYPO3_URL_CONTRIBUTE`

* :php:`TYPO3_URL_SECURITY`

* :php:`TYPO3_URL_DOWNLOAD`

* :php:`TYPO3_URL_SYSTEMREQUIREMENTS`


Impact
======

The above constants are still defined in TYPO3 v9, but their definition will be
dropped in TYPO3 v10.


Affected Installations
======================

Constants can not be deprecated as such and using them does not trigger a PHP :php:`E_USER_DEPRECATED` error.
Extensions in TYPO3 v9 should not use them any longer but switch to the alternatives already.

The extension scanner will find usages of the above constants and marks them as strong
matches.


Migration
=========

Use one of the :php:`chr(*)` variants or replace the constant usage with the URL in your own code.

.. index:: PHP-API, FullyScanned
