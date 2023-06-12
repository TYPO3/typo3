.. include:: /Includes.rst.txt

.. _breaking-100963-1686129084:

====================================================
Breaking: #100963 - Deprecated functionality removed
====================================================

See :issue:`100963`

Description
===========

The following PHP classes that have previously been marked as deprecated for v12 and were now removed:

- :php:`\TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader`
- :php:`\TYPO3\CMS\Core\Configuration\PageTsConfig`
- :php:`\TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser`
- :php:`\TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction`
- :php:`\TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction`
- :php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`
- :php:`\TYPO3\CMS\Core\TypoScript\TemplateService`
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin`

The following PHP classes have been declared :php:`final`:

- :php:`\Full\Class\Name`

The following PHP interfaces that have previously been marked as deprecated for v12 and were now removed:

- :php:`\Full\Class\Name`

The following PHP interfaces changed:

- :php:`\Full\Class\Name`

The following PHP class aliases that have previously been marked as deprecated for v12 and were now removed:

* :php:`\Full\Class\Name`

The following PHP class methods that have previously been marked as deprecated for v12 and were now removed:

- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getBodyTag`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getDynamicTabMenu`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getView`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->header`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->isUiBlock`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->registerModuleMenu`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->renderContent`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->setContent`
- :php:`\TYPO3\CMS\Core\Environment->getBackendPath`
- :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->getUserTSconf`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->baseUrlWrap`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkEnableFields`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->doWorkspacePreview`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPagesTSconfig`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initUserGroups`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isBackendUserLoggedIn`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isUserOrGroupSet`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->whichWorkspace`

The following PHP static class methods that have previously been marked as deprecated for v12 and were now removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getDropdownMenu`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu`

The following methods changed signature according to previous deprecations in v12 at the end of the argument list:

- :php:`\Full\Class\Name->methodName` (argument 42 is now an integer)

The following public class properties have been dropped:

- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->baseUrl`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->extTarget`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fileTarget`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->intTarget`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->spamProtectEmailAddresses`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->tmpl`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->xhtmlDoctype`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->xhtmlVersion`

The following class methods visibility have been changed to protected:

- :php:`\Full\Class\Name->methodName`

The following class methods visibility have been changed to private:

- :php:`\Full\Class\Name->methodName`

The following class properties visibility have been changed to protected:

- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->type`

The following class properties visibility have been changed to private:

- :php:`\Full\Class\Name->propertyName`

The following ViewHelpers have been changed or removed:

- :html:`<f:helper.name>` Argument "foo" dropped

The following TypoScript options have been dropped or adapted:

- :typoscript:`config.baseURL`
- :typoscript:`config.removePageCss`
- :typoscript:`config.spamProtectEmailAddresses` (only `ascii` value)
- :typoscript:`config.xhtmlDoctype`
- :typoscript:`plugin.[pluginName]._CSS_PAGE_STYLE`

The following constants have been dropped:

- :php:`CONSTANT_NAME`

The following class constants have been dropped:

- :php:`\Full\Class\Name::CONSTANT_NAME`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig']`

The following global variables have been removed:

- :php:`$GLOBALS['KEY']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['KEY']['subKey']`

The following single field configurations have been removed from TCA:

- :php:`MM_insert_fields` (for TCA fields with `MM` configuration)

The following single field configurations have been removed from :php:`$GLOBALS['TYPO3_USER_SETTINGS']`:

- :php:`dummy`

The following events have been removed:

- :php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent`

The following features are now always enabled:

- `the.feature.name`

The following features have been removed:

- A feature like a removed upgrade wizard

The following database tables have been removed:

- :sql:`table`

The following database table fields have been removed:

- :sql:`fe_users.TSconfig`
- :sql:`fe_groups.TSconfig`

The following Backend route identifiers have been removed:

- `routeIdentifier`

The following global JavaScript variables have been removed:

- :js:`Global_JavaScript_Variable_Name`

The following global JavaScript functions have been removed:

- :js:`Global_JavaScript_Function_Name`

The following JavaScript modules have been removed:

- :js:`module.name`

The following RequireJS module names have been removed:

- :js:`Dummy`

The following module configuration have been removed:

- :php:`dummy`

The following command line options have been removed:

- :bash:`a:command --option`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
