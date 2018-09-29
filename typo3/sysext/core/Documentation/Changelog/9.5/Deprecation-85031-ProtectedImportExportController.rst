.. include:: ../../Includes.txt

======================================================
Deprecation: #85031 - Protected ImportExportController
======================================================

See :issue:`85031`

Description
===========

The following properties changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->pageinfo`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->id`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->perms_clause`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extObj`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->doc`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->content`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extClassConf`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modMenu_setDefaultList`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modMenu_dontValidateList`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modMenu_type`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modTSconfig`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->MOD_SETTINGS`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->MOD_MENU`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->CMD`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->MCONF`

The following methods changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->init()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->main()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->exportData()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->addRecordsForPid()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->exec_listQueryPid()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->makeConfigurationForm()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->makeAdvancedOptionsForm()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->makeSaveForm()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->importData()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->checkUpload()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->getTableSelectOptions()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->filterPageIds()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->getExtObjContent()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extObjContent()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extObjHeader()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->checkSubExtObj()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->checkExtObj()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->getExternalItemConfig()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->handleExternalFunctionValue()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->mergeExternalItems()`
* [not scanned] :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->menuConfig()`

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
