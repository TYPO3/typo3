.. include:: ../../Includes.txt

===================================================
Breaking: #87193 - Deprecated functionality removed
===================================================

See :issue:`87193`

Description
===========

The following PHP classes that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Adminpanel\View\AdminPanelView`
* :php:`TYPO3\CMS\Backend\Controller\LoginFramesetController`
* :php:`TYPO3\CMS\Backend\Http\AjaxRequestHandler`
* :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule`
* :php:`TYPO3\CMS\Backend\Module\AbstractModule`
* :php:`TYPO3\CMS\Backend\Module\BaseScriptClass`
* :php:`TYPO3\CMS\Backend\RecordList\AbstractRecordList`
* :php:`TYPO3\CMS\Core\Cache\Frontend\StringFrontend`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\AbstractComposedSalt`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\ExtensionManagerConfigurationUtility`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordService`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility`
* :php:`TYPO3\CMS\Core\Encoder\JavaScriptEncoder`
* :php:`TYPO3\CMS\Core\FrontendEditing\FrontendEditingController`
* :php:`TYPO3\CMS\Core\Integrity\DatabaseIntegrityCheck`
* :php:`TYPO3\CMS\Core\Log\Writer\RuntimeCacheWriter`
* :php:`TYPO3\CMS\Core\Package\DependencyResolver`
* :php:`TYPO3\CMS\Core\PageTitle\AltPageTitleProvider`
* :php:`TYPO3\CMS\Core\Resource\Service\UserStorageCapabilityService`
* :php:`TYPO3\CMS\Core\Resource\Utility\BackendUtility`
* :php:`TYPO3\CMS\Core\Utility\ClientUtility`
* :php:`TYPO3\CMS\Core\Utility\PhpOptionsUtility`
* :php:`TYPO3\CMS\Extbase\Command\CoreCommand`
* :php:`TYPO3\CMS\Extbase\Command\ExtbaseCommand`
* :php:`TYPO3\CMS\Extbase\Command\HelpCommand`
* :php:`TYPO3\CMS\Extbase\Command\HelpCommandController`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\Command`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\CommandManager`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\Request`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\RequestHandler`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\Response`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\Controller\CommandController`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\AmbiguousCommandIdentifierException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\CommandException`
* :php:`TYPO3\CMS\Extbase\Scheduler\FieldProvider`
* :php:`TYPO3\CMS\Extbase\Scheduler\Task`
* :php:`TYPO3\CMS\Extbase\Scheduler\TaskExecutor`
* :php:`TYPO3\CMS\Extensionmanager\Command\ExtensionCommandController`
* :php:`TYPO3\CMS\Frontend\Http\EidRequestHandler`
* :php:`TYPO3\CMS\Frontend\Page\ExternalPageUrlHandler`
* :php:`TYPO3\CMS\Frontend\Page\PageGenerator`
* :php:`TYPO3\CMS\Frontend\Utility\EidUtility`
* :php:`TYPO3\CMS\Recordlist\Controller\ElementBrowserFramesetController`
* :php:`TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList`
* :php:`TYPO3\CMS\Workspaces\Service\AutoPublishService`
* :php:`TYPO3\CMS\Workspaces\Task\AutoPublishTask`
* :php:`TYPO3\CMS\Workspaces\Task\CleanupPreviewLinkTask`


The following PHP interfaces that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Adminpanel\View\AdminPanelViewHookInterface`
* :php:`TYPO3\CMS\Extbase\Mvc\Cli\Controller\CommandControllerInterface`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\ComposedPasswordHashInterface`
* :php:`TYPO3\CMS\Frontend\Http\UrlHandlerInterface`


The following PHP class aliases that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Backend\AjaxLoginHandler`
* :php:`TYPO3\CMS\Backend\Form\Wizard\ImageManipulationWizard`
* :php:`TYPO3\CMS\Core\History\RecordHistory`
* :php:`TYPO3\CMS\Core\IO\PharStreamWrapper`
* :php:`TYPO3\CMS\Core\IO\PharStreamWrapperException`
* :php:`TYPO3\CMS\Core\Tree\TableConfiguration\ExtJsArrayTreeRenderer`
* :php:`TYPO3\CMS\ContextHelp\Controller\ContextHelpAjaxController`
* :php:`TYPO3\CMS\Cshmanual\Domain\Repository\TableManualRepository`
* :php:`TYPO3\CMS\Extbase\Configuration\Exception\ContainerIsLockedException`
* :php:`TYPO3\CMS\Extbase\Configuration\Exception\NoSuchFileException`
* :php:`TYPO3\CMS\Extbase\Configuration\Exception\NoSuchOptionException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidCommandIdentifierException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidMarkerException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidOrNoRequestHashException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestTypeException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidTemplateResourceException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidUriPatternException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidViewHelperException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\RequiredArgumentMissingException`
* :php:`TYPO3\CMS\Extbase\Object\Container\Exception\CannotInitializeCacheException`
* :php:`TYPO3\CMS\Extbase\Object\Container\Exception\TooManyRecursionLevelsException`
* :php:`TYPO3\CMS\Extbase\Object\Exception\WrongScopeException`
* :php:`TYPO3\CMS\Extbase\Object\InvalidClassException`
* :php:`TYPO3\CMS\Extbase\Object\InvalidObjectConfigurationException`
* :php:`TYPO3\CMS\Extbase\Object\InvalidObjectException`
* :php:`TYPO3\CMS\Extbase\Object\ObjectAlreadyRegisteredException`
* :php:`TYPO3\CMS\Extbase\Object\UnknownClassException`
* :php:`TYPO3\CMS\Extbase\Object\UnknownInterfaceException`
* :php:`TYPO3\CMS\Extbase\Object\UnresolvedDependenciesException`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception\CleanStateNotMemorizedException`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidPropertyTypeException`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingBackendException`
* :php:`TYPO3\CMS\Extbase\Property\Exception\FormatNotSupportedException`
* :php:`TYPO3\CMS\Extbase\Property\Exception\InvalidFormatException`
* :php:`TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyException`
* :php:`TYPO3\CMS\Extbase\Reflection\Exception\InvalidPropertyTypeException`
* :php:`TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException`
* :php:`TYPO3\CMS\Extbase\Security\Exception\SyntacticallyWrongRequestHashException`
* :php:`TYPO3\CMS\Extbase\Service\FlexFormService`
* :php:`TYPO3\CMS\Extbase\Service\TypoScriptService`
* :php:`TYPO3\CMS\Extbase\Validation\Exception\InvalidSubjectException`
* :php:`TYPO3\CMS\Extbase\Validation\Exception\NoValidatorFoundException`
* :php:`TYPO3\CMS\Frontend\Controller\PageInformationController`
* :php:`TYPO3\CMS\Frontend\Controller\TranslationStatusController`
* :php:`TYPO3\CMS\Frontend\View\AdminPanelView`
* :php:`TYPO3\CMS\Frontend\View\AdminPanelViewHookInterface`
* :php:`TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler`
* :php:`TYPO3\CMS\Fluid\Core\Exception`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode`
* :php:`TYPO3\CMS\Fluid\Core\Parser\InterceptorInterface`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\NodeInterface`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode`
* :php:`TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode`
* :php:`TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface`
* :php:`TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Exception`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\Facets\PostParseInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperInterface`
* :php:`TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer`
* :php:`TYPO3\CMS\Fluid\View\Exception`
* :php:`TYPO3\CMS\Fluid\View\Exception\InvalidSectionException`
* :php:`TYPO3\CMS\Fluid\View\Exception\InvalidTemplateResourceException`
* :php:`TYPO3\CMS\InfoPagetsconfig\Controller\InfoPageTyposcriptConfigController`
* :php:`TYPO3\CMS\Lang\LanguageService`
* :php:`TYPO3\CMS\Lowlevel\Command\WorkspaceVersionRecordsCommand`
* :php:`TYPO3\CMS\Lowlevel\View\ConfigurationView`
* :php:`TYPO3\CMS\Recordlist\RecordList`
* :php:`TYPO3\CMS\Saltedpasswords\Exception\InvalidSaltException`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\AbstractSalt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BcryptSalt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\ComposedSaltInterface`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Md5Salt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\SaltFactory`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\SaltInterface`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt`
* :php:`TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt`
* :php:`TYPO3\CMS\Saltedpasswords\SaltedPasswordsService`
* :php:`TYPO3\CMS\Saltedpasswords\Utility\ExensionManagerConfigurationUtility`
* :php:`TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility`
* :php:`TYPO3\CMS\Sv\AbstractAuthenticationService`
* :php:`TYPO3\CMS\Sv\AuthenticationService`
* :php:`TYPO3\CMS\Sv\Report\ServicesListReport`
* :php:`TYPO3\CMS\T3editor\CodeCompletion`
* :php:`TYPO3\CMS\T3editor\TypoScriptReferenceLoader`
* :php:`TYPO3\CMS\Version\DataHandler\CommandMap`
* :php:`TYPO3\CMS\Version\Dependency\DependencyEntityFactory`
* :php:`TYPO3\CMS\Version\Dependency\DependencyResolver`
* :php:`TYPO3\CMS\Version\Dependency\ElementEntity`
* :php:`TYPO3\CMS\Version\Dependency\ElementEntityProcessor`
* :php:`TYPO3\CMS\Version\Dependency\EventCallback`
* :php:`TYPO3\CMS\Version\Dependency\ReferenceEntity`
* :php:`TYPO3\CMS\Version\Hook\DataHandlerHook`
* :php:`TYPO3\CMS\Version\Hook\PreviewHook`
* :php:`TYPO3\CMS\Version\Utility\WorkspacesUtility`


The following PHP class methods that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->compileStoreDat()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->doProcessData()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->getNewIconMode()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->openInNewWindowLink()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->setDocument()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->shortCutLink()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->initializeAdminPanel()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->initializeFrontendEdit()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->isFrontendEditingActive()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->displayAdminPanel()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->isAdminPanelVisible()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->checkBackendAccessSettingsFromInitPhp()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extPageReadAccess()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extGetTreeList()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extGetLL()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convArray()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convCaseFirst()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->crop()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->entities_to_utf8()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parse_charset()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char2byte_pos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_to_entities()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->__construct()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->configure()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->createApplicationContext()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->checkIfEssentialConfigurationExists()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->defineTypo3RequestTypes()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->disableCoreCaches()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->getEarlyInstance()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->getEarlyInstances()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->getInstance()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->initializeCachingFramework()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->initializePackageManagement()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->loadConfigurationAndInitialize()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->populateLocalConfiguration()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->setEarlyInstance()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->setFinalCachingFrameworkCacheConfiguration()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->setRequestType()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->usesComposerClassLoading()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash->getOptions()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash->setOptions()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash->getOptions()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash->setOptions()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->getHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->getMaxHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->getMinHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->getSaltLength()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->getSetting()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->setHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->setMaxHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishSalt->setMinHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->getSetting()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->getSaltLength()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getMaxHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getMinHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getSaltLength()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getSetting()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->setHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->setMaxHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->setMinHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getMaxHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getMinHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getSaltLength()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getSetting()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->setHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->setMaxHashCount()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->setMinHashCount()`
* :php:`TYPO3\CMS\Core\Package\PackageManager->injectDependencyResolver()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->getFileName()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->getFromMPmap()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->init()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->initMPmap_create()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->linkData()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->printTitle()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->_GETset()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->arrayToLogString()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->clientInfo()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->deprecationLog()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->devLog()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->getDeprecationLogFileName()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->getHostname()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->getUserObj()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->initSysLog()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->llXmlAutoFileName()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->logDeprecatedFunction()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->logDeprecatedViewHelperAttribute()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->sysLog()`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->unQuoteFilenames()`
* :php:`TYPO3\CMS\Extbase\Core\Bootstrap->configureObjectManager()`
* :php:`TYPO3\CMS\Extbase\Service\EnvironmentService->isEnvironmentInCliMode`
* :php:`TYPO3\CMS\Fluid\Core\Widget\Bootstrap->configureObjectManager()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->addParams()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->calcIntExplode()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->currentPageUrl()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->enableFields()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->filelink()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->filelist()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->typolinkWrap()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_addParams()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_filelink()`
* :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->stdWrap_filelist()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addTempContentHttpHeaders()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkAlternativeIdMethods()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkPageForMountpointRedirect()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkPageForShortcutRedirect()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkPageUnavailableHandler()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->connectToDB()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->convertCharsetRecursivelyToUtf8()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->convPOSTCharset()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->domainNameMatchesCurrentRequest()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getDomainDataForPid()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getDomainNameForPid()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getLLL()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageShortcut()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getUniqueId()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->handleDataSubmission()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->hook_eofe()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initFEuser()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initializeBackendUser()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initializeRedirectUrlHandlers()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initLLvars()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initTemplate()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->makeCacheHash()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->mergingWithGetVars()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageErrorHandler()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageNotFoundAndExit()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageNotFoundHandler()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageUnavailableAndExit()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageUnavailableHandler()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->previewInfo()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->processOutput()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->readLLfile()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->redirectToCurrentPage()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->redirectToExternalUrl()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sendCacheHeaders()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sendHttpHeadersDirectly()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setCSS()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->storeSessionData()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getFirstWebPage()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getDomainStartPage()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRootLine()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRecordsByField()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->deleteClause()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->checkWorkspaceAccess()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getFileReferences()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->checkExtObj()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->checkSubExtObj()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extObjContent()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extObjHeader()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->getExtObjContent()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->getExternalItemConfig()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->handleExternalFunctionValue()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->menuConfig()`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->mergeExternalItems()`


The following PHP static class methods that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getOriginalTranslationTable()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypes()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::storeHash()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getHash()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getListGroupNames()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getPidForModTSconfig()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getDomainStartPage()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::shortcutExists()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::determineSaltingHashingMethod()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::getSaltingInstance()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::setPreferredHashingMethod()`


