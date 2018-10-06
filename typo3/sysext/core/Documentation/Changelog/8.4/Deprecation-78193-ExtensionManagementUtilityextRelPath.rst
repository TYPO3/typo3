.. include:: ../../Includes.txt

==============================================================
Deprecation: #78193 - ExtensionManagementUtility::extRelPath()
==============================================================

See :issue:`78193`

Description
===========

The method :php:`ExtensionManagementUtility::extRelPath()` for resolving paths relative to the current script has been marked as deprecated.


Impact
======

Calling :php:`ExtensionManagementUtility::extRelPath()` will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance with extensions or third-party scripts resolving paths with the method above.


Migration
=========

Use alternatives for resolving paths. There are the following methods available:

* :php:`ExtensionManagementUtility::extPath()` - to resolve the full path of an extension
* :php:`ExtensionManagementUtility::siteRelPath()` - to resolve the location of an extension relative to `PATH_site`
* :php:`GeneralUtility::getFileAbsFileName()` - to resolve a file/path prefixed with `EXT:myext`
* :php:`PathUtility::getAbsoluteWebPath()` - used for output a file location (previously resolved with :php:`GeneralUtility::getFileAbsFileName()`) that is absolutely prefixed for the web folder

.. index:: PHP-API
