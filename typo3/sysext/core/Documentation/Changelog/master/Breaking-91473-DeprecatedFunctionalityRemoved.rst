.. include:: ../../Includes.txt

===================================================
Breaking: #91473 - Deprecated functionality removed
===================================================

See :issue:`91473`

Description
===========

The following PHP classes that have been previously deprecated for v10 have been removed:

- :php:`\TYPO3\CMS\Backend\Template\DocumentTemplate`

The following PHP interfaces that have been previously deprecated for v10 have been removed:

- :php:`\TYPO3\CMS\Core\Resource\ResourceFactoryInterface`

The following PHP class aliases that have been previously deprecated for v10 have been removed:

The following PHP class methods that have been previously deprecated for v10 have been removed:

- :php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController->emitBeforeCallActionMethodSignal`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isOutputting`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->processContentForOutput`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->settingLocale`


The following PHP static class methods that have been previously deprecated for v10 have been removed:

- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRawPagesTSconfig`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getBackendScript`
- :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice`
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

The following PHP methods have been additionally deprecated and are a no-op now:

The following methods changed signature according to previous deprecations in v10 at the end of the argument list:

- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl` (arguments 2, 3 and 4 are dropped)
- :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService` (arguments 3 :php:`$excludeServiceKeys` is now an array)
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->__construct`

The following public class properties have been dropped:

The following class methods have changed visibility:

The following class properties have changed visibility:

The following ViewHelpers have changed:

The following scheduler tasks have been removed:

The following user TSconfig options have been dropped:

The following TypoScript options have been dropped:

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
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_BeforeCaching`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GenerateDataArray_PostProcesss`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_GetDataArray_PostProcesss`
- :php:`\TYPO3\CMS\Workspaces\Service\GridDataService::SIGNAL_SortDataArray_PostProcesss`

The following class constants have been set to protected:

The following global options are ignored:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']`

The following language files and aliases have been removed:

The following global variables have been removed:

The following hooks have been removed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace']`


The following hooks don't pass the class reference anymore:

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

The following features have been removed:

The following database tables have been removed:

The following database fields have been removed:

- pages.legacy_overlay_uid

The following php doc annotations have been removed:

The following global JavaScript functions have been removed:

- :js:`rawurlencode`
- :js:`str_replace`
- :js:`openUrlInWindow`

The following JavaScript modules have been removed:

- :js:`jquery.clearable`
- :js:`md5`

The following global instances have been removed:

Impact
======

Instantiating or requiring the PHP classes or calling the PHP methods directly will trigger PHP :php:`E_ERROR` errors.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