The following methods changed signature according to previous deprecations in v9 at the end of the argument list:

* :php:`TYPO3\CMS\Backend\Http\RouteDispatcher->dispatch()` - Second argument dropped
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig()` - Second and third argument dropped
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->conv()` - Fourth argument dropped
* :php:`TYPO3\CMS\Core\Core\Bootstrap->checkIfEssentialConfigurationExists()` - First argument mandatory
* :php:`TYPO3\CMS\Core\Core\Bootstrap->populateLocalConfiguration()` - First argument mandatory
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Http\Dispatcher->dispatch()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Package\PackageManager->__construct()` - First argument mandatory
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->explodeUrl2Array()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->getUrl()` - Third argument must be an array of arrays if given
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->mkdir_deep()` - Second argument dropped
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRawRecord()` - Fourth argument dropped
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->__construct()` - Fourth argument unused
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->calculateLinkVars()` - First argument mandatory
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->preparePageContentGeneration()` - First argument mandatory



The following public class properties have been dropped:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->cacheCmd`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->content`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->doc`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->MCONF`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->popViewId_addParams`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->redirect`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->template`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extAdmEnabled`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->adminPanel`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->frontendEdit`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extAdminConfig`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->synonyms`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->allowedPaths`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->debug`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->fileCache`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->frames`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->MPmap`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->whereClause`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->activeUrlHandlers`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->ADMCMD_preview_BEUSER_uid`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->altPageTitle`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->beUserLogin`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->debug`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->gr_list`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->lang`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->loginUser`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->MP_defaults`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->page_cache_reg1`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->showHiddenPage`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->showHiddenRecords`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->siteScript`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_content`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_contentOL`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_mode`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_uid`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->workspacePreview`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->error_getRootLine_failPid`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->error_getRootLine`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->versioningPreview`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->workspaceCache`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->CMD`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->content`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->doc`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extClassConf`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->extObj`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->MCONF`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->MOD_MENU`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->MOD_SETTINGS`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modMenu_dontValidateList`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modMenu_setDefaultList`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modMenu_type`
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->modTSconfig`
* :php:`TYPO3\CMS\Impexp\Export->maxFileSize`
* :php:`TYPO3\CMS\Impexp\Export->maxRecordSize`
* :php:`TYPO3\CMS\Impexp\Export->maxExportSize`


The following class methods have changed visibility:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->closeDocument()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->compileForm()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->fixWSversioningInEditConf()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->getLanguages()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->getRecordForEdit()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->init()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->languageSwitch()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->localizationRedirect()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->main()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->makeEditForm()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->preInit()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->processData()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->flattenSetup()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->mergeConstantsFromPageTSconfig()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->prependStaticExtra()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->processIncludes()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->substituteConstants()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->versionOL()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->clearPageCacheContent_pidList()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->contentStrReplace()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->realPageCacheContent()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setPageCacheContent()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setSysLastChanged()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->tempPageCacheContent()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->addRecordsForPid()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->checkUpload()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->exec_listQueryPid()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->exportData()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->filterPageIds()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->getTableSelectOptions()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->importData()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->init()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->main()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->makeAdvancedOptionsForm()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->makeConfigurationForm()` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->makeSaveForm()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->checkExtObj()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->checkSubExtObj()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->clearCache()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->extObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->extObjHeader()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->getExternalItemConfig()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->getExtObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->init()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->main()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->menuConfig()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->mergeExternalItems()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->setInPageArray()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->initialize_editor()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->modMenu()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->initialize_editor()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->initialize_editor()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tableRowData()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->initialize_editor()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->modMenu()` changed from public to protected


The following class properties have changed visibility:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->closeDoc` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->cmd` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->columnsOnly` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->defVals` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->docDat` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->docHandler` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->dontStoreDocumentRef` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->doSave` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->editconf` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->errorC` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->firstEl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->mirror` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->modTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->newC` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->noView` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->overrideVals` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->pageinfo` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->popViewId` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->recTitle` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->retUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->returnEditConf` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->returnNewPageId` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->R_URI` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->R_URL_getvars` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->R_URL_parts` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->storeArray` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->storeTitle` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->storeUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->storeUrlMd5` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->uc` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->viewId` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->viewId_addParams` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->viewUrl` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->eucBasedSets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->noCharByteVal` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parsedCharsets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->toASCII` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->twoByteSets` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->absoluteRootLine` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->matchAll` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->nextLevel` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->outermostRootlineIndexWithTemplate` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->rootId` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->rowSum` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sectionsMatch` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->simulationHiddenOrTime` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sitetitle` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cacheContentFlag` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cacheExpires` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cacheTimeOutDefault` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isClientCachable` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->loginAllowedInBranch` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->loginAllowedInBranch_mode` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->no_cacheBeforePageGen` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageAccessFailureHistory` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pageCacheTags` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->pagesTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->tempContent` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->uniqueCounter` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->uniqueString` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->sys_language_uid` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->versioningWorkspaceId` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->id` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->pageinfo` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->access` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->CMD` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->content` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->edit` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->extObj` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->id` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->MCONF` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->modMenu_type` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->modTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->pageinfo` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->sObj` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController->textExtensions` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->function_key` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->localLangFile` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TemplateAnalyzerModuleFunctionController->pObj` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->function_key` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->localLangFile` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController->pObj` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->function_key` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->localLangFile` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->pObj` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController->tce_processed` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->function_key` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->localLangFile` changed from public to protected
* :php:`TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController->pObj` changed from public to protected


The following scheduler tasks have been removed:

* EXT:extbase Task
* EXT:workspaces CleanupPreviewLinkTask
* EXT:workspaces AutoPublishTask


The following user TSconfig options have been dropped:

* Prefix `mod.` to override page TSconfig is ignored
* `TSFE.frontendEditingController` to override the frontend editing controller in EXT:feedit


The following TypoScript options have been dropped:

* `config.concatenateJsAndCss`
* `config.titleTagFunction`
* `config.typolinkCheckRootline`
* `config.tx_extbase.objects`
* `config.USERNAME_substToken`
* `config.USERUID_substToken`
* `page.javascriptLibs`
* `page.javascriptLibs.jQuery`
* `plugin.tx_%plugin%.objects`
* `stdWrap.addParams`
* `stdWrap.filelink`
* `stdWrap.filelist`


The following constants have been dropped:

* :php:`PATH_typo3`
* :php:`PATH_typo3conf`
* :php:`T3_ERR_SV_GENERAL`
* :php:`T3_ERR_SV_FILE_NOT_FOUND`
* :php:`T3_ERR_SV_FILE_READ`
* :php:`T3_ERR_SV_FILE_WRITE`
* :php:`T3_ERR_SV_NO_INPUT`
* :php:`T3_ERR_SV_NOT_AVAIL`
* :php:`T3_ERR_SV_PROG_FAILED`
* :php:`T3_ERR_SV_PROG_NOT_FOUND`
* :php:`T3_ERR_SV_WRONG_SUBTYPE`
* :php:`TYPO3_URL_CONSULTANCY`
* :php:`TYPO3_OS`
* :php:`TYPO3_URL_CONTRIBUTE`
* :php:`TYPO3_URL_DOCUMENTATION`
* :php:`TYPO3_URL_DOCUMENTATION_TSCONFIG`
* :php:`TYPO3_URL_DOCUMENTATION_TSREF`
* :php:`TYPO3_URL_DOWNLOAD`
* :php:`TYPO3_URL_MAILINGLISTS`
* :php:`TYPO3_URL_SECURITY`
* :php:`TYPO3_URL_SYSTEMREQUIREMENTS`


The following class constants have been dropped:

* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::ITOA64`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::MAX_HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash::MIN_HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash::ITOA64`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::ITOA64`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::MAX_HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash::MIN_HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::ITOA64`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::MAX_HASH_COUNT`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash::MIN_HASH_COUNT`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING`


