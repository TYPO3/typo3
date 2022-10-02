.. include:: /Includes.rst.txt

.. _breaking-96107:

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
- :php:`\TYPO3\CMS\Extbase\Annotation\Inject`
- :php:`\TYPO3\CMS\Extbase\Configuration\Exception\ParseErrorException`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\BackendUser`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FrontendUser`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException`
- :php:`\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\AbstractView`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\EmptyView`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\ReferringRequest`
- :php:`\TYPO3\CMS\Extbase\Object\Container\Container`
- :php:`\TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException`
- :php:`\TYPO3\CMS\Extbase\Object\Exception`
- :php:`\TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException`
- :php:`\TYPO3\CMS\Extbase\Object\Exception\CannotReconstituteObjectException`
- :php:`\TYPO3\CMS\Extbase\Object\ObjectManager`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException`
- :php:`\TYPO3\CMS\Extbase\Service\EnvironmentService`
- :php:`\TYPO3\CMS\Extbase\SignalSlot\Dispatcher`
- :php:`\TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException`
- :php:`\TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException`
- :php:`\TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject`

The following PHP classes have been declared final:

- All Fluid ViewHelpers

The following PHP interfaces that have previously been marked as deprecated for v11 and were now removed:

- :php:`\TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface`
- :php:`\TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface`
- :php:`\TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface`
- :php:`\TYPO3\CMS\Core\Utility\File\ExtendedFileUtilityProcessDataHookInterface`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\ViewInterface`
- :php:`\TYPO3\CMS\Extbase\Object\ObjectManagerInterface`
- :php:`\TYPO3\CMS\Extbase\Persistence\ForwardCompatibleQueryInterface`
- :php:`\TYPO3\CMS\Extbase\Persistence\ForwardCompatibleQueryResultInterface`
- :php:`\TYPO3\CMS\Filelist\FileListEditIconHookInterface'`
- :php:`\TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface`

The following PHP interfaces changed:

- :php:`\TYPO3\CMS\Core\Collection\CollectionInterface` (no longer extends \Serializable)
- :php:`\TYPO3\CMS\Core\Resource\FolderInterface` (method :php:`getFile()` added)
- :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface` (method :php:`setType()` added)
- :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface->logicalAnd` (all arguments are now type hinted as `ConstraintInterface`)
- :php:`\TYPO3\CMS\Extbase\Persistence\QueryInterface->logicalOr` (all arguments are now type hinted as `ConstraintInterface`)
- :php:`\TYPO3\CMS\Extbase\Persistence\QueryResultInterface` (method :php:`setQuery()` added)
- :php:`\TYPO3\CMS\Form\Domain\Finishers\FinisherInterface` (method :php:`setFinisherIdentifier()` added)
- :php:`\TYPO3\CMS\Frontend\ContentObject\Exception\ExceptionHandlerInterface` (method :php:`setConfiguration()` added)

The following PHP class methods that have previously been marked as deprecated for v11 and were now removed:

