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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\PathUtility;

// TODO implement constructor-level caching
/**
 * Factory class for FAL objects.
 *
 * NOTE: This class is part of the lowlevel FAL API and should not be used from outside the FAL package.
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */
class ResourceFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Gets a singleton instance of this class.
	 *
	 * @return ResourceFactory
	 */
	static public function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
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
	 * Creates a driver object for a specified storage object.
	 *
	 * @param string $driverIdentificationString The driver class (or identifier) to use.
	 * @param array $driverConfiguration The configuration of the storage
	 * @return Driver\AbstractDriver
	 * @throws \InvalidArgumentException
	 */
	public function getDriverObject($driverIdentificationString, array $driverConfiguration) {
		/** @var $driverRegistry Driver\DriverRegistry */
		$driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\DriverRegistry');
		$driverClass = $driverRegistry->getDriverClass($driverIdentificationString);
		$driverObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($driverClass, $driverConfiguration);
		return $driverObject;
	}

	/**
	 * Creates an instance of the storage from given UID. The $recordData can
	 * be supplied to increase performance.
	 *
	 * @param integer $uid The uid of the storage to instantiate.
	 * @param array $recordData The record row from database.
	 *
	 * @throws \InvalidArgumentException
	 * @return ResourceStorage
	 */
	public function getStorageObject($uid, array $recordData = array()) {
		if (!is_numeric($uid)) {
			throw new \InvalidArgumentException('uid of Storage has to be numeric.', 1314085991);
		}
		if (!$this->storageInstances[$uid]) {
			$storageConfiguration = NULL;
			$storageObject = NULL;
			// If the built-in storage with UID=0 is requested:
			if (intval($uid) === 0) {
				$recordData = array(
					'uid' => 0,
					'pid' => 0,
					'name' => 'Default Storage',
					'description' => 'Internal storage, mounting the main TYPO3_site directory.',
					'driver' => 'Local',
					'processingfolder' => 'typo3temp/_processed_/',
					// legacy code
					'configuration' => '',
					'is_online' => TRUE,
					'is_browsable' => TRUE,
					'is_public' => TRUE,
					'is_writable' => TRUE
				);
				$storageConfiguration = array(
					'basePath' => '/',
					'pathType' => 'relative'
				);
			} elseif (count($recordData) === 0 || $recordData['uid'] !== $uid) {
				/** @var $storageRepository StorageRepository */
				$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
				/** @var $storage ResourceStorage */
				$storageObject = $storageRepository->findByUid($uid);
			}
			if (!$storageObject instanceof ResourceStorage) {
				$storageObject = $this->createStorageObject($recordData, $storageConfiguration);
			}
			$this->storageInstances[$uid] = $storageObject;
		}
		return $this->storageInstances[$uid];
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
			$flexFormContents = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($flexFormData);
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
				$recordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_collection', 'uid=' . intval($uid) . ' AND deleted=0');
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
		switch ($collectionData['type']) {
		case 'static':
			$collection = Collection\StaticFileCollection::create($collectionData);
			break;
		case 'folder':
			$collection = Collection\FolderBasedFileCollection::create($collectionData);
			break;
		default:
			$collection = NULL;
		}
		return $collection;
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
		$storage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $driverObject, $storageRecord);
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
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Folder', $storage, $identifier, $name);
	}

	protected function createPublisherFromConfiguration(array $configuration) {
		$publishingTarget = $this->getStorageObject($configuration['publisherConfiguration']['publishingTarget']);
		$publisher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($configuration['publisher'], $publishingTarget, $configuration['publisherConfiguration']);
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
				/** @var $GLOBALS['TYPO3_DB'] \TYPO3\CMS\Core\Database\DatabaseConnection */
				$fileData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file', 'uid=' . intval($uid) . ' AND deleted=0');
				if (!is_array($fileData)) {
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
		$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $identifier);
		if (count($parts) === 2) {
			$storageUid = $parts[0];
			$fileIdentifier = $parts[1];
		} else {
			// We only got a path: Go into backwards compatibility mode and
			// use virtual Storage (uid=0)
			$storageUid = 0;
			$fileIdentifier = $parts[0];
		}
		return $this->getStorageObject($storageUid)->getFile($fileIdentifier);
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
		// Easy function to deal with that, could be dropped in the future
		// if we know where to use this function
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($input, 'file:')) {
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
				$input = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($input);
				$input = \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath(PATH_site, dirname($input)) . basename($input);
				return $this->getFileObjectFromCombinedIdentifier($input);
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
		$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $identifier);
		if (count($parts) === 2) {
			$storageUid = $parts[0];
			$folderIdentifier = $parts[1];
		} else {
			// We only got a path: Go into backwards compatibility mode and
			// use virtual Storage (uid=0)
			$storageUid = 0;
			$folderIdentifier = $parts[0];
			// make sure to not use an absolute path, and remove PATH_site if it is prepended
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($folderIdentifier, PATH_site)) {
				$folderIdentifier = substr($parts[0], strlen(PATH_site));
			}
		}
		return $this->getStorageObject($storageUid)->getFolder($folderIdentifier);
	}

	/**
	 * Gets a storage object from a combined identifier
	 *
	 * @param string $identifier An identifier of the form [storage uid]:[object identifier]
	 * @return ResourceStorage
	 */
	public function getStorageObjectFromCombinedIdentifier($identifier) {
		$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $identifier);
		if (count($parts) === 2) {
			$storageUid = $parts[0];
		}

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
		list($storageId, $objectIdentifier) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $identifier);
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
	 * @return File
	 */
	public function createFileObject(array $fileData) {
		/** @var File $fileObject */
		$fileObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\File', $fileData);
		if (is_numeric($fileData['storage'])) {
			$storageObject = $this->getStorageObject($fileData['storage']);
			$fileObject->setStorage($storageObject);
		}
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
					$fileReferenceData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_reference', 'uid=' . intval($uid) . ' AND deleted=0');
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
		$fileReferenceObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileReference', $fileReferenceData);
		return $fileReferenceObject;
	}
}


?>