The following constants have been set to protected:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_CLEAR_ALL`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_DEFAULT`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_NO_REDIRECT`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_REDIRECT`


The following global options are ignored:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']`


The following language files and aliases have been removed:

* :php:`EXT:saltedpasswords/Resources/Private/Language/locallang.xlf`
* :php:`EXT:saltedpasswords/Resources/Private/Language/locallang_em.xlf`


The following global variables have been removed:

* :php:`$GLOBALS['TYPO3_LOADED_EXT']`


The following hooks have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc']`


The following feature is now always enabled:

* :php:`simplifiedControllerActionDispatching` - Backend controller actions do not receive a prepared response object anymore


The following features have been removed:

* Migration from v4 to v5 PackagesStates.php
* Backend modules validated against special GET/POST `M` parameter
* `eID` script targets cannot define a script path anymore:
  `$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['my_eID'] = 'EXT:benni/Scripts/download.php'` will not work anymore.
  Instead, they must contain a target (callable, class/method, function).


The following database fields have been removed:

* `index_phash.data_page_reg1`


The following php doc annotations have been removed:

* `@cascade`
* `@cli`
* `@flushesCaches`
* `@ignorevalidation`
* `@inject`
* `@internal`
* `@lazy`
* `@transient`
* `@validate`

Impact
======

Instantiating or requiring the PHP classes, calling the PHP methods directly, will result in PHP fatal errors.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