- :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->addItemsFromSpecial()`
- :php:`\TYPO3\CMS\Backend\Template\Components\AbstractControl->getOnClick'()`
- :php:`\TYPO3\CMS\Backend\Template\Components\AbstractControl->setOnClick'()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getIconFactory()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getPageRenderer()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->setNavigationFrameScript()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->getNavigationFrameScript()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->setNavigationFrameScriptParameters()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->getNavigationFrameScriptParameters()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->setOnClick()`
- :php:`\TYPO3\CMS\Backend\Domain\Module\BackendModule->getOnClick()`
- :php:`\TYPO3\CMS\Backend\View\Event\AbstractSectionMarkupGeneratedEvent->getPageLayoutView()`
- :php:`\TYPO3\CMS\Backend\View\Event\AbstractSectionMarkupGeneratedEvent->getLanguageId()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->createSessionId()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->fetchUserSession()`
- :php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools->getArrayValueByPath()`
- :php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools->setArrayValueByPath()`
- :php:`\TYPO3\CMS\Core\Database\ReferenceIndex->disableRuntimeCache()`
- :php:`\TYPO3\CMS\Core\Database\ReferenceIndex->enableRuntimeCache()`
- :php:`\TYPO3\CMS\Core\Database\RelationHandler->setUpdateReferenceIndex()`
- :php:`\TYPO3\CMS\Core\Database\RelationHandler->remapMM()`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->fixVersioningPid()`
- :php:`\TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent->isRelativeToCurrentScript()`
- :php:`\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider->getRootUid()`
- :php:`\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider->setRootUid()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->buildControllerContext()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->getControllerContext()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->forward()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Request->getBaseUri()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Request->getRequestUri()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Request->isDispatched()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Request->setDispatched()`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView->setControllerContext()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder->setAddQueryStringMethod()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getLanguageMode()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->setLanguageMode()`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->getControllerContext()`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->setControllerContext()`
- :php:`\TYPO3\CMS\Fluid\View\AbstractTemplateView->setControllerContext()`
- :php:`\TYPO3\CMS\Form\Domain\Renderer\AbstractElementRenderer->setControllerContext()`
- :php:`\TYPO3\CMS\Form\Domain\Renderer\RendererInterface->setControllerContext()`
- :php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime->getControllerContext()`
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
- :php:`\TYPO3\CMS\Core\Localization\LanguageService::create()`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService::createFromSiteLanguage()`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService::createFromUserPreferences()`
- :php:`\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\Index\FileIndexRepository::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance()`
- :php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->doSyntaxHighlight()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::compileSelectedGetVarsFromArray()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hideIfNotTranslated()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::hideIfDefaultLanguage()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedHostHeaderValue()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::stdAuthCode()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList()`
- :php:`\TYPO3\CMS\Core\Utility\HttpUtility::redirect()`
- :php:`\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCode()`
- :php:`\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit()`
- :php:`\TYPO3\CMS\Core\Utility\StringUtility::beginsWith()`
- :php:`\TYPO3\CMS\Core\Utility\StringUtility::endsWith()`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::getControllerClassName()`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::resolveVendorFromExtensionAndControllerClassName()`
- :php:`\TYPO3\CMS\Form\Service\TranslationService::getInstance()`
- :php:`\TYPO3\CMS\T3editor\Registry\AddonRegistry::getInstance()`
- :php:`\TYPO3\CMS\T3editor\Registry\ModeRegistry::getInstance()`

The following PHP class methods changed signature according to previous deprecations in v11 at the end of the argument list:

- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->unpack_uc()` (argument 1 removed)
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->writeUC()` (argument 1 removed)
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->start()` (argument 1 always required)
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->checkAuthentication()` (argument 1 always required)
- :php:`\TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication->checkAuthentication()` (argument 1 always required)
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->isInWebMount()` (argument 3 removed)
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->backendCheckLogin()` (argument 1 removed)
- :php:`\TYPO3\CMS\Core\Core\ApplicationInterface->run()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Database\RelationHandler->writeForeignField()` (argument 4 removed)
- :php:`\TYPO3\CMS\Core\Resource\AbstractFile->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\File->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\FileInterface->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\FileReference->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\Folder->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\ProcessedFile->getPublicUrl()` (argument 1 is removed)
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->getPublicUrl()` (argument 2 is removed)
- :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface->getPublicUrl()` (argument 2 is removed)
- :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper->getPublicUrl()` (argument 2 is removed)
- :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\YouTubeHelper->getPublicUrl()` (argument 2 is removed)
- :php:`\TYPO3\CMS\Extbase\Core\Bootstrap->run()` (optional third argument is now required)
- :php:`\TYPO3\CMS\Fluid\View\StandaloneView->__construct()` (optional constructor argument is removed)
- :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->fetchGroupData()` (argument 1 always required)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->ATagParams()` (argument 2 is removed)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getConfigArray()` (argument 1 is always required)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->determineId()` (argument 1 is always required)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->INTincScript()` (argument 1 is always required)
- :php:`\TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder->build()` (return type is now of LinkResultInterface)

The following PHP static class methods changed signature according to previous deprecations in v11 at the end of the argument list:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::wrapClickMenuOnIcon()` (arguments 5, 6 and 7 are removed)
- :php:`\TYPO3\CMS\Core\Utility\ArrayUtility::arrayDiffAssocRecursive()` (argument 3 is removed)

The following PHP class methods changed signature according to previous deprecations in v11 and are now type hinted:

- :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder->literal()` (second argument requires an integer)
- :php:`\TYPO3\CMS\Core\Database\Query\QueryBuilder->quote()` (second argument requires an integer)
- :php:`\TYPO3\CMS\Core\TimeTracker\TimeTracker->setTSlogMessage()` (second argument requires a string)
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getIcon()` (first argument is now type hinted `array`)
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Query->logicalAnd()` (all arguments are now type hinted as `ConstraintInterface`)
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Query->logicalOr()` (all arguments are now type hinted as `ConstraintInterface`)
- :php:`\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList->linkUrlMail()` (all arguments are now type hinted as `string`)

