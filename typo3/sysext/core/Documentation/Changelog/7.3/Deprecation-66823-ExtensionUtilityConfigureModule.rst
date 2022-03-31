
.. include:: /Includes.rst.txt

================================================================================
Deprecation: #66823 - Deprecate Extbase ExtensionUtility->configureModule method
================================================================================

See :issue:`66823`

Description
===========

The method `TYPO3\CMS\Extbase\Utility\ExtensionUtility->configureModule()` has been marked for deprecation, and will
be removed with TYPO3 CMS 8.


Impact
======

Calling `TYPO3\CMS\Extbase\Utility\ExtensionUtility->configureModule()` will throw a deprecation message.


Affected Installations
======================

Any installation with a third-party extension making use of `ExtensionUtility->configureModule()` directly
inside e.g. ext_tables.php.


Migration
=========

Use the 1:1 functionality in `TYPO3\CMS\Core\Utility\ExtensionManagementUtility->configureModule()` directly.


.. index:: PHP-API, Backend, ext:extbase
