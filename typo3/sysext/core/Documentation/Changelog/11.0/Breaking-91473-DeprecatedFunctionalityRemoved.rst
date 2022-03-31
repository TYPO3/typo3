.. include:: /Includes.rst.txt

===================================================
Breaking: #91473 - Deprecated functionality removed
===================================================

See :issue:`91473`

Description
===========

The following PHP classes that have previously been marked as deprecated for v10 and were now removed:

- :php:`\TYPO3\CMS\Backend\Configuration\TsConfigParser`
- :php:`\TYPO3\CMS\Backend\Controller\File\CreateFolderController`
- :php:`\TYPO3\CMS\Backend\Controller\File\EditFileController`
- :php:`\TYPO3\CMS\Backend\Controller\File\FileUploadController`
- :php:`\TYPO3\CMS\Backend\Controller\File\RenameFileController`
- :php:`\TYPO3\CMS\Backend\Controller\File\ReplaceFileController`
- :php:`\TYPO3\CMS\Backend\Template\DocumentTemplate`
- :php:`\TYPO3\CMS\Core\Console\CommandRequestHandler`
- :php:`\TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser`
- :php:`\TYPO3\CMS\Core\Routing\Aspect\PersistenceDelegate`
- :php:`\TYPO3\CMS\Core\Routing\Legacy\PersistedAliasMapperLegacyTrait`
- :php:`\TYPO3\CMS\Core\Routing\Legacy\PersistedPatternMapperLegacyTrait`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\AbstractFileCollection`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FileMount`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection`
- :php:`\TYPO3\CMS\Extbase\Domain\Repository\FileMountRepository`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Request`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FolderBasedFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\StaticFileCollectionConverter`
- :php:`\TYPO3\CMS\Felogin\Controller\FrontendLoginController`
- :php:`\TYPO3\CMS\Felogin\Hooks\CmsLayout`
- :php:`\TYPO3\CMS\Fluid\ViewHelpers\Widget\AutocompleteViewHelper`
- :php:`\TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController`

The following PHP interfaces that have previously been marked as deprecated for v10 and were now removed:

- :php:`\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface`
- :php:`\TYPO3\CMS\Core\Console\RequestHandlerInterface`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface`
- :php:`\TYPO3\CMS\Core\Routing\Aspect\DelegateInterface`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectGetSingleHookInterface`

The following PHP class aliases that have previously been marked as deprecated for v10 and were now removed:

* :php:`TYPO3\CMS\Frontend\Page\PageRepository`
* :php:`TYPO3\CMS\Frontend\Page\PageRepositoryGetPageHookInterface`
* :php:`TYPO3\CMS\Frontend\Page\PageRepositoryGetPageOverlayHookInterface`
* :php:`TYPO3\CMS\Frontend\Page\PageRepositoryGetRecordOverlayHookInterface`
* :php:`TYPO3\CMS\Frontend\Page\PageRepositoryInitHookInterface`
* :php:`TYPO3\CMS\Lowlevel\Utility\ArrayBrowser`

The following PHP class methods that have previously been marked as deprecated for v10 and were now removed:

- :php:`\TYPO3\CMS\Backend\History\RecordHistory->createChangeLog`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->createMultipleDiff`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->getElementData`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->getHistoryData`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->getHistoryEntry`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->performRollback`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->setLastHistoryEntry`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->shouldPerformRollback`
- :php:`\TYPO3\CMS\Core\Console\CommandRegistry->getIterator`
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->assemblePermissions`
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->process_uploads`
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->setTSconfigPermissions`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService->getLabelsWithPrefix`
- :php:`\TYPO3\CMS\Core\Html\RteHtmlParser->init`
- :php:`\TYPO3\CMS\Core\Html\RteHtmlParser->RTE_transform`
- :php:`\TYPO3\CMS\Core\Resource\File->_getMetaData`
- :php:`\TYPO3\CMS\Core\Resource\FileRepository->searchByName`
- :php:`\TYPO3\CMS\Core\Resource\Index\FileIndexRepository->findBySearchWordInMetaData`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactory->getInstance`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorage->checkFileAndFolderNameFilters`
- :php:`\TYPO3\CMS\Core\Utility\BasicFileUtility->setFileExtensionPermissions`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->emitBeforeCallActionMethodSignal`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder->setUseCacheHash`
- :php:`\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder->getUseCacheHash`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->cImage`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getAltParam`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getBorderAttr`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getImageSourceCollection`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getImageTagTemplate`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->linkWrap`
- :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->sendNotifyEmail`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isOutputting`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->processContentForOutput`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->reqCHash`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->settingLocale`
- :php:`\TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository->getNumberOfBrokenLinks`


The following PHP static class methods that have previously been marked as deprecated for v10 and were now removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRawPagesTSconfig`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice`
- :php:`\TYPO3\CMS\Core\Localization\Locales::initialize`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::compressIPv6`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::flushDirectory`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::idnaEncode`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Hex2Bin`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::IPv6Bin2Hex`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isRunningOnCgiServerApi`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::presetApplicationContext`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::resetApplicationContext`
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern`
- :php:`\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertIntegerToVersionNumber`
- :php:`\TYPO3\CMS\Core\Utility\VersionNumberUtility::splitVersionRange`
- :php:`\TYPO3\CMS\Core\Utility\VersionNumberUtility::raiseVersionNumber`
- :php:`\TYPO3\CMS\Extbase\Reflection\ObjectAccess::buildSetterMethodName`
- :php:`\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::hex2bin`

