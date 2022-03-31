.. include:: /Includes.rst.txt

=====================================================================================================
Deprecation: #84284 - Protected methods and properties in ContentElement/ElementInformationController
=====================================================================================================

See :issue:`84284`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`table`
* [not scanned] :php:`uid`
* :php:`access`
* [not scanned] :php:`type`
* :php:`pageInfo`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`init()`
* [not scanned] :php:`main()`
* :php:`getLabelForTableColumn()`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`ElementInformationController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

In general all extensions
that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

Since some of the deprecated methods and properties have quite common names and would produce false positives, their
usage is not detected by the extension scanner.

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
