.. include:: /Includes.rst.txt

.. _breaking-100963-1686129084:

====================================================
Breaking: #100963 - Deprecated functionality removed
====================================================

See :issue:`100963`

Description
===========

The following PHP classes that have previously been marked as deprecated with v12 have been removed:

- :php:`\TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher`
- :php:`\TYPO3\CMS\Backend\EventListener\SilentSiteLanguageFlagMigration`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\HelpButton`
- :php:`\TYPO3\CMS\Backend\Tree\View\BrowseTreeView`
- :php:`\TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView`
- :php:`\TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader`
- :php:`\TYPO3\CMS\Core\Configuration\PageTsConfig`
- :php:`\TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser`
- :php:`\TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher`
- :php:`\TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException`
- :php:`\TYPO3\CMS\Core\Controller\RequireJsController`
- :php:`\TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction`
- :php:`\TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction`
- :php:`\TYPO3\CMS\Core\Exception\MissingTsfeException`
- :php:`\TYPO3\CMS\Core\ExpressionLanguage\DeprecatingRequestWrapper`
- :php:`\TYPO3\CMS\Core\Resource\Service\MagicImageService`
- :php:`\TYPO3\CMS\Core\Resource\Service\UserFileInlineLabelService`
- :php:`\TYPO3\CMS\Core\Resource\Service\UserFileMountService`
- :php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`
- :php:`\TYPO3\CMS\Core\TypoScript\TemplateService`
- :php:`\TYPO3\CMS\Core\Utility\ResourceUtility`
- :php:`\TYPO3\CMS\Dashboard\Views\Factory`
- :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons\CshViewHelper`
- :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\Labels\CshViewHelper`
- :php:`\TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher`
- :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin`

The following PHP classes have been declared :php:`final`:

- :php:`\TYPO3\CMS\Core\Database\Driver\PDOMySql\Driver`
- :php:`\TYPO3\CMS\Core\Database\Driver\PDOPgSql\Driver`
- :php:`\TYPO3\CMS\Core\Database\Driver\PDOSqlite\Driver`

The following PHP interfaces that have previously been marked as deprecated with v12 have been removed:

- :php:`\TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface`
- :php:`\TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface`
- :php:`\TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface`
- :php:`\TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\ConditionMatcherInterface`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageOverlayHookInterface`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetRecordOverlayHookInterface`
- :php:`\TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuFilterPagesHookInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\TypolinkModifyLinkConfigForPageLinksHookInterface`
- :php:`\TYPO3\CMS\Frontend\Http\UrlProcessorInterface`

The following PHP interfaces changed:

- :php:`\TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface` method :php:`setModuleData()` added
- :php:`\TYPO3\CMS\Backend\Form\NodeInterface` method :php:`setData()` added
- :php:`\TYPO3\CMS\Backend\Form\NodeInterface` method :php:`render()` must return :php:`array`
- :php:`\TYPO3\CMS\Backend\Form\NodeResolverInterface` method :php:`setData()` added
- :php:`\TYPO3\CMS\Backend\Form\NodeResolverInterface` method :php:`resolve()` must return :php:`?string`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface` method `getContentObject()` removed
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface` method `isFeatureEnabled()` removed
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface` method `setContentObject()` removed
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface` method `setRequest()` added

The following PHP class aliases that have previously been marked as deprecated with v12 have been removed:

- :php:`\TYPO3\CMS\Backend\ElementBrowser\FileBrowser`
- :php:`\TYPO3\CMS\Backend\ElementBrowser\FolderBrowser`
- :php:`\TYPO3\CMS\Backend\Form\Element\InputColorPickerElement`
- :php:`\TYPO3\CMS\Backend\Form\Element\InputDateTimeElement`
- :php:`\TYPO3\CMS\Backend\Form\Element\InputLinkElement`
- :php:`\TYPO3\CMS\Backend\Provider\PageTsBackendLayoutDataProvider`
- :php:`\TYPO3\CMS\Frontend\Service\TypoLinkCodecService`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkResultFactory`
- :php:`\TYPO3\CMS\Recordlist\Browser\AbstractElementBrowser`
- :php:`\TYPO3\CMS\Recordlist\Browser\DatabaseBrowser`
- :php:`\TYPO3\CMS\Recordlist\Browser\ElementBrowserInterface`
- :php:`\TYPO3\CMS\Recordlist\Browser\ElementBrowserRegistry`
- :php:`\TYPO3\CMS\Recordlist\Browser\FileBrowser`
- :php:`\TYPO3\CMS\Recordlist\Browser\FolderBrowser`
- :php:`\TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController`
- :php:`\TYPO3\CMS\Recordlist\Controller\AccessDeniedException`
- :php:`\TYPO3\CMS\Recordlist\Controller\ClearPageCacheController`
- :php:`\TYPO3\CMS\Recordlist\Controller\ElementBrowserController`
- :php:`\TYPO3\CMS\Recordlist\Controller\RecordDownloadController`
- :php:`\TYPO3\CMS\Recordlist\Controller\RecordListController`
- :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListHeaderColumnsEvent`
- :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent`
- :php:`\TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent`
- :php:`\TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\AbstractLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\FolderLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\MailLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\PageLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\RecordLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\TelephoneLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\LinkHandler\UrlLinkHandler`
- :php:`\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList`
- :php:`\TYPO3\CMS\Recordlist\RecordList\DownloadRecordList`
- :php:`\TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface`
- :php:`\TYPO3\CMS\Recordlist\View\FolderUtilityRenderer`
- :php:`\TYPO3\CMS\Recordlist\View\RecordSearchBoxComponent`

