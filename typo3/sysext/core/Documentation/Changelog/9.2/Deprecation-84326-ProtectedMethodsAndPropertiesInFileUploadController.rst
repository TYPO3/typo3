.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #84326 - Protected methods and properties in FileUploadController
==============================================================================

See :issue:`84326`

Description
===========

This file is about third party usage of :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* :php:`title`
* :php:`target`
* :php:`returnUrl`
* [not scanned] :php:`content`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`main()`
* :php:`renderUploadForm()`

Additionally :php:`$GLOBALS['SOBE']` is not set by the :php:`FileUploadController` constructor anymore.


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`FileUploadController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`FileUploadController`.

In general all extensions that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

.. index:: Backend, PHP-API, PartiallyScanned
