.. include:: /Includes.rst.txt

================================================
Deprecation: #91030 - Runtime-Activated Packages
================================================

See :issue:`91030`

Description
===========

TYPO3's global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']` has been marked as deprecated.

The option to register packages during runtime was introduced as
a work-around to dynamically modify the "extension list" when migrating from TYPO3 v4.5 to TYPO3 v6.x.

However, using this feature has certain limitations:

* Runtime-activated Extensions cannot add their DI configuration
* Runtime-activated Extensions make every (!) single TYPO3 request much slower just like back in 6.2.0 times

The main use case we know from people was to this functionality to enable e.g. extensions such as "devlog", "mask"/"mask_export" or "extensionbuilder" only on development systems.


Impact
======

Having a TYPO3 system using Runtime Activated Packages functionality
will trigger a PHP :php:`E_USER_DEPRECATED` error on every TYPO3 request.


Affected Installations
======================

TYPO3 installations having the affected option set in either :file:`typo3conf/LocalConfiguration.php` or :file:`typo3conf/AdditionalConfiguration.php`.


Migration
=========

It is recommended - if this functionality is needed - to use TYPO3
Console and Composer Mode (with require-dev) to achieve a similar behavior.

If it is critical to have such features, consider modifying the extension in question to deal with TYPO3's Context
feature to enable / disable functionality for Production environment.

.. index:: LocalConfiguration, FullyScanned, ext:core