The following PHP class methods that have previously been marked as deprecated with v12 have been removed:

- :php:`\TYPO3\CMS\Backend\Template\Components\ButtonBar->makeHelpButton()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getBodyTag()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getDynamicTabMenu()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->getView()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->header()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->isUiBlock()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->registerModuleMenu()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->renderContent()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->setContent()`
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->addTagAttributes()`
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->determineScriptUrl()`
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getRootIcon()`
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getRootRecord()`
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->getThisScript()`
- :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication->modAccess()`
- :php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools->removeElementTceFormsRecursive()`
- :php:`\TYPO3\CMS\Core\Database\Driver\PDOMySql\Driver->getName()`
- :php:`\TYPO3\CMS\Core\Database\Driver\PDOPgSql\Driver->getName()`
- :php:`\TYPO3\CMS\Core\Database\Driver\PDOSqlite\Driver->getName()`
- :php:`\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression->add()`
- :php:`\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression->addMultiple()`
- :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder->andX()`
- :php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder->orX()`
- :php:`\TYPO3\CMS\Core\Database\Query\QueryBuilder->execute()`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getExtURL()`
- :php:`\TYPO3\CMS\Core\Environment->getBackendPath()`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService->getLL()`
- :php:`\TYPO3\CMS\Core\Localization\Locales->getIsoMapping()`
- :php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction->shallLoadRequireJs()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->loadRequireJs()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->loadRequireJsModule()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setRenderXhtml()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getRenderXhtml()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setCharSet()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getCharSet()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setMetaCharsetTag()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getMetaCharsetTag()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setBaseUrl()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getBaseUrl()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->enableRemoveLineBreaksFromTemplate()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->disableRemoveLineBreaksFromTemplate()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->getRemoveLineBreaksFromTemplate()`
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->enableDebugMode()`
- :php:`\TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter->filterInlineChildren()`
- :php:`\TYPO3\CMS\Core\Session\UserSessionManager->createFromGlobalCookieOrAnonymous()`
- :php:`\TYPO3\CMS\Core\Site\Entity\SiteLanguage->getTwoLetterIsoCode()`
- :php:`\TYPO3\CMS\Core\Site\Entity\SiteLanguage->getDirection()`
- :php:`\TYPO3\CMS\Core\Type\DocType->getXhtmlDocType()`
- :php:`\TYPO3\CMS\Dashboard\DashboardInitializationService->getRequireJsModules()`
- :php:`\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager->getContentObject()`
- :php:`\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager->setContentObject()`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager->getContentObject()`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager->isFeatureEnabled()`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager->setContentObject()`
- :php:`\TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager->getContentObject()`
- :php:`\TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager->setContentObject()`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder->getRequest()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->setLanguageOverlayMode()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getLanguageOverlayMode()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->setLanguageUid()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings->getLanguageUid()`
- :php:`\TYPO3\CMS\Extbase\Property\AbstractTypeConverter->canConvertFrom()`
- :php:`\TYPO3\CMS\Extbase\Property\AbstractTypeConverter->getPriority()`
- :php:`\TYPO3\CMS\Extbase\Property\AbstractTypeConverter->getSupportedTargetType()`
- :php:`\TYPO3\CMS\Extbase\Property\AbstractTypeConverter->getSupportedSourceTypes()`
- :php:`\TYPO3\CMS\Fluid\View\StandaloneView->getFormat()`
- :php:`\TYPO3\CMS\Fluid\View\StandaloneView->getRequest()`
- :php:`\TYPO3\CMS\Fluid\View\StandaloneView->getTemplatePathAndFilename()`
- :php:`\TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent->getErrorMessage()`
- :php:`\TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent->isPropagationStopped()`
- :php:`\TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent->setAsInvalid()`
- :php:`\TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent->setHashedPassword()`
- :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->getUserTSconf()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->baseUrlWrap()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkEnableFields()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->doWorkspacePreview()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPagesTSconfig()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initUserGroups()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isBackendUserLoggedIn()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isUserOrGroupSet()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->whichWorkspace()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->createFromUriString()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getATagParams()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getMailTo()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getQueryArguments()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getTreeList()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getTypoLink_URL()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getTypoLink()`
- :php:`\TYPO3\CMS\Frontend\Typolink\LinkFactory->getUrlToCurrentLocation()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->addTask()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->fetchTaskRecord()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->fetchTaskWithCondition()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->fetchTask()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->isValidTaskObject()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->removeTask()`
- :php:`\TYPO3\CMS\Scheduler\Scheduler->saveTask()`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->isExecutionRunning()`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->markExecution()`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->remove()`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->unmarkAllExecutions()`
- :php:`\TYPO3\CMS\Scheduler\Task\AbstractTask->unmarkExecution()`
- :php:`\TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent->addModule()`
- :php:`\TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent->getModules()`

