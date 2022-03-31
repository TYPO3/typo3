.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #84341 - Protected methods and properties in NewRecordController
=============================================================================

See :issue:`84341`

Description
===========

This file is about third party usage of :php:`TYPO3\CMS\Backend\Controller\NewRecordController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`pageinfo`
* :php:`pidInfo`
* :php:`newPagesInto`
* :php:`newContentInto`
* :php:`newPagesAfter`
* :php:`web_list_modTSconfig`
* :php:`allowedNewTables`
* :php:`deniedNewTables`
* :php:`web_list_modTSconfig_pid`
* :php:`allowedNewTables_pid`
* :php:`deniedNewTables_pid`
* :php:`code`
* :php:`R_URI`
* [not scanned] :php:`id`
* :php:`returnUrl`
* :php:`pagesOnly`
* [not scanned] :php:`perms_clause`
* [not scanned] :php:`content`
* :php:`tRows`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`main()`
* :php:`pagesOnly()`
* :php:`regularNew()`
* :php:`sortNewRecordsByConfig()`
* :php:`linkWrap()`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`NewRecordController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`NewRecordController`.

In general all extensions that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

.. index:: Backend, PHP-API, PartiallyScanned
