.. include:: ../../Includes.txt

====================================================
Deprecation: #80583 - TYPO3_CONF_VARS_extensionAdded
====================================================

See :issue:`80583`

Description
===========

The global array :php:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']` has been deprecated along with the method
:php:`ExtensionManagementUtility::appendToTypoConfVars()`


Impact
======

Using method :php:`appendToTypoConfVars()` throws a deprecation warning and accessing
:php:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']` will stop working with core version 9.


Affected Installations
======================

Extensions using :php:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']` or method :php:`appendToTypoConfVars()`


Migration
=========

Access :php:`$GLOBALS['TYPO3_CONF_VARS']` directly.

.. index:: LocalConfiguration, PHP-API