The following PHP static class methods that have previously been marked as deprecated for v12 have been removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::ADMCMD_previewCmds()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::cshItem()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getClickMenuOnIconTagParameters()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getDropdownMenu()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getPreviewUrl()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordToolTip()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getThumbnailUrl()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode()`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isModuleSetInTBE_MODULES()`
- :php:`\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()`
- :php:`\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances()`
- :php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction::forRequireJS()`
- :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::transform()`
- :php:`\TYPO3\CMS\Core\Utility\DebugUtility::debugInPopUpWindow()`
- :php:`\TYPO3\CMS\Core\Utility\DebugUtility::debugRows()`
- :php:`\TYPO3\CMS\Core\Utility\DebugUtility::printArray()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addCoreNavigationComponent()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addNavigationComponent()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig()`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::_GET()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::_GP()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::_POST()`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript()`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule()`
- :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter()`

The following methods changed signature according to previous deprecations in v12 at the end of the argument list:

- :php:`\TYPO3\CMS\Backend\Form\FormDataCompiler->compile()` (argument 2 is now required)
- :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage->setSeverity()` (argument 1 is now of type :php:`ContextualFeedbackSeverity`)
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessageQueue->clear()` (argument 1 is now of type :php:`ContextualFeedbackSeverity|null`)
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessageQueue->getAllMessagesAndFlush()` (argument 1 is now of type :php:`ContextualFeedbackSeverity|null`)
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessageQueue->getAllMessages()` (argument 1 is now of type :php:`ContextualFeedbackSeverity|null`)
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessageQueue->removeAllFlashMessagesFromSession()` (argument 1 is now of type :php:`ContextualFeedbackSeverity|null`)
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessages->__construct()` (argument 3 is now of type :php:`ContextualFeedbackSeverity`)
- :php:`\TYPO3\CMS\Core\Page\PageRenderer->setLanguage()` (argument 1 is now of type :php:`Locale`)
- :php:`\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility->addMessageToFlashMessageQueue()` (argument 2 is now of type :php:`ContextualFeedbackSeverity|null`)
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode()` (argument 4 :php:`$limit` has been removed)
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->addFlashMessage()` (argument 2 is now of type :php:`ContextualFeedbackSeverity`)
- :php:`\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate()` (argument 4 has been removed)
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->start()` (argument 3 :php:`$request` has been removed)
- :php:`\TYPO3\CMS\Reports\Status->__construct()` (argument 4 is now of type :php:`ContextualFeedbackSeverity`)
- :php:`\TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider->addMessage()` (argument 2 is now of type :php:`ContextualFeedbackSeverity`)

The following public class properties have been dropped:

- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->BE_USER`
- :php:`\TYPO3\CMS\Backend\Tree\View\AbstractTreeView->thisScript`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService->debugKey`
- :php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce->b64`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkLD`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkTarget`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->lastTypoLinkUrl`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->baseUrl`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->extTarget`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fileTarget`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->intTarget`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->spamProtectEmailAddresses`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->tmpl`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->xhtmlDoctype`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->xhtmlVersion`

The following class method visibility has been changed to protected:

- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->getRecordOverlay()`

