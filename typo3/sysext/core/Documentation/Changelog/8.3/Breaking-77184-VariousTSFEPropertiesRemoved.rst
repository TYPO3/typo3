
.. include:: ../../Includes.txt

==================================================
Breaking: #77184 - Various TSFE properties removed
==================================================

See :issue:`77184`

Description
===========

The following public properties of the PHP class `TypoScriptFrontendController` have been removed.

- TYPO3_CONF_VARS
- defaultBodyTag
- clientInfo

Additionally, the first parameter of the `TypoScriptFrontendController` constructor has no effect anymore and can be set
to null.


Impact
======

Accessing or setting the properties will throw a PHP warning and have no effect anymore.


Affected Installations
======================

Any installation working with the public property in a third-party extension or instantiating the `TSFE` object itself.


Migration
=========

For any calls to `$TSFE->TYPO3_CONF_VARS` the global array `$GLOBALS['TYPO3_CONF_VARS']` should be used.

For the property `defaultBodyTag` the according TypoScript settings can be used to override the
body tag or the page title.

The information previously stored in the clientInfo property can be fetched via `GeneralUtility::clientInfo()`.

.. index:: PHP-API, Frontend
