.. include:: ../../Includes.txt

================================================================================
Deprecation: #84195 - Protected methods and properties in EditDocumentController
================================================================================

See :issue:`84195`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\EditDocumentController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* :php:`$editconf`
* :php:`$defVals`
* :php:`$overrideVals`
* :php:`$columnsOnly`
* :php:`$returnUrl`
* :php:`$closeDoc`
* :php:`$doSave`
* :php:`$returnEditConf`
* [not scanned] :php:`$uc`
* :php:`$retUrl`
* :php:`$R_URL_parts`
* :php:`$R_URL_getvars`
* :php:`$storeArray`
* :php:`$storeUrl`
* :php:`$storeUrlMd5`
* :php:`$docDat`
* :php:`$docHandler`
* [not scanned] :php:`$cmd`
* [not scanned] :php:`$mirror`
* :php:`$cacheCmd`
* :php:`$redirect`
* :php:`$returnNewPageId`
* :php:`$popViewId`
* :php:`$popViewId_addParams`
* :php:`$viewUrl`
* :php:`$recTitle`
* :php:`$noView`
* :php:`$MCONF`
* [not scanned] :php:`$doc`
* :php:`$perms_clause`
* [not scanned] :php:`$template`
* :php:`$content`
* :php:`$R_URI`
* :php:`$pageinfo`
* :php:`$storeTitle`
* :php:`$firstEl`
* :php:`$errorC`
* :php:`$newC`
* :php:`$viewId`
* :php:`$viewId_addParams`
* :php:`$modTSconfig`
* :php:`$dontStoreDocumentRef`

Some properties are set to :php:`@internal` and may vanish or be set to protected in v10 without further notice:

* [not scanned] :php:`$data`
* :php:`$elementsData`

All methods not used as entry points by :php:`TYPO3\CMS\Backend\Http\RouteDispatcher` will be
removed or set to protected in v10 and throw deprecation warnings if used from a third party:

* :php:`preInit()`
* :php:`doProcessData()`
* :php:`processData()`
* [not scanned] :php:`init()`
* [note scanned] :php:`main()`
* :php:`makeEditForm()`
* :php:`compileForm()`
* :php:`shortCutLink()`
* :php:`openInNewWindowLink()`
* :php:`languageSwitch()`
* :php:`localizationRedirect()`
* :php:`getLanguages()`
* :php:`fixWSversioningInEditConf()`
* :php:`getRecordForEdit()`
* :php:`compileStoreDat()`
* :php:`getNewIconMode()`
* :php:`closeDocument()`
* :php:`setDocument()`

Two slots retrieve a parent object that will throw deprecation warnings if properties are read or
methods are called. They receive a :php:`ServerRequestInterface $request` argument as second
argument instead:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::preInitAfter`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::preInit`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`EditDocumentController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will find most usages, but may also find some false positives. The most
common property and method names like :php:`$data` are not registered and will not be found
if an extension uses that on an instance of :php:`EditDocumenController`. In general all extensions
that set properties or call methods except :php:`mainAction()` are affected.

Installations may alse be affected, if the two signals
:php:`TYPO3\CMS\Backend\Controller\EditDocumentController::preInitAfter` and
:php:`TYPO3\CMS\Backend\Controller\EditDocumentController::InitAfter`
are used and the slot write to or reads from first argument "parent object".


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.
Registered slots for the two signals :php:`preInitAfter` and :php:`initAfter` should read
(not write!) from new second argument :php:`$request` instead.
Slots that currently write to "parent object" should instead be turned into a PSR-15 middleware
to manipulate :php:`$request` before :php:`EditDocumentController` is called.


.. index:: Backend, PHP-API, PartiallyScanned