The following PHP class methods changed signature according to previous deprecations:

- :php:`\TYPO3\CMS\Core\Controller\ErrorPageController->errorAction()` (the third argument :php:`$severity` is removed)

The following class properties have been removed:

- :php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->breakPointLN`
- :php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->parentObject`
- :php:`\TYPO3\CMS\Core\TypoScript\TemplateService->ext_constants_BRP`
- :php:`\TYPO3\CMS\Core\TypoScript\TemplateService->ext_config_BRP`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->controllerContext`
- :php:`\TYPO3\CMS\Extbase\Mvc\View\JsonView->controllerContext`
- :php:`\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext->controllerContext`
- :php:`\TYPO3\CMS\Fluid\View\AbstractTemplateView->controllerContext`
- :php:`\TYPO3\CMS\Form\Domain\Renderer\AbstractElementRenderer->controllerContext`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->align`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->oldData`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->alternativeData`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->currentRecordTotal`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->recordRegister`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->ATagParams`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cObjectDepthCounter`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->displayEditIcons`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->displayFieldEditIcons`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sWordRegex` (internal, but public)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sWordList` (internal, but public)
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin->pi_EPtemp_cObj`

The following class properties have been changed:

- :php:`\TYPO3\CMS\Core\TimeTracker\TimeTracker->wrapError` (does not contain numeric keys anymore)
- :php:`\TYPO3\CMS\Core\TimeTracker\TimeTracker->wrapIcon` (does not contain numeric keys anymore)

The following class methods visibility have been changed to protected:

- :php:`\TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser->getTypoLinkParts()`
- :php:`\TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser->setTypoLinkPartsElement()`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::resolveControllerAliasFromControllerClassName()`

The following class properties visibility have been changed to protected:

- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->cObj`
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin->cObj`

The following ViewHelpers have been changed or removed:

- :html:`<be:moduleLayout>` removed
- :html:`<be:moduleLayout.menu>` removed
- :html:`<be:moduleLayout.menuItem>` removed
- :html:`<be:moduleLayout.button.linkButton>` removed
- :html:`<be:moduleLayout.button.shortcutButton>` removed
- :html:`<f:base>` removed
- :html:`<f:be.container>` removed
- :html:`<f:uri.email>` removed
- :html:`<f:form>` (:php:`addQueryStringMethod` argument removed)
- :html:`<f:link.action>` (:php:`addQueryStringMethod` argument removed)
- :html:`<f:link.page>` (:php:`addQueryStringMethod` argument removed)
- :html:`<f:link.typolink>` (:php:`addQueryStringMethod` argument removed)
- :html:`<f:uri.action>` (:php:`addQueryStringMethod` argument removed)
- :html:`<f:uri.page>` (:php:`addQueryStringMethod` argument removed)
- :html:`<f:uri.typolink>` (:php:`addQueryStringMethod` argument removed)

The following TypoScript options have been removed or adapted:

- `config.sword_standAlone`
- `config.sword_noMixedCase`
- `_parseFunc.sword`
- `EDITPANEL` content object
- `mod.linkvalidator.linkhandler.reportHiddenRecords`
- `page.includeCSS.myfile*.import`
- `page.includeCSSLibs.myfile*.import`
- `plugin.tx_indexedsearch.settings.forwardSearchWordsInResultLink`
- `plugin.tx_indexedsearch.settings.forwardSearchWordsInResultLink.no_cache`
- `stdWrap.editPanel`
- `stdWrap.editPanel.`
- `stdWrap.editIcons`
- `stdWrap.editIcons.`
- `TMENU.JSWindow`
- `TMENU.JSWindow.params`

The following constants have been dropped:

- :php:`TYPO3_branch`
- :php:`TYPO3_MODE`
- :php:`TYPO3_REQUESTTYPE`
- :php:`TYPO3_REQUESTTYPE_AJAX`
- :php:`TYPO3_REQUESTTYPE_BE`
- :php:`TYPO3_REQUESTTYPE_CLI`
- :php:`TYPO3_REQUESTTYPE_FE`
- :php:`TYPO3_REQUESTTYPE_INSTALL`
- :php:`TYPO3_version`

