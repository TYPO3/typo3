.. include:: ../../Includes.txt

===================================================
Breaking: #87193 - Deprecated functionality removed
===================================================

See :issue:`87193`

Description
===========

The following PHP classes that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Backend\Module\AbstractFunctionModule`
* :php:`TYPO3\CMS\Backend\Module\AbstractModule`
* :php:`TYPO3\CMS\Backend\Module\BaseScriptClass`
* :php:`TYPO3\CMS\Backend\RecordList\AbstractRecordList`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\AbstractComposedSalt`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\ExtensionManagerConfigurationUtility`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordService`
* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility`
* :php:`TYPO3\CMS\Core\Encoder\JavaScriptEncoder`
* :php:`TYPO3\CMS\Core\Resource\Utility\BackendUtility`
* :php:`TYPO3\CMS\Core\Utility\ClientUtility`
* :php:`TYPO3\CMS\Core\Utility\PhpOptionsUtility`
* :php:`TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList`
* :php:`TYPO3\CMS\Workspaces\Service\AutoPublishService`
* :php:`TYPO3\CMS\Workspaces\Task\AutoPublishTask`
* :php:`TYPO3\CMS\Workspaces\Task\CleanupPreviewLinkTask`


The following PHP interfaces that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Core\Crypto\PasswordHashing\ComposedPasswordHashInterface`


The following PHP class aliases that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Backend\AjaxLoginHandler`
* :php:`TYPO3\CMS\Backend\Form\Wizard\ImageManipulationWizard`
* :php:`TYPO3\CMS\Cshmanual\Domain\Repository\TableManualRepository`
* :php:`TYPO3\CMS\Frontend\Controller\PageInformationController`
* :php:`TYPO3\CMS\Frontend\Controller\TranslationStatusController`
* :php:`TYPO3\CMS\InfoPagetsconfig\Controller\InfoPageTyposcriptConfigController`
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
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->getFileName()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->getFromMPmap()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->init()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->initMPmap_create()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->linkData()`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->printTitle()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getFirstWebPage()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getDomainStartPage()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRootLine()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRecordsByField()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->deleteClause()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->checkWorkspaceAccess()`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getFileReferences()`


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
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->getRawRecord()` - Fourth argument dropped


The following public class properties have been dropped:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->synonyms`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->allowedPaths`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->debug`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->fileCache`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->frames`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->MPmap`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->whereClause`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->error_getRootLine_failPid`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->error_getRootLine`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->versioningPreview`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->workspaceCache`


The following class methods have changed visibility:

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


The following class properties have changed visibility:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->eucBasedSets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->noCharByteVal` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parsedCharsets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->toASCII` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->twoByteSets` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->sys_language_uid` changed from public to protected
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->versioningWorkspaceId` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->absoluteRootLine` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->matchAll` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->nextLevel` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->outermostRootlineIndexWithTemplate` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->rootId` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->rowSum` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sectionsMatch` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->simulationHiddenOrTime` changed from public to protected
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->sitetitle` changed from public to protected


The following scheduler tasks have been removed:

* EXT:workspaces CleanupPreviewLinkTask
* EXT:workspaces AutoPublishTask


The following user TSconfig options have been dropped:

* Prefix `mod.` to override page TSconfig is ignored


The following constants have been dropped:

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


The following global options are ignored:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods']`


The following language files and aliases have been removed:

* :php:`EXT:saltedpasswords/Resources/Private/Language/locallang.xlf`
* :php:`EXT:saltedpasswords/Resources/Private/Language/locallang_em.xlf`


The following global variables have been removed:

* :php:`$GLOBALS['TYPO3_LOADED_EXT']`


The following feature is now always enabled:

* :php:`simplifiedControllerActionDispatching` - Backend controller actions do not receive a prepared response object anymore


Impact
======

Instantiating or requiring the PHP classes, calling the PHP methods directly, will result in PHP fatal errors.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
