.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #84324 - Use ServerRequestInterface in File/FileController
=======================================================================

See :issue:`84324`


Description
===========

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [not scanned] :php:`main()`
* :php: `initClipboard()`
* :php: `finish()`

Impact
======

Calling one of the above methods on an instance of
:php:`FileController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. In general all extensions
that call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.


.. index:: Backend, PHP-API, PartiallyScanned