The following class methods are now marked as internal:

- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->isSetSessionCookie()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->isRefreshTimeBasedCookie()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->removeCookie()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->isCookieSet()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->unpack_uc()`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->appendCookieToResponse()`

The following class methods now have a native return type and removed the
:php:`#[\ReturnTypeWillChange]` attribute:

- :php:`\TYPO3\CMS\Core\Collection\AbstractRecordCollection->current()`
- :php:`\TYPO3\CMS\Core\Collection\AbstractRecordCollection->key()`
- :php:`\TYPO3\CMS\Core\Log\LogRecord->offsetGet()`
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessageQueue->dequeue()`
- :php:`\TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection->key()`
- :php:`\TYPO3\CMS\Core\Resource\MetaDataAspect->offsetGet()`
- :php:`\TYPO3\CMS\Core\Resource\MetaDataAspect->current()`
- :php:`\TYPO3\CMS\Core\Resource\Search\Result\EmptyFileSearchResult->current()`
- :php:`\TYPO3\CMS\Core\Resource\Search\Result\EmptyFileSearchResult->key()`
- :php:`\TYPO3\CMS\Core\Routing\SiteRouteResult->offsetGet()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy->current()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy->key()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage->current()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage->offsetGet()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult->offsetGet()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult->current()`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult->key()`
- :php:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage->current()`
- :php:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage->offsetGet()`
- :php:`\TYPO3\CMS\Filelist\Dto\ResourceCollection->current()`
- :php:`\TYPO3\CMS\Filelist\Dto\ResourceCollection->key()`

The following class properties visibility have been changed to protected:

- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->where_hid_del`
- :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository->where_groupAccess`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->type`

The following class property visibility has been changed to private:

- :php:`\TYPO3\CMS\Core\Type\DocType->getXhtmlVersion`

The following class properties have been marked as internal:

- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->lastLogin_column`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->formfield_uname`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->formfield_uident`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->formfield_status`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->loginSessionStarted`
- :php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->dontSetCookie`
- :php:`\TYPO3\CMS\Core\Authentication\FrontendUserAuthentication->formfield_permanent`
- :php:`\TYPO3\CMS\Core\Authentication\FrontendUserAuthentication->is_permanent`

The following class property has changed/enforced type:

- :php:`\TYPO3\CMS\Core\Page\PageRenderer->endingSlash` (is now string)

The following eID entry point has been removed:

- :php:`requirejs`

The following ViewHelpers have been changed or removed:

- :html:`<f:be.buttons.csh>` removed
- :html:`<f:be.labels.csh>` removed
- :html:`<f:translate>` Argument "alternativeLanguageKeys" has been removed

The following TypoScript options have been dropped or adapted:

- :typoscript:`config.baseURL`
- :typoscript:`config.removePageCss`
- :typoscript:`config.spamProtectEmailAddresses` (only `ascii` value)
- :typoscript:`config.xhtmlDoctype`
- :typoscript:`plugin.[pluginName]._CSS_PAGE_STYLE`
- :typoscript:`[ip()]` condition function must be used in a context with request
- :typoscript:`[loginUser()]` condition function removed
- :typoscript:`[usergroup()]` condition function removed
- :typoscript:`constants` setup top-level-object and :typoscript:`constants` property of :typoscript:`parseFunc`
- :typoscript:`plugin.tx_felogin_login.settings.passwordValidators` has been removed

The following constant has been dropped:

- :php:`TYPO3_mainDir`

The following class constants have been dropped:

- :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR`
- :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage::INFO`
- :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage::NOTICE`
- :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage::OK`
- :php:`\TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING`
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR`
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::INFO`
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE`
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::OK`
- :php:`\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING`
- :php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction::FLAG_LOAD_REQUIRE_JS`
- :php:`\TYPO3\CMS\Reports\Status::ERROR`
- :php:`\TYPO3\CMS\Reports\Status::INFO`
- :php:`\TYPO3\CMS\Reports\Status::NOTICE`
- :php:`\TYPO3\CMS\Reports\Status::OK`
- :php:`\TYPO3\CMS\Reports\Status::WARNING`

