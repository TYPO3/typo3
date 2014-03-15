<?php
namespace TYPO3\CMS\Core\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

// TODO implement constructor-level caching
/**
 * Factory class for FAL objects
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */
class ResourceFactory implements ResourceFactoryInterface, \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Gets a singleton instance of this class.
	 *
	 * @return ResourceFactory
	 */
	static public function getInstance() {
		return GeneralUtility::makeInstance(__CLASS__);
	}

	/**
	 * @var ResourceStorage[]
	 */
	protected $storageInstances = array();

	/**
	 * @var Collection\AbstractFileCollection[]
	 */
	protected $collectionInstances = array();

	/**
	 * @var File[]
	 */
	protected $fileInstances = array();

	/**
	 * @var FileReference[]
	 */
	protected $fileReferenceInstances = array();

	/**
	 * A list of the base paths of "local" driver storages. Used to make the detection of base paths easier.
	 *
	 * @var array
	 */
	protected $localDriverStorageCache = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * Inject signal slot dispatcher
	 */
	public function __construct(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher = NULL) {
		$this->signalSlotDispatcher = $signalSlotDispatcher ?: GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
	}

	/**
	 * Creates a driver object for a specified storage object.
	 *
	 * @param string $driverIdentificationString The driver class (or identifier) to use.
	 * @param array $driverConfiguration The configuration of the storage
	 * @return Driver\DriverInterface
	 * @throws \InvalidArgumentException
	 */
	public function getDriverObject($driverIdentificationString, array $driverConfiguration) {
		/** @var $driverRegistry Driver\DriverRegistry */
		$driverRegistry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\DriverRegistry');
		$driverClass = $driverRegistry->getDriverClass($driverIdentificationString);
		$driverObject = GeneralUtility::makeInstance($driverClass, $driverConfiguration);
		return $driverObject;
	}


	/**
	 * Returns the Default Storage
	 *
	 * The Default Storage is considered to be the replacement for the fileadmin/ construct.
	 * It is automatically created with the setting fileadminDir from install tool.
	 * getDefaultStorage->getDefaultFolder() will get you fileadmin/user_upload/ in a standard
	 * TYPO3 installation.
	 *
	 * @return null|ResourceStorage
	 */
	public function getDefaultStorage() {
		/** @var $storageRepository StorageRepository */
		$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');

		$allStorages = $storageRepository->findAll();
		foreach ($allStorages as $storage) {
			if ($storage->isDefault()) {
				return $storage;
			}
		}
		return NULL;
	}
	/**
	 * Creates an instance of the storage from given UID. The $recordData can
	 * be supplied to increase performance.
	 *
	 * @param integer $uid The uid of the storage to instantiate.
	 * @param array $recordData The record row from database.
	 * @param string $fileIdentifier Identifier for a file. Used for auto-detection of a storage, but only if $uid === 0 (Local default storage) is used
	 *
	 * @throws \InvalidArgumentException
	 * @return ResourceStorage
	 */
	public function getStorageObject($uid, array $recordData = array(), &$fileIdentifier = NULL) {
		if (!is_numeric($uid)) {
			throw new \InvalidArgumentException('uid of Storage has to be numeric.', 1314085991);
		}
		$uid = (int)$uid;
		if ($uid === 0 && $fileIdentifier !== NULL) {
			$uid = $this->findBestMatchingStorageByLocalPath($fileIdentifier);
		}
		if (!$this->storageInstances[$uid]) {
			$storageConfiguration = NULL;
			$storageObject = NULL;
			// If the built-in storage with UID=0 is requested:
			if ($uid === 0) {
				$recordData = array(
					'uid' => 0,
					'pid' => 0,
					'name' => 'Fallback Storage',
					'description' => 'Internal storage, mounting the main TYPO3_site directory.',
					'driver' => 'Local',
					'processingfolder' => 'typo3temp/_processed_/',
					// legacy code
					'configuration' => '',
					'is_online' => TRUE,
					'is_browsable' => TRUE,
					'is_public' => TRUE,
					'is_writable' => TRUE,
					'is_default' => FALSE,
				);
				$storageConfiguration = array(
					'basePath' => '/',
					'pathType' => 'relative'
				);
			} elseif (count($recordData) === 0 || (int)$recordData['uid'] !== $uid) {
				/** @var $storageRepository StorageRepository */
				$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
				/** @var $storage ResourceStorage */
				$storageObject = $storageRepository->findByUid($uid);
			}
			if (!$storageObject instanceof ResourceStorage) {
				$storageObject = $this->createStorageObject($recordData, $storageConfiguration);
			}
			$this->signalSlotDispatcher->dispatch('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', self::SIGNAL_PostProcessStorage, array($this, $storageObject));
			$this->storageInstances[$uid] = $storageObject;
		}
		return $this->storageInstances[$uid];
	}

	/**
	 * Checks whether a file resides within a real storage in local file system.
	 * If no match is found, uid 0 is returned which is a fallback storage pointing to PATH_site.
	 *
	 * The file identifier is adapted accordingly to match the new storage's base path.
	 *
	 * @param string $localPath
	 *
	 * @return integer
	 */
	protected function findBestMatchingStorageByLocalPath(&$localPath) {
		if ($this->localDriverStorageCache === NULL) {
			$this->initializeLocalStorageCache();
		}

		$bestMatchStorageUid = 0;
		$bestMatchLength = 0;
		foreach ($this->localDriverStorageCache as $storageUid => $basePath) {
			$matchLength = strlen(PathUtility::getCommonPrefix(array($basePath, $localPath)));
			$basePathLength = strlen($basePath);

			if ($matchLength >= $basePathLength && $matchLength > $bestMatchLength) {
				$bestMatchStorageUid = (int)$storageUid;
				$bestMatchLength = $matchLength;
			}
		}
		if ($bestMatchStorageUid !== 0) {
			$localPath = substr($localPath, $bestMatchLength);
		}
		return $bestMatchStorageUid;
	}

	/**
	 * Creates an array mapping all uids to the basePath of storages using the "local" driver.
	 *
	 * @return void
	 */
	protected function initializeLocalStorageCache() {
		/** @var $storageRepository StorageRepository */
		$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		/** @var $storageObjects ResourceStorage[] */
		$storageObjects = $storageRepository->findByStorageType('Local');

		$storageCache = array();
		foreach ($storageObjects as $localStorage) {
			$configuration = $localStorage->getConfiguration();
			$storageCache[$localStorage->getUid()] = $configuration['basePath'];
		}
		$this->localDriverStorageCache = $storageCache;
	}

	/**
	 * Converts a flexform data string to a flat array with key value pairs
	 *
	 * @param string $flexFormData
	 * @return array Array with key => value pairs of the field data in the FlexForm
	 */
	public function convertFlexFormDataToConfigurationArray($flexFormData) {
		$configuration = array();
		if ($flexFormData) {
			$flexFormContents = GeneralUtility::xml2array($flexFormData);
			if (!empty($flexFormContents['data']['sDEF']['lDEF']) && is_array($flexFormContents['data']['sDEF']['lDEF'])) {
				foreach ($flexFormContents['data']['sDEF']['lDEF'] as $key => $value) {
					if (isset($value['vDEF'])) {
						$configuration[$key] = $value['vDEF'];
					}
				}
			}
		}
		return $configuration;
	}

	/**
	 * Creates an instance of the collection from given UID. The $recordData can be supplied to increase performance.
	 *
	 * @param integer $uid The uid of the collection to instantiate.
	 * @param array $recordData The record row from database.
	 *
	 * @throws \InvalidArgumentException
	 * @return Collection\AbstractFileCollection
	 */
	public function getCollectionObject($uid, array $recordData = array()) {
		if (!is_numeric($uid)) {
			throw new \InvalidArgumentException('uid of collection has to be numeric.', 1314085999);
		}
		if (!$this->collectionInstances[$uid]) {
			// Get mount data if not already supplied as argument to this function
			if (count($recordData) === 0 || $recordData['uid'] !== $uid) {
				/** @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
				$recordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_collection', 'uid=' . (int)$uid . ' AND deleted=0');
				if (!is_array($recordData)) {
					throw new \InvalidArgumentException('No collection found for given UID.', 1314085992);
				}
			}
			$collectionObject = $this->createCollectionObject($recordData);
			$this->collectionInstances[$uid] = $collectionObject;
		}
		return $this->collectionInstances[$uid];
	}

	/**
	 * Creates a collection object.
	 *
	 * @param array $collectionData The database row of the sys_file_collection record.
	 * @return Collection\AbstractFileCollection
	 */
	public function createCollectionObject(array $collectionData) {
		/** @var $registry Collection\FileCollectionRegistry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Collection\\FileCollectionRegistry');
		$class = $registry->getFileCollectionClass($collectionData['type']);

		return $class::create($collectionData);
	}

	/**
	 * Creates a storage object from a storage database row.
	 *
	 * @param array $storageRecord
	 * @param array $storageConfiguration Storage configuration (if given, this won't be extracted from the FlexForm value but the supplied array used instead)
	 * @return ResourceStorage
	 */
	public function createStorageObject(array $storageRecord, array $storageConfiguration = NULL) {
		$className = 'TYPO3\\CMS\\Core\\Resource\\ResourceStorage';
		if (!$storageConfiguration) {
			$storageConfiguration = $this->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
		}
		$driverType = $storageRecord['driver'];
		$driverObject = $this->getDriverObject($driverType, $storageConfiguration);
		/** @var $storage ResourceStorage */
		$storage = GeneralUtility::makeInstance($className, $driverObject, $storageRecord);
		// TODO handle publisher
		return $storage;
	}

	/**
	 * Creates a folder to directly access (a part of) a storage.
	 *
	 * @param ResourceStorage $storage The storage the folder belongs to
	 * @param string $identifier The path to the folder. Might also be a simple unique string, depending on the storage driver.
	 * @param string $name The name of the folder (e.g. the folder name)
	 * @return Folder
	 */
	public function createFolderObject(ResourceStorage $storage, $identifier, $name) {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Folder', $storage, $identifier, $name);
	}

	protected function createPublisherFromConfiguration(array $configuration) {
		$publishingTarget = $this->getStorageObject($configuration['publisherConfiguration']['publishingTarget']);
		$publisher = GeneralUtility::makeInstance($configuration['publisher'], $publishingTarget, $configuration['publisherConfiguration']);
		return $publisher;
	}

	/**
	 * Creates an instance of the file given UID. The $fileData can be supplied
	 * to increase performance.
	 *
	 * @param integer $uid The uid of the file to instantiate.
	 * @param array $fileData The record row from database.
	 *
	 * @throws \InvalidArgumentException
	 * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
	 * @return File
	 */
	public function getFileObject($uid, array $fileData = array()) {
		if (!is_numeric($uid)) {
			throw new \InvalidArgumentException('uid of file has to be numeric.', 1300096564);
		}
		if (!$this->fileInstances[$uid]) {
			// Fetches data in case $fileData is empty
			if (empty($fileData)) {
				$fileData = $this->getFileIndexRepository()->findOneByUid($uid);
				if ($fileData === FALSE) {
					throw new \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException('No file found for given UID.', 1317178604);
				}
			}
			$this->fileInstances[$uid] = $this->createFileObject($fileData);
		}
		return $this->fileInstances[$uid];
	}

	/**
	 * Gets an file object from an identifier [storage]:[fileId]
	 *
	 * @param string $identifier
	 * @return File
	 */
	public function getFileObjectFromCombinedIdentifier($identifier) {
		$parts = GeneralUtility::trimExplode(':', $identifier);
		if (count($parts) === 2) {
			$storageUid = $parts[0];
			$fileIdentifier = $parts[1];
		} else {
			// We only got a path: Go into backwards compatibility mode and
			// use virtual Storage (uid=0)
			$storageUid = 0;
			$fileIdentifier = $parts[0];
		}

		// please note that getStorageObject() might modify $fileIdentifier when
		// auto-detecting the best-matching storage to use
		return $this->getFileObjectByStorageAndIdentifier($storageUid, $fileIdentifier);
	}

	/**
	 * Gets an file object from storage by file identifier
	 * If the file is outside of the process folder it gets indexed and returned as file object afterwards
	 * If the file is within processing folder the file object will be directly returned
	 *
	 * @param int $storageUid
	 * @param string $fileIdentifier
	 * @return File|ProcessedFile
	 */
	public function getFileObjectByStorageAndIdentifier($storageUid, &$fileIdentifier) {
		$storage = $this->getStorageObject($storageUid, array(), $fileIdentifier);
		if (!$storage->isWithinProcessingFolder($fileIdentifier)) {
			$fileData = $this->getFileIndexRepository()->findOneByStorageUidAndIdentifier($storage->getUid(), $fileIdentifier);
			if ($fileData === FALSE) {
				$fileObject = $this->getIndexer($storage)->createIndexEntry($fileIdentifier);
			} else {
				$fileObject = $this->getFileObject($fileData['uid'], $fileData);
			}
		} else {
			$fileObject = $this->getProcessedFileRepository()->findByStorageAndIdentifier($storage, $fileIdentifier);
		}

		return $fileObject;
	}

	/**
	 * Bulk function, can be used for anything to get a file or folder
	 *
	 * 1. It's a UID
	 * 2. It's a combined identifier
	 * 3. It's just a path/filename (coming from the oldstyle/backwards compatibility)
	 *
	 * Files, previously laid on fileadmin/ or something, will be "mapped" to the storage the file is
	 * in now. Files like typo3temp/ or typo3conf/ will be moved to the first writable storage
	 * in its processing folder
	 *
	 * $input could be
	 * - "2:myfolder/myfile.jpg" (combined identifier)
	 * - "23" (file UID)
	 * - "uploads/myfile.png" (backwards-compatibility, storage "0")
	 * - "file:23"
	 *
	 * @param string $input
	 * @return FileInterface|Folder
	 */
	public function retrieveFileOrFolderObject($input) {
		// Remove PATH_site because absolute paths under Windows systems contain ':'
		// This is done in all considered sub functions anyway
		$input = str_replace(PATH_site, '', $input);

		if (GeneralUtility::isFirstPartOfStr($input, 'file:')) {
			$input = substr($input, 5);
			return $this->retrieveFileOrFolderObject($input);
		} elseif (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($input)) {
			return $this->getFileObject($input);
		} elseif (strpos($input, ':') > 0) {
			list($prefix, $folderIdentifier) = explode(':', $input);
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($prefix)) {
				// path or folder in a valid storageUID
				return $this->getObjectFromCombinedIdentifier($input);
			} elseif ($prefix == 'EXT') {
				$input = GeneralUtility::getFileAbsFileName($input);
				if (empty($input)) {
					return NULL;
				}
				$input = PathUtility::getRelativePath(PATH_site, dirname($input)) . basename($input);
				return $this->getFileObjectFromCombinedIdentifier($input);
			} else {
				return NULL;
			}
		// this is a backwards-compatible way to access "0-storage" files or folders
		} elseif (@is_file(PATH_site . $input)) {
			// only the local file
			return $this->getFileObjectFromCombinedIdentifier($input);
		} else {
			// only the local path
			return $this->getFolderObjectFromCombinedIdentifier($input);
		}
	}

	/**
	 * Gets a folder object from an identifier [storage]:[fileId]
	 *
	 * @TODO check naming, inserted by SteffenR while working on filelist
	 * @param string $identifier
	 * @return Folder
	 */
	public function getFolderObjectFromCombinedIdentifier($identifier) {
		$parts = GeneralUtility::trimExplode(':', $identifier);
		if (count($parts) === 2) {
			$storageUid = $parts[0];
			$folderIdentifier = $parts[1];
		} else {
			// We only got a path: Go into backwards compatibility mode and
			// use virtual Storage (uid=0)
			$storageUid = 0;

			// please note that getStorageObject() might modify $folderIdentifier when
			// auto-detecting the best-matching storage to use
			$folderIdentifier = $parts[0];
			// make sure to not use an absolute path, and remove PATH_site if it is prepended
			if (GeneralUtility::isFirstPartOfStr($folderIdentifier, PATH_site)) {
				$folderIdentifier = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($parts[0]);
			}
		}
		return $this->getStorageObject($storageUid, array(), $folderIdentifier)->getFolder($folderIdentifier);
	}

	/**
	 * Gets a storage object from a combined identifier
	 *
	 * @param string $identifier An identifier of the form [storage uid]:[object identifier]
	 * @return ResourceStorage
	 */
	public function getStorageObjectFromCombinedIdentifier($identifier) {
		$parts = GeneralUtility::trimExplode(':', $identifier);
		$storageUid = count($parts) === 2 ? $parts[0] : NULL;
		return $this->getStorageObject($storageUid);
	}

	/**
	 * Gets a file or folder object.
	 *
	 * @param string $identifier
	 *
	 * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
	 * @return FileInterface|Folder
	 */
	public function getObjectFromCombinedIdentifier($identifier) {
		list($storageId, $objectIdentifier) = GeneralUtility::trimExplode(':', $identifier);
		$storage = $this->getStorageObject($storageId);
		if ($storage->hasFile($objectIdentifier)) {
			return $storage->getFile($objectIdentifier);
		} elseif ($storage->hasFolder($objectIdentifier)) {
			return $storage->getFolder($objectIdentifier);
		} else {
			throw new \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException('Object with identifier "' . $identifier . '" does not exist in storage', 1329647780);
		}
	}

	/**
	 * Creates a file object from an array of file data. Requires a database
	 * row to be fetched.
	 *
	 * @param array $fileData
	 * @param ResourceStorage $storage
	 * @return File
	 */
	public function createFileObject(array $fileData, ResourceStorage $storage = NULL) {
		/** @var File $fileObject */
		if (array_key_exists('storage', $fileData) && MathUtility::canBeInterpretedAsInteger($fileData['storage'])) {
			$storageObject = $this->getStorageObject((int)$fileData['storage']);
		} elseif ($storage !== NULL) {
			$storageObject = $storage;
			$fileData['storage'] = $storage->getUid();
		} else {
			throw new \RuntimeException('A file needs to reside in a Storage', 1381570997);
		}
		$fileObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\File', $fileData, $storageObject);
		return $fileObject;
	}

	/**
	 * Creates an instance of a FileReference object. The $fileReferenceData can
	 * be supplied to increase performance.
	 *
	 * @param integer $uid The uid of the file usage (sys_file_reference) to instantiate.
	 * @param array $fileReferenceData The record row from database.
	 *
	 * @throws \InvalidArgumentException
	 * @return FileReference
	 */
	public function getFileReferenceObject($uid, array $fileReferenceData = array()) {
		if (!is_numeric($uid)) {
			throw new \InvalidArgumentException('uid of fileusage (sys_file_reference) has to be numeric.', 1300086584);
		}
		if (!$this->fileReferenceInstances[$uid]) {
			// Fetches data in case $fileData is empty
			if (empty($fileReferenceData)) {
				// fetch the reference record of the current workspace
				if (TYPO3_MODE === 'BE') {
					$fileReferenceData = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('sys_file_reference', $uid);
				} elseif (is_object($GLOBALS['TSFE'])) {
					$fileReferenceData = $GLOBALS['TSFE']->sys_page->checkRecord('sys_file_reference', $uid);
				} else {
					/** @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
					$fileReferenceData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_reference', 'uid=' . (int)$uid . ' AND deleted=0');
				}
				if (!is_array($fileReferenceData)) {
					throw new \InvalidArgumentException('No fileusage (sys_file_reference) found for given UID.', 1317178794);
				}
			}
			$this->fileReferenceInstances[$uid] = $this->createFileReferenceObject($fileReferenceData);
		}
		return $this->fileReferenceInstances[$uid];
	}

	/**
	 * Creates a file usage object from an array of fileReference data
	 * from sys_file_reference table.
	 * Requires a database row to be already fetched and present.
	 *
	 * @param array $fileReferenceData
	 * @return FileReference
	 */
	public function createFileReferenceObject(array $fileReferenceData) {
		/** @var FileReference $fileReferenceObject */
		$fileReferenceObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileReference', $fileReferenceData);
		return $fileReferenceObject;
	}

	/**
	 * Returns an instance of the FileIndexRepository
	 *
	 * @return FileIndexRepository
	 */
	protected function getFileIndexRepository() {
		return FileIndexRepository::getInstance();
	}

	/**
	 * Returns an instance of the ProcessedFileRepository
	 *
	 * @return ProcessedFileRepository
	 */
	protected function getProcessedFileRepository() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
	}

	/**
	 * Returns an instance of the Indexer
	 *
	 * @return \TYPO3\CMS\Core\Resource\Index\Indexer
	 */
	protected function getIndexer(ResourceStorage $storage) {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\Indexer', $storage);
	}

}
