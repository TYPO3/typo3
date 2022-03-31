.. include:: /Includes.rst.txt

================================================================================
Deprecation: #84274 - Protected methods and properties in LoginController
================================================================================

See :issue:`84274`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\LoginController`.

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`main()`
* :php:`makeInterfaceSelectorBox()`


Impact
======

Calling above method on an instance of
:php:`LoginController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find all usages, but may also find some false positives.  In general all extensions
that set properties or call methods except :php:`formAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.


.. index:: Backend, PHP-API, PartiallyScanned
