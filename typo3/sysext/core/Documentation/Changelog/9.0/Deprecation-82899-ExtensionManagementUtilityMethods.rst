.. include:: ../../Includes.txt

========================================================
Deprecation: #82899 - ExtensionManagementUtility methods
========================================================

See :issue:`82899`

Description
===========

The following methods have been marked as deprecated in :php:`ExtensionManagementUtility`

* :php:`siteRelPath()`
* :php:`getExtensionKeyByPrefix()`
* :php:`removeCacheFiles()`

Additionally the second method parameter of :php:`ExtensionManagementUtility::isLoaded()` to
throw a exception when an extension is not loaded, has been marked as deprecated, and should not
be used anymore.


Impact
======

Calling any of the methods or :php:`isLoaded()` with a second argument set explictly will trigger
a deprecation message.


Affected Installations
======================

Any TYPO3 installation with an extension calling any of the methods above.


Migration
=========

Use :php:`PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($extensionKey))`
instead of :php:`ExtensionManagementUtility::siteRelPath()`.

Instead of calling :php:`getExtensionKeyByPrefix()` use the extension key directly.

Use CacheManager API directly instead of calling :php:`removeCacheFiles()`.

.. index:: PHP-API, FullyScanned