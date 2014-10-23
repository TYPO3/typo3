============================================================
Breaking: #62416 - Removal of deprecated code in sysext core
============================================================

Description
===========

DataHandler
-----------

DataHandler::clear_cache() is removed. Use ->clear_cacheCmd instead. Alternatively you can
call ->registerPageCacheClearing() from a hook to not immediately clear the cache but register clearing after DataHandler operation finishes.

DataHandler::internal_clearPageCache() is removed, use the cache manager directly.

The hook ['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables'] is removed, use the caching framework with database backend instead.


DatabaseConnection
------------------

All connection parameters (host, username, password, db) for methods sql_connect(), sql_select_db() and  connectDB() are removed.


PackageManager
--------------

The globally defined REQUIRED_EXTENSIONS constant is removed.


ExtensionManagementUtility
--------------------------

Parameter classPath of insertModuleFunction() is now unused. Leverage autoloading instead.


Removed PHP classes
-------------------

* TYPO3\CMS\Core\Resource\Service\IndexerService is removed without replacement. Indexing is done internally.
* TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator is removed without replacement.
  Do not use $GLOBALS[\'typo3CacheManager\'] and $GLOBALS[\'typo3CacheFactory\'] anymore, use GeneralUtility::makeInstance() instead.


Removed PHP class members
-------------------------

* AbstractUserAuthentication::$cookieId is removed. Use isCookieSet() instead.


Removed PHP methods
-------------------

* GeneralUtility::array_merge_recursive_overrule() is removed. Use ArrayUtility::mergeRecursiveWithOverrule() instead.
  WARNING: The new method changed its signature and does not return the first parameter anymore.
* GeneralUtility::htmlspecialchars_decode() is removed in favor of the native PHP htmlspecialchars_decode() function.
* CategoryRegistry::get() is removed. Use isRegistered() instead.
* CategoryRegistry::applyTca() is removed. The functionality is obsolete.
* DataHandler::clear_cache() is removed. Use ->clear_cacheCmd instead. Alternatively you can
  call ->registerPageCacheClearing() from a hook to not immediately clear the cache but register clearing after DataHandler operation finishes.
* DataHandler::internal_clearPageCache() is removed, use the cache manager directly.
* FileRepository::findByUid() is removed without replacement.
* FileRepository::addToIndex() is removed without replacement. Indexing is done transparently.
* FileRepository::getFileIndexRecordsForFolder() is removed. Use FileIndexRepository::findByFolder() instead.
* FileRepository::getFileIndexRecord() is removed. Use FileIndexRepository::findOneByFileObject() instead.
* FileRepository::findBySha1Hash() is removed. Use FileIndexRepository::findByContentHash() instead.
* FileRepository::update() is removed. Use FileIndexRepository::update() instead.
* ResourceStorage::getFolderByIdentifier() is replaced by getFolder().
* ResourceStorage::getFileByIdentifier() is replaced by getFileInfoByIdentifier().
* ResourceStorage::getFileList() is replaced by getFilesInFolder().
* ResourceStorage::getFolderList() is removed. Use getFoldersInFolder() instead.
* ResourceStorage::fetchFolderListFromDriver() is removed. Use getFoldersInFolder() instead.
* BasicFileUtility::getTotalFileInfo() is removed. Use ResourceStorage instead via $GLOBALS['BE_USER']->getFileStorages()
* BasicFileUtility::checkFileNameLen() is removed. Use ResourceStorage instead via $GLOBALS['BE_USER']->getFileStorages()
* BasicFileUtility::isPathValid() is removed in favor of GeneralUtility::validPathStr().
* BasicFileUtility::blindPath() is removed without replacement.
* BasicFileUtility::findTempFolder() is removed without replacement.
* BasicFileUtility::rmDoubleSlash() is removed without replacement.
* BasicFileUtility::cleanDirectoryName() is removed. Use PathUtility::getCanonicalPath() instead.
* ExtendedFileUtility::init_actionPerms() is replaced with setActionPermissions().
* ExtendedFileUtility::printLogErrorMessages() is replaced with pushErrorMessagesToFlashMessageQueue().
* ExtendedFileUtility::findRecycler() is removed. Use ResourceStorage instead.
* RteHtmlParser::rteImageStorageDir() and SelectImage::getRTEImageStorageDir() are removed.
  Use $fileFactory->getFolderObjectFromCombinedIdentifier($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']); instead.
* Locales::getTerLocales() is removed without replacement.
* Locales::getTerLocaleDependencies() is removed without replacement.
* ExtensionManagementUtility::getRequiredExtensionListArray() is removed without replacement.
* ExtensionManagementUtility::writeNewExtensionList() is removed without replacement.
* PhpOptionsUtility::isSqlSafeModeEnabled() is removed without replacement.
* ClassLoader::getAliasForClassName() is replaced with getAliasesForClassName().


Impact
======

A call to any of the aforementioned methods by third party code will result in a fatal PHP error.


Affected installations
======================

Any installation which contains third party code still using these deprecated methods.


Migration
=========

Replace the calls with the suggestions outlined above.
