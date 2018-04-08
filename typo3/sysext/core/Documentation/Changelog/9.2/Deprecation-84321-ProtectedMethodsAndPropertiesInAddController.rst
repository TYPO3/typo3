.. include:: ../../Includes.txt

================================================================================
Deprecation: #84321 - Protected methods and properties in AddController
================================================================================

See :issue:`84321`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`$content`
* :php:`$processDataFlag`
* [not scanned] :php:`$pid`
* [not scanned] :php:`$table`
* [not scanned] :php:`$id`
* :php:`$P`
* :php:`$returnEditConf`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`init()`
* [not scanned] :php:`main()`

Due to refactoring the :php:`init()` method does not perform a redirect anymore in case no ``pid``
was set by GET params. This redirect has been moved and will be performed for legacy code by the
deprecated :php:`main()` method now.

Additionally :php:`$GLOBALS['SOBE']` is not set by the :php:`AddController` constructor anymore.


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`AddController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`AddController`. In general all extensions
that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.


.. index:: Backend, PHP-API, PartiallyScanned
