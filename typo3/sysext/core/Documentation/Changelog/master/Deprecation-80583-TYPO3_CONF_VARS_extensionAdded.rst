.. include:: ../../Includes.txt

====================================================
Deprecation: #80583 - TYPO3_CONF_VARS_extensionAdded
====================================================

See :issue:`80583`

Description
===========

The global array :code:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']` has been deprecated along with the method :code:`ExtensionManagementUtility::appendToTypoConfVars()`


Impact
======

Using method :code:`appendToTypoConfVars()` throws a deprecation warning and accessing
:code:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']` will stop working with core version 9.


Affected Installations
======================

Extensions using :code:`$GLOBALS['TYPO3_CONF_VARS_extensionAdded']` or method :code:`appendToTypoConfVars()`


Migration
=========

Access :code:`$GLOBALS['TYPO3_CONF_VARS']` directly.

.. index:: LocalConfiguration, PHP-API