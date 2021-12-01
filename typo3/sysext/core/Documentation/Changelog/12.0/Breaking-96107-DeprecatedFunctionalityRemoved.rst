.. include:: ../../Includes.txt

===================================================
Breaking: #96107 - Deprecated functionality removed
===================================================

See :issue:`96107`

Description
===========

The following PHP classes that have previously been marked as deprecated for v11 and were now removed:

- :php:`\TYPO3\CMS\Backend\View\BackendTemplateView`
- :php:`\TYPO3\CMS\Core\Cache\Backend\PdoBackend`
- :php:`\TYPO3\CMS\Core\Cache\Backend\WincacheBackend`
- :php:`\TYPO3\CMS\Core\Category\CategoryRegistry`
- :php:`\TYPO3\CMS\Core\Database\QueryGenerator`
- :php:`\TYPO3\CMS\Core\Database\QueryView`
- :php:`\TYPO3\CMS\Core\Database\SoftReferenceIndex`
- :php:`\TYPO3\CMS\Core\Service\AbstractService`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\BackendUser`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FrontendUser`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\AbstractView`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\EmptyView`
- :php:`\TYPO3\CMS\Extbase\Service\EnvironmentService`
- :php:`\TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject`

The following PHP interfaces that have previously been marked as deprecated for v11 and were now removed:

- :php:`\TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface`
- :php:`\TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\ViewInterface`
- :php:`\TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface`

The following PHP class aliases that have previously been marked as deprecated for v11 and were now removed:

* :php:`Full\Class\Name`

The following PHP class methods that have previously been marked as deprecated for v11 and were now removed:

- :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->addItemsFromSpecial()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getIconFactory()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getPageRenderer()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->setNavigationFrameScript()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->getNavigationFrameScript()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->setNavigationFrameScriptParameters()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->getNavigationFrameScriptParameters()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->setOnClick()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->getOnClick()`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->fixVersioningPid()`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->editIcons()`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->editPanel()`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->isDisabled()`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_editIcons()`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_editPanel()`
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_getEditPanel()`
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_getEditIcon()`

The following PHP static class methods that have previously been marked as deprecated for v11 and were now removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::explodeSoftRefParserList()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::softRefParserObj()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick`
- :php:`\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\Index\FileIndexRepository::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::compileSelectedGetVarsFromArray()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hideIfNotTranslated()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hideIfDefaultLanguage()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::stdAuthCode()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList()`
- :php:`\TYPO3\CMS\Core\Utility\HttpUtility::redirect()`
- :php:`\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCode()`
- :php:`\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit()`
- :php:`\TYPO3\CMS\Core\Utility\StringUtility::beginsWith()`
- :php:`\TYPO3\CMS\Core\Utility\StringUtility::endsWith()`
- :php:`\TYPO3\CMS\Form\Service\TranslationService::getInstance()`
- :php:`\TYPO3\CMS\T3editor\Registry\AddonRegistry::getInstance()`
- :php:`\TYPO3\CMS\T3editor\Registry\ModeRegistry::getInstance()`

The following methods changed signature according to previous deprecations in v11 at the end of the argument list:

- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->ATagParams` (argument 2 is removed)

The following methods changed signature according to previous deprecations in v11 and are now type hinted:

- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getIcon()` (first argument is now type hinted `array`)

The following public class properties have been removed:

- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.php->displayEditIcons`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.php->displayFieldEditIcons`
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_EPtemp_cObj`

The following class methods visibility have been changed to protected:

- :php:`\TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser->getTypoLinkParts`
- :php:`\TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser->setTypoLinkPartsElement`

The following class methods visibility have been changed to private:

- :php:`\Full\Class\Name->methodName`

The following class properties visibility have been changed to protected:

- :php:`\Full\Class\Name->propertyName`

The following class properties visibility have been changed to private:

- :php:`\Full\Class\Name->propertyName`

The following ViewHelpers have been changed or removed:

- :html:`<be:moduleLayout>` removed
- :html:`<be:moduleLayout.menu>` removed
- :html:`<be:moduleLayout.menuItem>` removed
- :html:`<be:moduleLayout.button.linkButton>` removed
- :html:`<be:moduleLayout.button.shortcutButton>` removed

The following TypoScript options have been removed or adapted:

- `EDITPANEL` content object
- `mod.linkvalidator.linkhandler.reportHiddenRecords`
- `page.includeCSS.myfile*.import`
- `page.includeCSSLibs.myfile*.import`
- `stdWrap.editPanel`
- `stdWrap.editPanel.`
- `stdWrap.editIcons`
- `stdWrap.editIcons.`

The following constants have been dropped:

- :php:`CONSTANT_NAME`

The following class constants have been dropped:

- :php:`\Full\Class\Name::CONSTANT_NAME`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables']`

The following global variables have been removed:

- :php:`$GLOBALS['KEY']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']`

The following single field configurations have been removed from TCA:

- :php:`special` (for TCA type :php:`select`)

The following signals have been removed:

- :php:`\Full\Class\Name::signalName`

The following features are now always enabled:

- `the.feature.name`

The following features have been removed:

- A feature like a removed upgrade wizard

The following database tables have been removed:

- :sql:`table`

The following database table fields have been removed:

- :sql:`table.field`

The following Backend route identifiers have been removed:

- `routeIdentifier`

The following global JavaScript variables have been removed:

- :js:`top.currentSubScript`
- :js:`top.nextLoadModuleUrl`

The following global JavaScript functions have been removed:

- :js:`top.goToModule()`
- :js:`top.jump()`

The following JavaScript modules have been removed:

- :js:`module.name`

The following module configuration have been removed:

- :php:`navFrameScript`
- :php:`navFrameScriptParam`
- :php:`navigationFrameModule` (Extbase)

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
