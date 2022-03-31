.. include:: /Includes.rst.txt

===================================================
Breaking: #87193 - Deprecated functionality removed
===================================================

See :issue:`87193`

Description
===========

The following PHP classes that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Adminpanel\View\AdminPanelView`
* :php:`TYPO3\CMS\Backend\Controller\LoginFramesetController`
* :php:`TYPO3\CMS\Backend\Form\Form\FieldWizard\FileThumbnails`
* :php:`TYPO3\CMS\Backend\Form\Form\FieldWizard\FileTypeList`
* :php:`TYPO3\CMS\Backend\Form\Form\FieldWizard\FileUpload`
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
* :php:`TYPO3\CMS\Core\Site\Entity\PseudoSite`
* :php:`TYPO3\CMS\Core\Site\PseudoSiteFinder`
* :php:`TYPO3\CMS\Core\TypoScript\ConfigurationForm`
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
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException`
* :php:`TYPO3\CMS\Extbase\Scheduler\FieldProvider`
* :php:`TYPO3\CMS\Extbase\Scheduler\Task`
* :php:`TYPO3\CMS\Extbase\Scheduler\TaskExecutor`
* :php:`TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase`
* :php:`TYPO3\CMS\Extbase\Validation\Validator\RawValidator`
* :php:`TYPO3\CMS\Extensionmanager\Command\ExtensionCommandController`
* :php:`TYPO3\CMS\Form\Domain\Model\FormElements\GridContainer`
* :php:`TYPO3\CMS\Frontend\ContentObject\FileContentObject`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\ImageMenuContentObject`
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
* :php:`TYPO3\CMS\Core\Database\PdoHelper`
* :php:`TYPO3\CMS\Core\IO\PharStreamWrapper`
* :php:`TYPO3\CMS\Core\IO\PharStreamWrapperException`
* :php:`TYPO3\CMS\Core\History\RecordHistory`
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
* :php:`TYPO3\CMS\Saltedpasswords\SaltedPasswordService`
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

* :php:`TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->foreignTranslationTable()`
* :php:`TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->getTranslationTable()`
* :php:`TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider->isTranslationInOwnTable()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->evaluateCondition($string)`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getVariable($var)`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getGroupList()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getPage()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->isNewPageWithPageId($pageId)`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->determineRootline()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getUserId()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->isUserLoggedIn()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->isAdminUser()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getBackendUserAuthentication()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->determinePageId()`
* :php:`TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getPageIdByRecord()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->main()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->main()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->removeInvalidElements()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->wizard_appendWizards()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->wizard_getItem()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->wizard_getGroupHeader()`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->wizardArray()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->compileStoreDat()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->doProcessData()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->getNewIconMode()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->openInNewWindowLink()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->setDocument()`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->shortCutLink()`
* :php:`TYPO3\CMS\Backend\Controller\EditFileController->getButtons()`
* :php:`TYPO3\CMS\Backend\Controller\File\FileController->finish()`
* :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController->main()`
* :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController->renderUploadForm()`
* :php:`TYPO3\CMS\Backend\Controller\File\RenameFileController->main()`
* :php:`TYPO3\CMS\Backend\Controller\File\ReplaceFileController->main()`
* :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->initPage()`
* :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->main()`
* :php:`TYPO3\CMS\Backend\Controller\LoginController->main()`
* :php:`TYPO3\CMS\Backend\Controller\LoginController->makeInterfaceSelectorBox()`
* :php:`TYPO3\CMS\Backend\Controller\LogoutController->logout()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->isTableAllowedForThisPage()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->linkWrap()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->main()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->pagesonly()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->regularNew()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->showNewRecLink()`
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->sortNewRecordsByConfig()`
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->main()`
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->initClipboard()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->main()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\EditController->main()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\ListController->main()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->cfgArray2CfgString()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->cfgString2CfgArray()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->changeFunc()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->getConfigCode()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->getTableHTML()`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->tableWizard()`
* :php:`TYPO3\CMS\Backend\Controller\UserSettingsController->process()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->initializeAdminPanel()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->initializeFrontendEdit()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->isFrontendEditingActive()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->displayAdminPanel()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->isAdminPanelVisible()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->checkBackendAccessSettingsFromInitPhp()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extPageReadAccess()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extGetTreeList()`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extGetLL()`
* :php:`TYPO3\CMS\Backend\Routing\UriBuilder->buildUriFromModule()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->addStyleSheet()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->formWidth()`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->xUaCompatible()`
* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->icons()`
* :php:`TYPO3\CMS\Backend\Template\ModuleTemplate->loadJavascriptLib()`
* :php:`TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView->ext_isLinkable()`
* :php:`TYPO3\CMS\Backend\Tree\View\AbstractTreeView->setDataFromArray()`
* :php:`TYPO3\CMS\Backend\Tree\View\AbstractTreeView->setDataFromTreeArray()`
* :php:`TYPO3\CMS\Backend\Tree\View\PagePositionMap->getModConfig()`
* :php:`TYPO3\CMS\Backend\View\PageLayoutView->languageFlag()`
* :php:`TYPO3\CMS\Core\Authentication\AbstractAuthenticationService->compareUident()`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->compareUident()`
* :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication->fetchUserRecord()`
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->addTScomment()`
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getTSConfigProp()`
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getTSConfigVal()`
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->isPSet()`
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->simplelog()`
* :php:`TYPO3\CMS\Core\Cache\PhpFrontend->getByTag()`
* :php:`TYPO3\CMS\Core\Cache\VariableFrontend->getByTag()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convArray()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convCaseFirst()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->crop()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->entities_to_utf8()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parse_charset()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char2byte_pos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_to_entities()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->strictSyntaxEnabled()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->normalizeExpression($expression)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->evaluateConditionCommon($key, $value)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->evaluateCustomDefinedCondition($condition)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->parseUserFuncArguments($arguments)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->getVariableCommon(array $vars)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->compareNumber($test, $leftValue)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->searchStringWildcard($haystack, $needle)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->getGlobal($var, $source = null)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->evaluateCondition($string)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->getVariable($name)`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->getGroupList()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->determinePageId()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->getPage()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->determineRootline()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->getUserId()`
* :php:`TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher->isUserLoggedIn()`
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
* :php:`TYPO3\CMS\Core\Database\ReferenceIndex->createEntryData_fileRels()`
* :php:`TYPO3\CMS\Core\Database\ReferenceIndex->createEntryDataForFileRelationsUsingRecord()`
* :php:`TYPO3\CMS\Core\Database\ReferenceIndex->destPathFromUploadFolder()`
* :php:`TYPO3\CMS\Core\Database\ReferenceIndex->getRelations_procFiles()`
* :php:`TYPO3\CMS\Core\Database\ReferenceIndex->setReferenceValue_fileRels()`
* :php:`TYPO3\CMS\Core\Database\SoftReferenceIndex->getPageIdFromAlias()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->checkValue_group_select_file()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->copyRecord_fixRTEmagicImages()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->copyRecord_procFilesRefs()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->deleteRecord_flexFormCallBack()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->extFileFields()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->extFileFunctions()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->getTCEMAIN_TSconfig()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->newlog2()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->process_uploads_traverseArray()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->removeRegisteredFiles()`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->resorting()`
* :php:`TYPO3\CMS\Core\Imaging\GraphicalFunctions->init()`
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->transformStyledATags()`
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_links_rte()`
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->urlInfoForLinkTags()`
* :php:`TYPO3\CMS\Core\Package\PackageManager->injectDependencyResolver()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->addMetaTag()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->disableConcatenateFiles()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->enableConcatenateFiles()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getConcatenateFiles()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->loadJquery()`
* :php:`TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver->getCharsetConversion()`
* :php:`TYPO3\CMS\Core\Resource\ResourceStorage->dumpFileContents()`
* :php:`TYPO3\CMS\Core\Service\AbstractService->devLog()`
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
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Argument->getValidationResults()`
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\Arguments->getValidationResults()`
* :php:`TYPO3\CMS\Extbase\Service\EnvironmentService->isEnvironmentInCliMode()`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility->processDatabaseUpdates()`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility->updateDbWithExtTablesSql()`
* :php:`TYPO3\CMS\Fluid\Core\Widget\Bootstrap->configureObjectManager()`
* :php:`TYPO3\CMS\Filelist\FileFacade->getIcon()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->evaluateCondition($string)`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getVariable($var)`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getSessionVariable(string $var)`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getGroupList()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->determinePageId()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getPage()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->determineRootline()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getUserId()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->isUserLoggedIn()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getTypoScriptFrontendController()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getCurrentSiteLanguage()`
* :php:`TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher->getCurrentSite()`
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
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_beforeAllWrap()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_beforeLinking()`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_init()`
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
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getPageIdFromAlias()`
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
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->extObjHeader()`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->checkSubExtObj()`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->getModuleTemplate()`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->checkExtObj()`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->extObjContent()`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->checkExtObj()`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->extObjContent()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->checkExtObj()`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->getSystemLanguages()`
* :php:`TYPO3\CMS\Install\Service\CoreVersionService->getDownloadBaseUrl()`
* :php:`TYPO3\CMS\Install\Service\CoreVersionService->isYoungerPatchDevelopmentReleaseAvailable()`
* :php:`TYPO3\CMS\Install\Service\CoreVersionService->getYoungestPatchDevelopmentRelease()`
* :php:`TYPO3\CMS\Install\Service\CoreVersionService->updateVersionMatrix()`
* :php:`TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck->getFileFields()`
* :php:`TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck->testFileRefs()`
* :php:`TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck->whereIsFileReferenced()`
* :php:`TYPO3\CMS\Recordlist\Controller\ElementBrowserController->main()`
* :php:`TYPO3\CMS\Rsaauth\RsaEncryptionEncode[r->getRsaPublicKeyAjaxHandler()`
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->getFormProtection()`
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->simulateUser()`


The following PHP static class methods that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getDomainStartPage()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getHash()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getListGroupNames()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getOriginalTranslationTable()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getPidForModTSconfig()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getTCAtypes()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::shortcutExists()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::storeHash()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::determineSaltingHashingMethod()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::getSaltingInstance()`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::setPreferredHashingMethod()`
* :php:`TYPO3\CMS\Core\Context\LanguageAspectFactory::createFromTypoScript()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::configureModule()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionKeyByPrefix()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles()`
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath()`
* :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::getActionMethodParameters()`


The following PHP methods have been additionally deprecated and are a no-op now:

* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->process_uploads()`


The following methods changed signature according to previous deprecations in v9 at the end of the argument list:

* :php:`TYPO3\CMS\Backend\Http\RouteDispatcher->dispatch()` - Second argument dropped
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig()` - Second and third argument dropped
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->modAccess()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->getTSConfig()` - First and second argument dropped
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->conv()` - Fourth argument dropped
* :php:`TYPO3\CMS\Core\Core\Bootstrap->checkIfEssentialConfigurationExists()` - First argument mandatory
* :php:`TYPO3\CMS\Core\Core\Bootstrap->populateLocalConfiguration()` - First argument mandatory
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->getHashedPassword()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Http\Dispatcher->dispatch()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Package\PackageManager->__construct()` - First argument mandatory
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_AtagToAbs()` - Second argument dropped and protected
* :php:`TYPO3\CMS\Core\Page\PageRenderer::addInlineLanguageLabelArray()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->explodeUrl2Array()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->getUrl()` - Third argument must be an array of arrays if given
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility->mkdir_deep()` - Second argument dropped
* :php:`TYPO3\CMS\Core\Utility\RootlineUtility->__construct()` - Third optional argument now has to be Context object or null
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRawRecord()` - Fourth argument dropped
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->__construct()` - Fourth argument unused
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->calculateLinkVars()` - First argument mandatory
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->preparePageContentGeneration()` - First argument mandatory
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->main()` - First argument mandatory


The following public class properties have been dropped:

* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->access`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->pageInfo`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->table`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->type`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->uid`
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->doc`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->cacheCmd`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->content`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->doc`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->MCONF`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->popViewId_addParams`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->redirect`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController->template`
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->content`
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->title`
* :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController->title`
* :php:`TYPO3\CMS\Backend\Controller\File\RenameFileController->content`
* :php:`TYPO3\CMS\Backend\Controller\File\RenameFileController->title`
* :php:`TYPO3\CMS\Backend\Controller\File\ReplaceFileController->content`
* :php:`TYPO3\CMS\Backend\Controller\File\ReplaceFileController->doc`
* :php:`TYPO3\CMS\Backend\Controller\File\ReplaceFileController->title`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->content`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\ListController->id`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\ListController->P`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\ListController->pid`
* :php:`TYPO3\CMS\Backend\Controller\Wizard\ListController->table`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extAdmEnabled`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->adminPanel`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->frontendEdit`
* :php:`TYPO3\CMS\Backend\FrontendBackendUserAuthentication->extAdminConfig`
* :php:`TYPO3\CMS\Backend\Template\DocumentTemplate->hasDocheader`
* :php:`TYPO3\CMS\Backend\Tree\View\AbstractTreeView->data`
* :php:`TYPO3\CMS\Backend\Tree\View\AbstractTreeView->dataLookup`
* :php:`TYPO3\CMS\Backend\Tree\View\AbstractTreeView->subLevelID`
* :php:`TYPO3\CMS\Backend\Tree\View\PagePositionMap->getModConfigCache`
* :php:`TYPO3\CMS\Backend\Tree\View\PagePositionMap->modConfigStr`
* :php:`TYPO3\CMS\Backend\View\PageLayoutView->languageIconTitles`
* :php:`TYPO3\CMS\Backend\View\PageLayoutView->translateTools`
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->userTS_dontGetCached`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->synonyms`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->alternativeFileName`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->alternativeFilePath`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->autoVersioningUpdate`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->bypassFileHandling`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->copiedFileMap`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->filefunc`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->removeFilesStore`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->RTEmagic_copyIndex`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->updateModeL10NdiffData`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->updateModeL10NdiffDataClear`
* :php:`TYPO3\CMS\Core\DataHandling\DataHandler->uploadedFileArray`
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
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->debug`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->GMENU_fixKey`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->imgNameNotRandom`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->imgNamePrefix`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->INPfixMD5`
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->nameAttribute`
* :php:`TYPO3C\MS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMfreezePrefix`
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
* :php:`TYPO3\CMS\IndexedSearch\Lexer->csObj`
* :php:`TYPO3\CMS\IndexedSearch\Indexer->csObj`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->CMD`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->doc`
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->MCONF`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->extObj`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->function_key`
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->localLangFile`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->extObj`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->function_key`
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->localLangFile`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->extClassConf`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->extObj`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->function_key`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->localLangFile`
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->pObj`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->addProperty`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->setModelType`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->getModelType`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->setUuidPropertyName`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->getUuidPropertyName`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->markAsIdentityProperty`
* :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema->getIdentityProperties`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getClassTagsValues`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getClassTagValues`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getClassPropertyNames`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->hasMethod`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getMethodTagsValues`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getMethodParameters`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getPropertyTagsValues`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->getPropertyTagValues`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->isClassTaggedWith`
* :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService->isPropertyTaggedWith`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver->buildMethodArgumentsValidatorConjunctions`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver->buildSubObjectValidator`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver->parseValidatorAnnotation`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver->parseValidatorOptions`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver->unquoteString`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver->getMethodValidateAnnotations`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->doc`
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->imagemode`
* :php:`TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList->newWizards`



The following class methods have changed visibility:

* :php:`TYPO3\CMS\Backend\Controller\BackendController->render()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->getLabelForTableColumn` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->init()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController->main()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->init()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->init()` changed from public to protected
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
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->main()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->main()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\FileController->initClipboard()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\FileController->main()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->clearCache()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->contentIsNotLockedForEditors()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->getLocalizedPageTitle()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->getModuleTemplate()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->getNumberOfHiddenElements()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->init()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->local_linkThisScript()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->main()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->menuConfig()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->pageIsNotLockedForEditors()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->renderContent()` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->init()` changed from public to protected
* :php:`TYPO3\CMS\Beuser\Controller\BackendUserController->initializeView()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->base64Encode()` changed from public to protected
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash->isValidSalt()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_images_db()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_links_db()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_transform_db()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_transform_rte()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->HTMLcleaner_db()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->getKeepTags()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->divideIntoLines()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->setDivTags()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->getWHFromAttribs()` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_AtagToAbs()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->flattenSetup()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->mergeConstantsFromPageTSconfig()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->prependStaticExtra()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->processIncludes()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->substituteConstants()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->versionOL()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->error()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->nextDivider()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->parseSub()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->regHighLight()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->rollParseSub()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->setVal()` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->syntaxHighlight_print()` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->menuConfig()` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->initializeView()` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->initializeIndexAction()` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->indexAction()` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->missingFolderAction()` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->searchAction()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->accessKey()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->changeLinksForAccessRestrictedPages()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getBannedUids()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getDoktypeExcludeWhere()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getMPvar()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->getPageTitle()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isActive()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isCurrent()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isItemState()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isNext()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->isSubMenu()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->link()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->menuTypoLink()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->procesItemStates()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->setATagParts()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->subMenu()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->userProcess()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_afterLinking()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->extProc_finish()` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject->getBeforeAfter()` changed from public to protected
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
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->checkExtObj()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->extObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->getExternalItemConfig()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->getExtObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->init()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->main()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->menuConfig()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->mergeExternalItems()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->modMenu()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->modMenu()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->extObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->getContentElementCount()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->getLangStatus()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->renderL10nTable()` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\TranslationStatusController->modMenu()` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->extObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController->getDisplayedLinkHandlerId()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController->renderLinkAttributeFields()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->init()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->menuConfig()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->clearCache()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->main()` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->getModuleTemplate()` changed from public to protected
* :php:`TYPO3\CMS\Reports\Controller\ReportController->detailAction()` changed from public to protected
* :php:`TYPO3\CMS\Reports\Controller\ReportController->indexAction()` changed from public to protected
* :php:`TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController->renderLinkAttributeFields()` changed from public to protected
* :php:`TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController->getPageConfigLabel()` changed from public to protected
* :php:`TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController->getDisplayedLinkHandlerId()` changed from public to protected
* :php:`TYPO3\CMS\Scheduler\Controller\SchedulerModuleController->addMessage()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->menuConfig()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->mergeExternalItems()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->handleExternalFunctionValue()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->getExternalItemConfig()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->main()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->urlInIframe()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->extObjHeader()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->checkSubExtObj()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->checkExtObj()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->extObjContent()` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->getExtObjContent()` changed from public to protected
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

* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->input_moveUid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->makeCopy` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->moveUid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->page_id` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->R_URI` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->sys_language` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\MoveElementController->table` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->access` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->config` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->colPos` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->id` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->modTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->R_URI` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->sys_language` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController->uid_pid` changed from public to protected
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
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->folderNumber` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->number` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\CreateFolderController->target` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->origTarget` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->target` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->title` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\EditFileController->doc` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\FileUploadController->target` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\RenameFileController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\RenameFileController->target` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\ReplaceFileController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\File\ReplaceFileController->uid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->foldertree` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->currentSubScript` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController->cMR` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->allowedNewTables` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->allowedNewTables_pid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->code` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->deniedNewTables` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->deniedNewTables_pid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->newContentInto` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->newPagesAfter` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->newPagesInto` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->pageinfo` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->pagesOnly` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->pidInfo` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->R_URI` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->tRows` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->web_list_modTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\NewRecordController->web_list_modTSconfig_pid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->activeColPosList` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->CALC_PERMS` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->clear_cache` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->colPosList` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->current_sys_language` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->descrTable` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->EDIT_CONTENT` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->imagemode` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->MCONF` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->MOD_MENU` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->modSharedTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->modTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->pointer` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->popView` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->search_field` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->search_levels` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\PageLayoutController->showLimit` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->cacheCmd` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->CB` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->cmd` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->data` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->flags` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->mirror` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->redirect` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\SimpleDataHandlerController->tce` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->id` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->P` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->pid` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->processDataFlag` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->returnEditConf` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\AddController->table` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\EditController->doClose` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\EditController->P` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->content` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->inputStyle` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->xmlStorage` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->numNewRows` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->colsFieldName` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->P` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->TABLECFG` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->tableParsing_quote` changed from public to protected
* :php:`TYPO3\CMS\Backend\Controller\Wizard\TableController->tableParsing_delimiter` changed from public to protected
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->checkWorkspaceCurrent_cache` changed from public to protected
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->TSdataArray` changed from public to protected
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->userTS` changed from public to protected
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->userTSUpdated` changed from public to protected
* :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->userTS_text` has been removed
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->eucBasedSets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->noCharByteVal` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parsedCharsets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->toASCII` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->twoByteSets` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->allowedClasses` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->blockElementList` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->elRef` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->getKeepTags_cache` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->procOptions` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->recPid` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_transform_db_safecounter` changed from public to protected
* :php:`TYPO3\CMS\Core\Html\RteHtmlParser->tsConfig` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->absoluteRootLine` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->matchAll` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->nextLevel` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->outermostRootlineIndexWithTemplate` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->rootId` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->rowSum` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sectionsMatch` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->simulationHiddenOrTime` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sitetitle` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->commentSet` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->highLightBlockStyles` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->highLightBlockStyles_basecolor` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->highLightData` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->highLightData_bracelevel` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->highLightStyles` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->inBrace` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->lastComment` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->lastConditionTrue` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->multiLineEnabled` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->multiLineObject` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->multiLineValue` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->raw` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->rawP` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser->syntaxHighLight` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->MOD_MENU` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->MOD_SETTINGS` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->doc` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->id` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->pointer` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->table` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->imagemode` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->cmd` changed from public to protected
* :php:`TYPO3\CMS\Filelist\Controller\FileListController->filelist` changed from public to protected
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
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->alternativeMenuTempArray` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->alwaysActivePIDlist` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->conf` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->doktypeExcludeList` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->entryLevel` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->hash` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->id` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->I` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->mconf` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->menuArr` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->menuNumber` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->nextActive` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->MP_array` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->result` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->rL_uidRegister` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->spacerIDList` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->sys_page` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->tmpl` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMcObj` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMextraScript` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMmenuItems` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMresult` changed from public to protected
* :php:`TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject->WMsubmenuObjSuffixes` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->sys_language_uid` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->versioningWorkspaceId` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->id` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->pageinfo` changed from public to protected
* :php:`TYPO3\CMS\Impexp\Controller\ImportExportController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->content` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->extObj` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->id` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modMenu_dontValidateList` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modMenu_setDefaultList` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modMenu_type` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->modTSconfig` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoModuleController->pObj` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\PageInformationController->pObj` changed from public to protected
* :php:`TYPO3\CMS\Info\Controller\InfoPageTyposcriptConfigController->pObj` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->pObj` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->doc` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->function_key` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->localLangFile` changed from public to protected
* :php:`TYPO3\CMS\Linkvalidator\Report\LinkValidatorReport->extObj` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->id` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->pointer` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->table` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->search_field` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->search_levels` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->showLimit` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->returnUrl` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->clear_cache` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->cmd` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->cmd_table` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->pageinfo` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->MOD_MENU` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->content` changed from public to protected
* :php:`TYPO3\CMS\Recordlist\Controller\RecordListController->body` changed from public to protected
* :php:`TYPO3\CMS\Scheduler\Controller\SchedulerModuleController->CMD` changed from public to protected
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->OLD_BE_USER` changed from public to protected
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->MOD_MENU` changed from public to protected
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->MOD_SETTINGS` changed from public to protected
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->content` changed from public to protected
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->overrideConf` changed from public to protected
* :php:`TYPO3\CMS\Setup\Controller\SetupModuleController->languageUpdate` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->MCONF` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->id` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->MOD_MENU` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->modMenu_type` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->modMenu_setDefaultList` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->modMenu_dontValidateList` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->content` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->perms_clause` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->CMD` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->extClassConf` changed from public to protected
* :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController->extObj` changed from public to protected
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


The following VieHelpers have changed:

* :php:`TYPO3\CMS\Form\ViewHelpers\TranslateElementErrorViewHelper`: The arguments `code`, `arguments` & `defaultValue` have been removed.


The following scheduler tasks have been removed:

* EXT:extbase Task
* EXT:workspaces CleanupPreviewLinkTask
* EXT:workspaces AutoPublishTask


The following user TSconfig options have been dropped:

* Prefix `mod.` to override page TSconfig is ignored
* `TSFE.frontendEditingController` to override the frontend editing controller in EXT:feedit
* `RTE.proc.keepPDIVattribs`
* `RTE.proc.dontRemoveUnknownTags_db`
* `options.clearCache.system`
* `TCEMAIN.previewDomain`


The following TypoScript options have been dropped:

* `config.concatenateJsAndCss`
* `config.defaultGetVars`
* `config.htmlTag_langKey`
* `config.htmlTag_dir`
* `config.language`
* `config.language_alt`
* `config.locale_all`
* `config.sys_language_isocode`
* `config.sys_language_isocode_default`
* `config.sys_language_mode`
* `config.sys_language_overlay`
* `config.sys_language_uid`
* `config.titleTagFunction`
* `config.tx_extbase.objects`
* `config.typolinkCheckRootline`
* `config.typolinkEnableLinksAcrossDomains`
* `config.USERNAME_substToken`
* `config.USERUID_substToken`
* `FILE`
* `page.javascriptLibs`
* `page.javascriptLibs.jQuery`
* `plugin.tx_%plugin%.objects`
* `stdWrap.addParams`
* `stdWrap.filelink`
* `stdWrap.filelist`
* `SVG.noscript`
* `SVG.value`
* `typolink.useCacheHash`
* `TMENU.beforeImg`
* `TMENU.afterImg`
* `GMENU`
* `GMENUITEMS`
* `IMGMENU`
* `IMGMENUITEMS`

The following TypoScript conditions have been dropped:

* `language`
* `IP`
* `hostname`
* `applicationContext`
* `hour`
* `minute`
* `month`
* `year`
* `dayofweek`
* `dayofmonth`
* `dayofyear`
* `usergroup`
* `loginUser`
* `page`
* `treeLevel`
* `PIDinRootline`
* `PIDupinRootline`
* `compatVersion`
* `globalVar`
* `globalString`
* `userFunc`

The following constants have been dropped:

* :php:`PATH_site`
* :php:`PATH_thisScript`
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
* :php:`TYPO3\CMS\Core\DataHandling\TableColumnSubType::FILE`
* :php:`TYPO3\CMS\Core\DataHandling\TableColumnSubType::FILE_REFERENCE`
* :php:`TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_NONE`
* :php:`TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE`
* :php:`TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver::PATTERN_MATCH_VALIDATORS`
* :php:`TYPO3\CMS\Extbase\Validation\ValidatorResolver::PATTERN_MATCH_VALIDATOROPTIONS`
* :php:`TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::PAGE_ALIAS_NOT_FOUND`


The following constants have been set to protected:

* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_CLEAR_ALL`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_DEFAULT`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_NO_REDIRECT`
* :php:`TYPO3\CMS\Backend\Controller\EditDocumentController::DOCUMENT_CLOSE_MODE_REDIRECT`


The following global options are ignored:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_statheader']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_accessdeniedheader']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['recursiveDomainSearch']`


The following language files and aliases have been removed:

* :php:`EXT:saltedpasswords/Resources/Private/Language/locallang.xlf`
* :php:`EXT:saltedpasswords/Resources/Private/Language/locallang_em.xlf`


The following global variables have been removed:

* :php:`$GLOBALS['TYPO3_LOADED_EXT']`


The following hooks have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['buildQueryParameters']`