The following class constants have been dropped:

- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_SERVER_NAME`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::NEW_PLACEHOLDER_VERSION`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::MOVE_PLACEHOLDER`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']`
- :php:`$GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']`

The following single field configurations have been removed from TCA:

- :php:`special` (for TCA type :php:`select`)
- :php:`treeConfig.rootUid` (for TCA renderType :php:`selectTree` and :php:`category`)

The following single field configurations have been removed from :php:`$GLOBALS['TYPO3_USER_SETTINGS']`:

- :php:`confirmData.jsCodeAfterOk`
- :php:`onClick`
- :php:`onClickLabels`

The following features are now always enabled:

- `runtimeDbQuotingOfTcaConfiguration`
- `subrequestPageErrors`
- `yamlImportsFollowDeclarationOrder`

The following features have been removed:

- Extbase switchable controller actions
- Upgrade wizard "Migrate felogin plugins to use prefixed FlexForm keys"
- Upgrade wizard "Migrate felogin plugins to use Extbase CType"
- Upgrade wizard "Install extension 'feedit' from TER"
- Upgrade wizard "Install extension 'sys_action' from TER"
- Upgrade wizard "Install extension "taskcenter" from TER"
- Row upgrader "Workspace 'pid -1' migration"

The following fallbacks have been removed:

- Usage of the :html:`t3js-toggle-new-content-element-wizard` class to trigger the new content element wizard
- Usage of the :php:`DataHandler->inlineLocalizeSynchronize()` functionality without an array as input argument
- The :php:`route` parameter is no longer added to backend URLs
- Extensions, which are located in `typo3conf/ext`, but not installed by Composer, are no longer evaluated for installations in "Composer mode"
- Extbase no longer accepts :php:`MyVendor.` prefixed :php:`MyExtensionName` as first argument in
  :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin()`, :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin()`
  and :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule()` and controller class names must be registered
  with their fully qualified name.
- Extbase no longer determines types from doc block annotations for dependency injection methods and actions with validators,
  defined types in method signatures must be used.
- Accessing Core related caches with :php:`cache_` prefix has been removed.
- Accessing :php:`\TYPO3\CMS\Frontend\Typolink\LinkResult` properties as arrays - ArrayAccess functionality removed

The following database tables have been removed:

- :sql:`sys_language`

The following global JavaScript variables have been removed:

- :js:`top.currentSubScript`
- :js:`top.fsMod`
- :js:`top.nextLoadModuleUrl`

The following global JavaScript functions have been removed:

- :js:`top.goToModule()`
- :js:`top.jump()`

The following JavaScript functions have been removed:

- :js:`FormEngine.requestConfirmationOnFieldChange()`
- :js:`TBE_EDITOR.fieldChanged()`

The following JavaScript methods behaviour has changed:

- :js:`show()` and :js:`hide()` of :js:`TYPO3/CMS/Backend/Tooltip` do no longer allow JQuery objects passed as first argument
- :js:`FormEngine.setSelectOptionFromExternalSource()` does no longer allow JQuery objects passed as sixth argument
- :js:`DateTimePicker.initialize()` always requires an :js:`HTMLInputElement` to be passed as first argument

The following JavaScript modules have been removed:

- :js:`TYPO3/CMS/Backend/SplitButtons`
- :js:`TYPO3/CMS/Core/Ajax/ResponseError`
- :js:`TYPO3/CMS/T3editor/T3editor`

The following RequireJS module names have been removed:

- :js:`Sortable`

The following module configuration have been removed:

- :php:`navFrameScript`
- :php:`navFrameScriptParam`
- :php:`navigationFrameModule` (Extbase)

The following command line options have been removed:

- :bash:`impexp:export --includeRelated`
- :bash:`impexp:export --includeStatic`
- :bash:`impexp:export --excludeDisabledRecords`
- :bash:`impexp:export --excludeHtmlCss`
- :bash:`impexp:export --saveFilesOutsideExportFile`
- :bash:`impexp:import --updateRecords`
- :bash:`impexp:import --ignorePid`
- :bash:`impexp:import --forceUid`
- :bash:`impexp:import --importMode`
- :bash:`impexp:import --enableLog`

The following dependency injection container entries have been removed:

- `\TYPO3\CMS\Core\Localization\LanguageService`
- `\TYPO3\CMS\Fluid\Core\Rendering\RenderingContext`
- `\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