The following methods changed signature according to previous deprecations in v10 at the end of the argument list:

- :php:`\TYPO3\CMS\Core\Database\ReferenceIndex->updateIndex` (argument 2 is now either null or ProgressListenerInterface, not boolean anymore)
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->doesRecordExist` (argument 3 is now an integer)
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->recordInfoWithPermissionCheck` (argument 3 is now an integer)
- :php:`\TYPO3\CMS\Core\Localization\LanguageService->includeLLFile` (arguments 2 and 3 are dropped)
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::findService` (arguments 3 :php:`$excludeServiceKeys` is now an array)
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction` (arguments 3 no expects an object or null)
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl` (arguments 2, 3 and 4 are dropped)
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService` (arguments 3 :php:`$excludeServiceKeys` is now an array)
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper->__construct` (argument :php:`$query` is removed)
- :php:`\TYPO3\CMS\Extbase\Persistence\Reflection\ObjectAccess->setProperty` (argument :php:`$forceDirectAccess` is removed)
- :php:`\TYPO3\CMS\Extbase\Persistence\Reflection\ObjectAccess->getProperty` (argument :php:`$forceDirectAccess` is removed)
- :php:`\TYPO3\CMS\Extbase\Persistence\Reflection\ObjectAccess->getPropertyInternal` (argument :php:`$forceDirectAccess` is removed)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->__construct`

The following public class properties have been dropped:

- :php:`\TYPO3\CMS\Backend\History\RecordHistory->changeLog`
- :php:`\TYPO3\CMS\Backend\History\RecordHistory->lastHistoryEntry`
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->defaultPermissions`
- :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->pMap`
- :php:`\TYPO3\CMS\Core\TypoScript\TemplateService->forceTemplateParsing`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cHash`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cHash_array`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->divSection`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->domainStartPage`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fePreview`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->forceTemplateParsing`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_isocode`

The following class methods have changed visibility:

- :php:`\TYPO3\CMS\Core\Localization\LanguageService->debugLL()`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService->getLLL()`

The following class properties have changed visibility:

- :php:`\TYPO3\CMS\Core\Localization\LanguageService->LL_files_cache`
- :php:`\TYPO3\CMS\Core\Localization\LanguageService->LL_labels_cache`

The following ViewHelpers have changed:

- :html:`<f:form>` ViewHelper argument "noCacheHash" is dropped
- :html:`<f:link.action>` ViewHelper argument "noCacheHash" is dropped
- :html:`<f:link.page>` ViewHelper argument "noCacheHash" is dropped
- :html:`<f:link.typolink>` ViewHelper argument "useCacheHash" is dropped
- :html:`<f:uri.action>` ViewHelper argument "noCacheHash" is dropped
- :html:`<f:uri.page>` ViewHelper argument "noCacheHash" is dropped
- :html:`<f:uri.typolink>` ViewHelper argument "useCacheHash" is dropped
- :html:`<f:widget.link>` ViewHelper argument "useCacheHash" is dropped
- :html:`<f:widget.uri>` ViewHelper argument "useCacheHash" is dropped
- :html:`<f:widget.autocomplete>` ViewHelper is removed

The following TypoScript options have been dropped:

- Extbase TypoScript option `requireCHashArgumentForActionArguments` for any plugin
- `typolink.useCacheHash`
- `typolink.addQueryString.method = POST`
- `typolink.addQueryString.method = POST,GET`
- `typolink.addQueryString.method = GET,POST`

The following constants have been dropped:

- :php:`FILE_DENY_PATTERN_DEFAULT`
- :php:`PHP_EXTENSIONS_DEFAULT`
- :php:`TYPO3_copyright_year`
- :php:`TYPO3_URL_DONATE`
- :php:`TYPO3_URL_EXCEPTION`
- :php:`TYPO3_URL_GENERAL`
- :php:`TYPO3_URL_LICENSE`
- :php:`TYPO3_URL_WIKI_OPCODECACHE`

The following class constants have been dropped:

