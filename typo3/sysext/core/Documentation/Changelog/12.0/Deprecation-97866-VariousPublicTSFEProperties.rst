.. include:: /Includes.rst.txt

.. _deprecation-97866-1657185866:

====================================================
Deprecation: #97866 - Various public TSFE properties
====================================================

See :issue:`97866`

Description
===========

The following properties within TypoScriptFrontendController have been deprecated:

* :php:`spamProtectEmailAddresses`
* :php:`intTarget`
* :php:`extTarget`
* :php:`fileTarget`
* :php:`baseUrl`

All of these properties can be accessed through TypoScript's config array.

Impact
======

Accessing these properties via TypoScript `getData` or via PHP will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
======================

TYPO3 installations with TypoScript options such as :typoscript:`.data = TSFE:fileTarget` or
TYPO3 installations with third-party extensions accessing the properties via PHP.

Migration
=========

Migrate the access to these properties to use the config property:

In TypoScript you can access the TypoScript properties directly via
:typoscript:`.data = TSFE:config|config|fileTarget` and in PHP code via
:php:`$GLOBALS['TSFE']->config['config']['fileTarget']`.

.. index:: Frontend, TypoScript, PartiallyScanned, ext:frontend
