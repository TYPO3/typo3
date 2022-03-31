.. include:: /Includes.rst.txt

====================================================
Deprecation: #86207 - Protected tstemplate extension
====================================================

See :issue:`86207`

Description
===========

To allow refactoring of the Web -> Template module in TYPO3 v10, the involved controller classes
have been disentangled and better encapsulated:

* Class :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController`
  does not extend :php:`TYPO3\CMS\Backend\Module\BaseScriptClass` anymore
* Class :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore
* Class :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore
* Class :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore
* Class :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController`
  does not extend :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule` anymore

Setting an instance of class :php:`TypoScriptTemplateModuleController` as global object :php:`$GLOBALS['SOBE']`
has been marked as deprecated and will be removed in TYPO3 v10.

The following class properties have been set from public to protected and will not be accessible in TYPO3 v10 anymore:

* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->textExtensions`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->pageinfo`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->id`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->modTSconfig`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->content`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->extObj`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->access`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->perms_clause`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->extClassConf`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->edit`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->modMenu_type`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->MCONF`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->CMD`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->sObj`
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->pObj`
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->function_key`
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->extClassConf`
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->localLangFile`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->pObj`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->function_key`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->extClassConf`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->localLangFile`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->pObj`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->function_key`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->extClassConf`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->localLangFile`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tce_processed`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->pObj`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->function_key`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->extClassConf`
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->localLangFile`

The following class methods have been set from public to protected and will not be accessible in TYPO3 v10 anymore:

* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->getExternalItemConfig()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->init()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->clearCache()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->main()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->setInPageArray()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->menuConfig()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->mergeExternalItems()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->handleExternalFunctionValue()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->checkExtObj()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->extObjContent()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->getExtObjContent()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->checkSubExtObj()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateModuleController->extObjHeader()`
* :php:`TYPO3CMSTstemplateControllerTemplateAnalyzerModuleFunctionControllerinitialize_editor()`
* :php:`TYPO3CMSTstemplateControllerTemplateAnalyzerModuleFunctionControllermodMenu()`
* :php:`TYPO3CMSTstemplateControllerTemplateAnalyzerModuleFunctionControllerhandleExternalFunctionValue()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateConstantEditorModuleFunctionControllerinitialize_editor()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateConstantEditorModuleFunctionControllerhandleExternalFunctionValue()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateInformationModuleFunctionControllerinitialize_editor()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateInformationModuleFunctionControllertableRowData()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateInformationModuleFunctionControllerhandleExternalFunctionValue()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateObjectBrowserModuleFunctionControllerinitialize_editor()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateObjectBrowserModuleFunctionControllermodMenu()`
* :php:`TYPO3CMSTstemplateControllerTypoScriptTemplateObjectBrowserModuleFunctionControllerhandleExternalFunctionValue()`


Impact
======

If an extension accesses one of the above protected properties or calls one of the above protected methods,
a :php:`E_USER_DEPRECATED` error will be triggered.


Affected Installations
======================

There are not many extensions that extend the `tstemplate` extension with own modules, it is relatively
unlikely that instances are affected by this.


Migration
=========

If extending the `tstemplate` module with an own extension, the extension should be adapted to not call
the above methods or properties any longer. Most usages can be easily adapted, for instance
to retrieve the current page id, use :php:`GeneralUtility::_GP('id')` instead of :php:`$this->pObj->id`.

.. index:: Backend, PHP-API, NotScanned, ext:tstemplate