The following hooks don't pass the class reference anymore:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']`


The following signals have been removed:

* :php:`TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService` signal `hasInstalledExtensions`
* :php:`TYPO3\CMS\Extensionmanager\Utility\InstallUtility` signal `tablesDefinitionIsBeingBuilt`


The following features are now always enabled:

* Extbase's :php:`consistentTranslationOverlayHandling` - Translations in Extbase are now always consistent
* :php:`simplifiedControllerActionDispatching` - Backend controller actions do not receive a prepared response object anymore
* :php:`unifiedPageTranslationHandling` - Page Translations are not within `pages_language_overlay` anymore
* TypoScript condition strict syntax - The feature toggle :php:`TypoScript.strictSyntax` has been dropped


The following features have been removed:

* Migration from v4 to v5 PackagesStates.php
* Backend modules validated against special GET/POST `M` parameter
* `eID` script targets cannot define a script path anymore:
  `$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['my_eID'] = 'EXT:benni/Scripts/download.php'` will not work anymore.
  Instead, they must contain a target (callable, class/method, function).
* TCA auto migration from core v6 to core v7 compatible TCA
* TCA auto migration from core v7 to core v8 compatible TCA
* TCA :php:`type='group'` with :php:`internal_type='file'` and :php:`internal_type='file_reference`
* Cache creation using :php:`\TYPO3\CMS\Cache\CacheManger` during :file:`ext_localconf.php` loading
* All install tool upgrade wizards upgrading from v7 to v8
* The array key :php:`uploadfolder` in extensions :file:`ext_emconf.php` files is obsolete and ignored.
* Standalone install tool entry point :file:`typo3/install/index.php` has been dropped, use :file:`typo3/install.php` instead
* INCLUDE_TYPOSCRIPT statements in typoscript using a `.txt` ending for a file that ends with `.typoscript` does not work any longer
* These variables are no longer declared in :file:`ext_tables.php` and :file:`ext_localconf.php` files: :php:`$_EXTKEY`, :php:`$_EXTCONF`,
  :php:`T3_SERVICES`, :php:`T3_VAR`, :php:`TYPO3_CONF_VARS`, :php:`TBE_MODULES`, :php:`TBE_MODULES_EXT`, :php:`TCA`,
  :php:`PAGES_TYPES`, :php:`TBE_STYLES`
