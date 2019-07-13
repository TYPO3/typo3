.. include:: ../../Includes.txt

======================================================
Deprecation: #85031 - Protected ImportExportController
======================================================

See :issue:`85031`

Description
===========

The following properties of class :php:`TYPO3\CMS\Impexp\Controller\ImportExportController` changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`pageinfo`
* [not scanned] :php:`id`
* [not scanned] :php:`perms_clause`
* [not scanned] :php:`extObj`
* [not scanned] :php:`doc`
* [not scanned] :php:`content`
* [not scanned] :php:`extClassConf`
* [not scanned] :php:`modMenu_setDefaultList`
* [not scanned] :php:`modMenu_dontValidateList`
* [not scanned] :php:`modMenu_type`
* [not scanned] :php:`modTSconfig`
* [not scanned] :php:`MOD_SETTINGS`
* [not scanned] :php:`MOD_MENU`
* [not scanned] :php:`CMD`
* [not scanned] :php:`MCONF`

The following methods of class :php:`TYPO3\CMS\Impexp\Controller\ImportExportController` changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`init()`
* [not scanned] :php:`main()`
* [not scanned] :php:`exportData()`
* :php:`addRecordsForPid()`
* :php:`exec_listQueryPid()`
* :php:`makeConfigurationForm()`
* :php:`makeAdvancedOptionsForm()`
* :php:`makeSaveForm()`
* [not scanned] :php:`importData()`
* [not scanned] :php:`checkUpload()`
* :php:`getTableSelectOptions()`
* :php:`filterPageIds()`
* [not scanned] :php:`getExtObjContent()`
* [not scanned] :php:`extObjContent()`
* [not scanned] :php:`extObjHeader()`
* [not scanned] :php:`checkSubExtObj()`
* [not scanned] :php:`checkExtObj()`
* [not scanned] :php:`getExternalItemConfig()`
* [not scanned] :php:`handleExternalFunctionValue()`
* [not scanned] :php:`mergeExternalItems()`
* [not scanned] :php:`menuConfig()`

Additionally, the assignment of an object instance of class :php:`ImportExportController` as
:php:`GLOBALS['SOBE']` has been marked as deprecated and will not be set anymore in TYPO3 v10.

Furthermore, class :php:`ImportExportController` does not inherit class :php:`BaseScriptClass` anymore.


Impact
======

Calling one of the above methods or accessing above properties triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

It is relatively unlikely instances are affected by this change since it is rather uncommon
to extend the import / export extension. The extension scanner will find some of the usages.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, PartiallyScanned, ext:impexp