- :php:`\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::SIGNAL_PostProcessTreeData`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PreProcessStorage`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PostProcessStorage`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCreate`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileSetContents`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCreate`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileReplace`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileSetContents`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_SanitizeFileName`
- :php:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PreFileProcess`
- :php:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PostFileProcess`
- :php:`\TYPO3\CMS\Form\Domain\Finishers\EmailFinisher::FORMAT_PLAINTEXT`
- :php:`\TYPO3\CMS\Form\Domain\Finishers\EmailFinisher::FORMAT_HTML`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_BeforeCaching`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_PostProcesss`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GetDataArray_PostProcesss`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_SortDataArray_PostProcesss`

The following global options are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']`

The following global variables have been removed:

- :php:`$GLOBALS['LOCAL_LANG']`

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClassDefault']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['extLinkATagParamsHandler']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['beforeRedirect']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['forgotPasswordMail']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_error']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent']`

The following signals have been removed:

- :php:`PackageManagement::packagesMayHaveChanged`
- :php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::getSystemInformation`
- :php:`\TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::loadMessages`
- :php:`\TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::getPageRenderer`
- :php:`\TYPO3\CMS\Backend\Controller\EditDocumentController::preInitAfter`
- :php:`\TYPO3\CMS\Backend\Controller\EditDocumentController::initAfter`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfigPreInclude`
- :php:`\TYPO3\CMS\Beuser\Controller\BackendUserController::switchUser`
- :php:`\TYPO3\CMS\Core\Database\SoftReferenceIndex::setTypoLinkPartsElement`
- :php:`\TYPO3\CMS\Core\Database\ReferenceIndex::shouldExcludeTableFromReferenceIndex`
- :php:`\TYPO3\CMS\Core\Imaging\IconFactory::buildIconForResourceSignal`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PreProcessStorage`
- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface::SIGNAL_PostProcessStorage`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileCreate`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileSetContents`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileCreate`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileReplace`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileSetContents`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderAdd`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderCopy`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderDelete`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderMove`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderRename`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreGeneratePublicUrl`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_SanitizeFileName`
- :php:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PreFileProcess`
- :php:`\TYPO3\CMS\Core\Resource\Service\FileProcessingService::SIGNAL_PostFileProcess`
- :php:`\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::PostProcessTreeData`
- :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::tcaIsBeingBuilt`
- :php:`\TYPO3\CMS\Extbase\Mvc\Dispatcher::afterRequestDispatch`
- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::beforeCallActionMethod`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::afterMappingSingleRow`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend::beforeGettingObjectData`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterGettingObjectData`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend::endInsertObject`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterUpdateObject`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterPersistObject`
- :php:`\TYPO3\CMS\Extbase\Persistence\Generic\Backend::afterRemoveObject`
- :php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionInstall`
- :php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionUninstall`
- :php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionT3DImport`
- :php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionStaticSqlImport`
- :php:`\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::afterExtensionFileImport`
- :php:`\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::willInstallExtensions`
- :php:`\TYPO3\CMS\Extensionmanager\ViewHelper\ProcessAvailableActionsViewHelper::processActions`
- :php:`\TYPO3\CMS\Install\Service\SqlExpectedSchemaService::tablesDefinitionIsBeingBuilt`
- :php:`\TYPO3\CMS\Impexp\Utility\ImportExportUtility::afterImportExportInitialisation`
- :php:`\TYPO3\CMS\Lang\Service\TranslationService::postProcessMirrorUrl`
- :php:`\TYPO3\CMS\Linkvalidator\LinkAnalyzer::beforeAnalyzeRecord`
- :php:`\TYPO3\CMS\Seo\Canonical\CanonicalGenerator::beforeGeneratingCanonical`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_BeforeCaching`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_PostProcesss`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GetDataArray_PostProcesss`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_SortDataArray_PostProcesss`

The following features are now always enabled:

- `felogin.extbase`

The following features have been removed:

- All install tool upgrade wizards upgrading from v8 to v9
- CLI Command Configuration definition via :file:`Commands.php`
- Pi-based plugin for "felogin" (CType `login`)
- XML-based (TYPO3-custom XML format) label parsing

The following database fields have been removed:

- :sql:`sys_template.sitetitle`
- :sql:`pages.legacy_overlay_uid`

The following Backend route identifiers have been removed:

- `xMOD_tximpexp`

The following global JavaScript variables have been removed:

- :js:`T3_THIS_LOCATION`
- :js:`T3_RETURN_URL`

The following global JavaScript functions have been removed:

- :js:`jumpExt`
- :js:`jumpToUrl`
- :js:`rawurlencode`
- :js:`str_replace`
- :js:`openUrlInWindow`
- :js:`setFormValueOpenBrowser`
- :js:`setFormValueFromBrowseWin`
- :js:`setHiddenFromList`
- :js:`setFormValueManipulate`
- :js:`setFormValue_getFObj`

The following JavaScript modules have been removed:

- :js:`jquery.clearable`
- :js:`md5`

Impact
======

Instantiating or requiring the PHP classes or calling the PHP methods directly will trigger PHP :php:`E_ERROR` errors.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