The following global option handling have been dropped and are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename']` only accepts a boolean value now

The following global variables have been removed:

- :php:`$GLOBALS['TBE_STYLES']`
- :php:`$GLOBALS['TBE_STYLES']['stylesheet']`
- :php:`$GLOBALS['TBE_STYLES']['stylesheet2']`
- :php:`$GLOBALS['TBE_STYLES']['skins']`
- :php:`$GLOBALS['TBE_STYLES']['admPanel']`
- :php:`$GLOBALS['TCA_DESCR']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php']['flushByTag']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postLoginFailureProcessing']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['backendUserLogin']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getDefaultUploadFolder']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Lowlevel\Controller\ConfigurationController']['modifyBlindedConfigurationOptions']`

The following single field configuration has been removed from TCA:

- :php:`MM_insert_fields` (for TCA fields with `MM` configuration)

The following event has been removed:

- :php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent`

The following fallbacks have been removed:

- Usage of the :file:`ext_icon.*` file locations for extension icons
- Usage of the result property :php:`additionalJavaScriptPost` of the form engine result array
- Using chart.js v3 compatible widgets in ext:dashboard
- Usage of :js:`.t3js-contextmenutrigger` to trigger and configure context menus
- Usage of the jsonArray property :php:`scriptCall` for AjaxController's
- Binding the selected menu items to callback actions in context menus
- Checking for :php:`\TYPO3\CMS\Core\Site\SiteLanguageAwareTrait` is removed in :php:`\TYPO3\CMS\Core\Routing\Aspect\AspectFactory`
- :html:`f:format.html` ViewHelper no longer works in BE context
- Usage of :php:`JScode` containing inline JavaScript for handing custom signals
- Usage property :php:`$resultArray['requireJsModules']` of the form engine result array
- Using backend FormEngine, the current ServerRequestInterface request must be provided in key "request" as
  initialData to FormDataCompiler, the fallback to :php:`$GLOBALS['TYPO3_REQUEST']` has been removed.
- Compatibility layer for "TCEforms" key in FlexFormTools has been removed
- Compatibility layer for using array parameters for files in extbase (use `UploadedFile` instead)

The following upgrade wizards have been removed:

- Wizard for migrating backend user languages
- Wizard for installing the extension "legacy_collections" from TER
- Wizard for migrating the :php:`transOrigDiffSourceField` field to a json encoded string
- Wizard for cleaning up workspace `new` placeholders
- Wizard for cleaning up workspace `move` placeholders
- Wizard for migrating shortcut records
- Wizard for sanitizing existing SVG files in the `fileadmin` folder
- Wizard for populating a new channel column of the sys_log table

The following features are now always enabled:

- `security.backend.enforceContentSecurityPolicy`

The following feature has been removed:

- Regular expression based validators in ext:form backend UI

The following database table fields have been removed:

- :sql:`fe_users.TSconfig`
- :sql:`fe_groups.TSconfig`

The following backend route identifier has been removed:

- `ajax_core_requirejs`

The following global JavaScript variable has been removed:

- :js:`TYPO3.Tooltip`

The following global JavaScript function has been removed:

- :js:`Global_JavaScript_Function_Name`

The following JavaScript module has been removed:

- :js:`tooltip`

The following JavaScript method behaviour has changed:

- :js:`ColorPicker.initialize()` always requires an :js:`HTMLInputElement` to be passed as first argument

The following JavaScript method has been removed:

- :js:`getParameterFromUrl()` of :js:`@typo3/backend/utility`

The following CKEditor plugin has been removed:

- :js:`SoftHyphen`

The following dependency injection service aliase has been removed:

- :yaml:`@dashboard.views.widget`

Impact
======

Using above removed functionality will most likely raise PHP fatal level errors,
may change website output or crashes browser JavaScript.

.. index:: Backend, CLI, Database, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, RTE, TCA, TSConfig, TypoScript, PartiallyScanned
