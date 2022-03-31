.. include:: /Includes.rst.txt

================================================================================
Deprecation: #84337 - Protected methods and properties in ListController
================================================================================

See :issue:`84337`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\Wizard\ListController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`pid`
* [not scanned] :php:`P`
* [not scanned] :php:`table`
* [not scanned] :php:`id`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`main()`


Impact
======

Calling above method on an instance of
:php:`ListController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find all usages, but may also find some false positives.  In general all extensions
that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.


.. index:: Backend, PHP-API, PartiallyScanned
