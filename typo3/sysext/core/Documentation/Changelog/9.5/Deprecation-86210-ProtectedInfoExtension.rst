.. include:: /Includes.rst.txt

==============================================
Deprecation: #86210 - Protected info extension
==============================================

See :issue:`86210`

Description
===========

To allow refactoring of the Web -> Info module in TYPO3 v10, the involved controller classes
have been disentangled and better encapsulated:

* Class :php:`TYPO3\CMS\Info\Controller\InfoModuleController`
  does not extend :php:`TYPO3\CMS\Backend\Module\BaseScriptClass` anymore
* Class :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore
* Class :php:`TYPO3\CMS\Info\Controller\PageInformationController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore
* Class :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore
* Class :php:`TYPO3\CMS\Info\Controller\TranslationStatusController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore

Setting an instance of class :php:`InfoModuleController` as global object :php:`$GLOBALS['SOBE']`
has been marked as deprecated and will be removed in TYPO3 v10.

The following class properties have been set from public to protected and will not be accessible in TYPO3 v10 anymore:

* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->perms_clause`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modTSconfig`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modMenu_setDefaultList`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modMenu_dontValidateList`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modMenu_type`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->extObj`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->content`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->pObj`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->id`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->CMD`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->doc`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->MCONF`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->pObj`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->doc`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->function_key`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->extClassConf`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->localLangFile`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->extObj`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->pObj`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->function_key`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->localLangFile`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->extObj`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->pObj`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->function_key`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->localLangFile`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->extObj`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->pObj`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->function_key`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->localLangFile`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->extObj`

The following class methods have been set from public to protected and will not be accessible in TYPO3 v10 anymore:

* :php:`TYPO3CMSInfoControllerInfoModuleController->main()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->init()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->getModuleTemplate()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->menuConfig()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->handleExternalFunctionValue()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->mergeExternalItems()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->getExternalItemConfig()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->extObjContent()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->getExtObjContent()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->checkExtObj()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->extObjHeader()`
* :php:`TYPO3CMSInfoControllerInfoModuleController->checkSubExtObj()`
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->extObjContent()`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->modMenu()`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->extObjContent()`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->modMenu()`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->extObjContent()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->getContentElementCount()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->getLangStatus()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->renderL10nTable()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->modMenu()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->extObjContent()`


Impact
======

If an extension accesses one of the above protected properties or calls one of the above protected methods,
a :php:`E_USER_DEPRECATED` error will be triggered.


Affected Installations
======================

Various extensions extend the Web -> Info module. Those typically call
:php:`ExtensionManagementUtility::insertModuleFunction('web_info', ...)` in :file:`ext_tables.php` or
:file:`ext_localconf.php`. Those instances may need adaptions.


Migration
=========

If extending the `info` module with an own extension, the extension should be adapted to not call
the above methods or properties any longer. Most usages can be easily adapted, for instance
to retrieve the current page id, use :php:`GeneralUtility::_GP('id')` instead of :php:`$this->pObj->id`.

.. index:: Backend, PHP-API, NotScanned, ext:info
