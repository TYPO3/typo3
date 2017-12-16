
.. include:: ../../Includes.txt

============================================================
Breaking: #62416 - Removal of deprecated code in sysext core
============================================================

See :issue:`62416`

Description
===========

DataHandler
-----------

:code:`DataHandler::clear_cache()` has been removed. Use :code:`->clear_cacheCmd()` instead. Alternatively you can
call :code:`->registerPageCacheClearing()` from a hook to not immediately clear the cache but register clearing after DataHandler operation finishes.

:code:`DataHandler::internal_clearPageCache()` has been removed, please use the cache manager directly.

The hook ['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables'] has been removed, use the caching framework with database backend instead.


DatabaseConnection
------------------

All connection parameters (host, username, password, db) for methods sql_connect(), sql_select_db() and  connectDB() have been removed.


PackageManager
--------------

The globally defined REQUIRED_EXTENSIONS constant has been removed.


ExtensionManagementUtility
--------------------------

Parameter classPath of insertModuleFunction() is now unused. Leverage autoloading instead.


Removed PHP classes
-------------------

* TYPO3\CMS\Core\Resource\Service\IndexerService has been removed without replacement. Indexing is done internally.
* TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator has been removed without replacement.
  Do not use $GLOBALS[\'typo3CacheManager\'] and $GLOBALS[\'typo3CacheFactory\'] anymore, use GeneralUtility::makeInstance() instead.


Removed PHP class members
-------------------------

* AbstractUserAuthentication::$cookieId has been removed. Use isCookieSet() instead.


Removed PHP methods
-------------------

* GeneralUtility::array_merge_recursive_overrule() has been removed. Use ArrayUtility::mergeRecursiveWithOverrule() instead.
  WARNING: The new method changed its signature and does not return the first parameter anymore.
* GeneralUtility::htmlspecialchars_decode() has been removed in favor of the native PHP htmlspecialchars_decode() function.
* CategoryRegistry::get() has been removed. Use isRegistered() instead.
* CategoryRegistry::applyTca() has been removed. The functionality is obsolete.
* DataHandler::clear_cache() has been removed. Use ->clear_cacheCmd instead. Alternatively you can
  call ->registerPageCacheClearing() from a hook to not immediately clear the cache but register clearing after DataHandler operation finishes.
* DataHandler::internal_clearPageCache() has been removed, use the cache manager directly.
* FileRepository::findByUid() has been removed without replacement.
* FileRepository::addToIndex() has been removed without replacement. Indexing is done transparently.
* FileRepository::getFileIndexRecordsForFolder() has been removed. Use FileIndexRepository::findByFolder() instead.
* FileRepository::getFileIndexRecord() has been removed. Use FileIndexRepository::findOneByFileObject() instead.
* FileRepository::findBySha1Hash() has been removed. Use FileIndexRepository::findByContentHash() instead.
* FileRepository::update() has been removed. Use FileIndexRepository::update() instead.
* ResourceStorage::getFolderByIdentifier() is replaced by getFolder().
* ResourceStorage::getFileByIdentifier() is replaced by getFileInfoByIdentifier().
* ResourceStorage::getFileList() is replaced by getFilesInFolder().
* ResourceStorage::getFolderList() has been removed. Use getFoldersInFolder() instead.
* ResourceStorage::fetchFolderListFromDriver() has been removed. Use getFoldersInFolder() instead.
* BasicFileUtility::getTotalFileInfo() has been removed. Use ResourceStorage instead via $GLOBALS['BE_USER']->getFileStorages()
* BasicFileUtility::checkFileNameLen() has been removed. Use ResourceStorage instead via $GLOBALS['BE_USER']->getFileStorages()
* BasicFileUtility::isPathValid() has been removed in favor of GeneralUtility::validPathStr().
* BasicFileUtility::blindPath() has been removed without replacement.
* BasicFileUtility::findTempFolder() has been removed without replacement.
* BasicFileUtility::rmDoubleSlash() has been removed without replacement.
* BasicFileUtility::cleanDirectoryName() has been removed. Use PathUtility::getCanonicalPath() instead.
* ExtendedFileUtility::init_actionPerms() is replaced with setActionPermissions().
* ExtendedFileUtility::printLogErrorMessages() is replaced with pushErrorMessagesToFlashMessageQueue().
* ExtendedFileUtility::findRecycler() has been removed. Use ResourceStorage instead.
* RteHtmlParser::rteImageStorageDir() and SelectImage::getRTEImageStorageDir() are removed.
  Use $fileFactory->getFolderObjectFromCombinedIdentifier($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']); instead.
* Locales::getTerLocales() has been removed without replacement.
* Locales::getTerLocaleDependencies() has been removed without replacement.
* ExtensionManagementUtility::getRequiredExtensionListArray() has been removed without replacement.
* ExtensionManagementUtility::writeNewExtensionList() has been removed without replacement.
* PhpOptionsUtility::isSqlSafeModeEnabled() has been removed without replacement.
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
