.. include:: /Includes.rst.txt

===========================================================
Deprecation: #85113 - Legacy Backend Module Routing methods
===========================================================

See :issue:`85113`

Description
===========

In TYPO3 v9, Backend routing was unified to be handled via the "route" query string parameter. Backend modules
are now automatically registered to be a backend route.

The following methods are thus deprecated in favor of using the method above.

* :php:`BackendUtility::getModuleUrl()`
* :php:`UriBuilder->buildUriFromModule()`


Impact
======

Calling one of the deprecated methods above will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a custom backend-related extension using one of the methods directly in a PHP context.


Migration
=========

Use :php:`UriBuilder->buildUriFromRoute($moduleIdentifier)` instead.

For example ::

   BackendUtility::getModuleUrl('record_edit', $uriParameters);

becomes ::

   $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
   $uriBuilder->buildUriFromRoute('record_edit', $uriParameters);

.. index:: Backend, PHP-API, FullyScanned