* Frontend, Backend and standalone install tool users who did not log in for multiple core versions and still use a :php:`M$`
  prefixed password can not log in anymore. Auto converting those user passwords during first login has been dropped, those
  users need their password being manually recovered or reset.
* Extension :php:`rsaauth` has been dropped from core
* Extension :php:`feedit` has been dropped from core
* The extension :php:`taskcenter` and its add-on extension :php:`sys_action` have been dropped from core
* Translation :php:`locallang` references :php:`EXT:lang` to removed extension "lang" do not work any longer
* EXT:form: type GridContainer
* EXT:form: :yaml:`renderingOptions._isHiddenFormElement` and :yaml:`renderingOptions._isReadOnlyFormElement` are dropped
* :php:`$TBE_MODULES`: configuring a module via a custom "configureModuleFunction" is dropped
* CLI Command alias "lang:language:update" is dropped in favor of "language:update"
* Accessing or modifying :php:`$_GET`/:php:`$_POST` parameters during any PSR-15 middleware will not reflect any change during the actual Request processing anymore as it is overridden by the incoming PSR-7 request object, but overridden again when the RequestHandler is accessed
* Parsing of the legacy `<link>` tags which were migrated to `<a>` tags in Frontend is dropped

The following database tables have been removed:

* `sys_domain` - Use site configuration instead
* `pages_language_overlay` - Migrate to `pages` with the upgrade wizard


The following database fields have been removed:

* `pages.alias`
* `pages.t3ver_label`
* `index_phash.cHashParams`
* `index_phash.data_page_reg1`
* `sys_category.t3ver_label`
* `sys_collection.t3ver_label`
* `sys_file_collection.t3ver_label`
* `sys_file_metadata.t3ver_label`
* `sys_file_reference.t3ver_label`
* `sys_template.t3ver_label`
* `tt_content.t3ver_label`


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


The following global JavaScript functions have been removed:

* `launchView()` - Use the method `showItem()` of the `TYPO3/CMS/Backend/InfoWindow` module


The following JavaScript modules have been removed:

* `TYPO3/CMS/Backend/Storage` - Use either `TYPO3/CMS/Backend/Storage/Client` or `TYPO3/CMS/Backend/Storage/Persistent`


The following global instances have been removed:

* `TYPO3.Popover` - require `TYPO3/CMS/Backend/Popover` in your AMD module
* `TYPO3.Utility` - require `TYPO3/CMS/Backend/Utility` in your AMD module


Impact
======

Instantiating or requiring the PHP classes or calling the PHP methods directly will trigger PHP :php:`E_ERROR` errors.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
