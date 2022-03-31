.. include:: /Includes.rst.txt

================================================================================
Deprecation: #84338 - Protected methods and properties in TableController
================================================================================

See :issue:`84388`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`$content`
* :php:`$inputStyle`
* :php:`$xmlStorage`
* :php:`$columnsOnly`
* :php:`$numNewRows`
* :php:`$colsFieldsName`
* [not scanned] :php:`$P`
* :php:`$TABLECFG`
* :php:`$tableParsing_quote`
* :php:`$tableParsing_delimiter`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* [note scanned] :php:`main()`
* :php:`tableWizard()`
* :php:`getConfigCode()`
* :php:`getTableHTML()`
* :php:`changeFunc()`
* :php:`cfgArray2CfgString()`
* :php:`cfgString2CfgArray()`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`TableController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$content` are not registered and will not be found
if an extension uses that on an instance of :php:`TableController`. In general all extensions
that set properties or call methods except :php:`mainAction()` are affected.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.


.. index:: Backend, PHP-API, PartiallyScanned
