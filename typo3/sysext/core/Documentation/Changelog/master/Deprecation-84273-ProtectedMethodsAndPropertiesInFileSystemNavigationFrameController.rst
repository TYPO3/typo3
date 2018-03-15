.. include:: ../../Includes.txt

=============================================================================================
Deprecation: #84273 - Protected methods and properties in FileSystemNavigationFrameController
=============================================================================================

See :issue:`84273`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController`.

A series of class properties have been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`$content`
* :php:`$foldertree`
* :php:`$currentSubScript`
* :php:`$cMR`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* :php:`initPage()`
* [not scanned] :php:`main()`
* [not scanned] :php:`init()`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`FileSystemNavigationFrameController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`FileSystemNavigationFrameController`. In general all extensions
that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.


.. index:: Backend, PHP-API, PartiallyScanned