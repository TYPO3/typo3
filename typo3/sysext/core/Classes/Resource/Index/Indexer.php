<?php
namespace TYPO3\CMS\Core\Resource\Index;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
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

use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\File;

/**
 * The New FAL Indexer
 */
class Indexer {

	/**
	 * @var array
	 */
	protected $filesToUpdate = array();

	/**
	 * @var integer[]
	 */
	protected $identifiedFileUids = array();

	/**
	 * @var ResourceStorage
	 */
	protected $storage = NULL;

	/**
	 * @param ResourceStorage $storage
	 */
	public function __construct(ResourceStorage $storage) {
		$this->storage = $storage;
	}

	/**
	 * Create index entry
	 *
	 * @param string $identifier
	 * @return File
	 */
	public function createIndexEntry($identifier) {
		$fileProperties = $this->gatherFileInformationArray($identifier);
		$record = $this->getFileIndexRepository()->addRaw($fileProperties);
		$fileObject = $this->getResourceFactory()->getFileObject($record['uid'], $record);
		$this->extractRequiredMetaData($fileObject);
		return $fileObject;
	}

	/**
	 * Update index entry
	 *
	 * @param File $fileObject
	 * @return void
	 */
	public function updateIndexEntry(File $fileObject) {
		$updatedInformation = $this->gatherFileInformationArray($fileObject->getIdentifier());
		$fileObject->updateProperties($updatedInformation);
		$this->getFileIndexRepository()->update($fileObject);
		$this->extractRequiredMetaData($fileObject);
	}

	/**
	 * @return void
	 */
	public function processChangesInStorages() {
		// get all file-identifiers from the storage
		$availableFiles = $this->storage->getFileIdentifiersInFolder($this->storage->getRootLevelFolder()->getIdentifier(), TRUE, TRUE);
		$this->detectChangedFilesInStorage($availableFiles);
		$this->processChangedAndNewFiles();

		$this->detectMissingFiles();
	}

	/**
	 * @param integer $maximumFileCount
	 * @return void
	 */
	public function runMetaDataExtraction($maximumFileCount = -1) {
		$fileIndexRecords = $this->getFileIndexRepository()->findInStorageWithIndexOutstanding($this->storage, $maximumFileCount);

		$extractionServices = $this->getExtractorRegistry()->getExtractorsWithDriverSupport($this->storage->getDriverType());
		foreach ($fileIndexRecords as $indexRecord) {
			$fileObject = $this->getResourceFactory()->getFileObject($indexRecord['uid'], $indexRecord);

			$newMetaData = array(
				0 => $fileObject->_getMetaData()
			);
			foreach ($extractionServices as $service) {
				if ($service->canProcess($fileObject)) {
					$newMetaData[$service->getPriority()] = $service->extractMetaData($fileObject, $newMetaData);
				}
			}
			ksort($newMetaData);
			$metaData = array();
			foreach ($newMetaData as $data) {
				$metaData = array_merge($metaData, $data);
			}
			$fileObject->_updateMetaDataProperties($metaData);
			$this->getMetaDataRepository()->update($fileObject->getUid(), $metaData);
			$this->getFileIndexRepository()->updateIndexingTime($fileObject->getUid());
		}
	}

	/**
	 * Since by now all files in filesystem have been looked at it is save to assume,
	 * that files that are in indexed but not touched in this run are missing
	 */
	protected function detectMissingFiles() {
		if (count($this->identifiedFileUids) > 0) {
			$indexedNotExistentFiles = $this->getFileIndexRepository()->findInStorageAndNotInUidList($this->storage, $this->identifiedFileUids);

			foreach ($indexedNotExistentFiles as $record) {
				if (!$this->storage->hasFile($record['identifier'])) {
					$this->getFileIndexRepository()->markFileAsMissing($record['uid']);
				}
			}
		}
	}

	/**
	 * Adds updated files to the processing queue
	 *
	 * @param array $fileIdentifierArray
	 * @return void
	 */
	protected function detectChangedFilesInStorage(array $fileIdentifierArray) {
		foreach ($fileIdentifierArray as $fileIdentifier) {
			// skip processed files
			if (strpos($fileIdentifier, $this->storage->getProcessingFolder()->getIdentifier()) === 0) {
				continue;
			}
			// Get the modification time for file-identifier from the storage
			$modificationTime = $this->storage->getFileInfoByIdentifier($fileIdentifier, array('mtime'));
			// Look if the the modification time in FS is higher than the one in database (key needed on timestamps)
			$indexRecord = $this->getFileIndexRepository()->findOneByStorageUidAndIdentifier($this->storage->getUid(), $fileIdentifier);

			if ($indexRecord !== FALSE) {
				$this->identifiedFileUids[] = $indexRecord['uid'];

				if ($indexRecord['modification_date'] < $modificationTime['mtime'] || $indexRecord['missing']) {
					$this->filesToUpdate[$fileIdentifier] = $indexRecord;
				}
			} else {
				$this->filesToUpdate[$fileIdentifier] = NULL;
			}
		}
	}

	/**
	 * Processes the Files which have been detected as "changed or new"
	 * in the storage
	 *
	 * @return void
	 */
	protected function processChangedAndNewFiles() {
		foreach ($this->filesToUpdate AS $identifier => $data) {
			if ($data == NULL) {
				$fileHash = $this->storage->hashFileByIdentifier($identifier, 'sha1');
				$files = $this->getFileIndexRepository()->findByContentHash($fileHash);
				if (count($files) > 0) {
					foreach ($files as $fileIndexEntry) {
						if ($fileIndexEntry['missing']) {
							$fileObject = $this->getResourceFactory()->getFileObject($fileIndexEntry['uid'], $fileIndexEntry);
							$fileObject->updateProperties(array(
								'identifier' => $identifier
							));
							$this->updateIndexEntry($fileObject);
							$this->identifiedFileUids[] = $fileObject->getUid();
							break;
						}
					}
				} else {
					// index new file
					$fileObject = $this->createIndexEntry($identifier);
					$this->identifiedFileUids[] = $fileObject->getUid();
				}
			} else {
				// update existing file
				$fileObject = $this->getResourceFactory()->getFileObject($data['uid'], $data);
				$this->updateIndexEntry($fileObject);
			}
		}
	}

	/**
	 * Since the core desperately needs image sizes in metadata table put them there
	 * This should be called after every "content" update and "record" creation
	 *
	 * @param File $fileObject
	 */
	protected function extractRequiredMetaData(File $fileObject) {
		// since the core desperately needs image sizes in metadata table do this manually
		// prevent doing this for remote storages, remote storages must provide the data with extractors
		if ($fileObject->getType() == File::FILETYPE_IMAGE && $this->storage->getDriverType() === 'Local') {
			$rawFileLocation = $fileObject->getForLocalProcessing(FALSE);
			$metaData = array();
			list($metaData['width'], $metaData['height']) = getimagesize($rawFileLocation);
			$this->getMetaDataRepository()->update($fileObject->getUid(), $metaData);
			$fileObject->_updateMetaDataProperties($metaData);
		}
	}

	/****************************
	 *
	 *         UTILITY
	 *
	 ****************************/

	/**
	 * Collects the information to be cached in sys_file
	 *
	 * @param string $identifier
	 * @return array
	 */
	protected function gatherFileInformationArray($identifier) {
		$fileInfo = $this->storage->getFileInfoByIdentifier($identifier);
		$fileInfo = $this->transformFromDriverFileInfoArrayToFileObjectFormat($fileInfo);
		$fileInfo['type'] = $this->getFileType($fileInfo['mime_type']);
		$fileInfo['sha1'] = $this->storage->hashFileByIdentifier($identifier, 'sha1');
		$fileInfo['extension'] = \TYPO3\CMS\Core\Utility\PathUtility::pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
		$fileInfo['missing'] = 0;

		return $fileInfo;
	}

	/**
	 * Maps the mimetype to a sys_file table type
	 *
	 * @param string $mimeType
	 * @return string
	 */
	protected function getFileType($mimeType) {
		list($fileType) = explode('/', $mimeType);
		switch (strtolower($fileType)) {
			case 'text':
				$type = File::FILETYPE_TEXT;
				break;
			case 'image':
				$type = File::FILETYPE_IMAGE;
				break;
			case 'audio':
				$type = File::FILETYPE_AUDIO;
				break;
			case 'video':
				$type = File::FILETYPE_VIDEO;
				break;
			case 'application':
			case 'software':
				$type = File::FILETYPE_APPLICATION;
				break;
			default:
				$type = File::FILETYPE_UNKNOWN;
		}
		return $type;
	}

	/**
	 * However it happened, the properties of a file object which
	 * are persisted to the database are named different than the
	 * properties the driver returns in getFileInfo.
	 * Therefore a mapping must happen.
	 *
	 * @param array $fileInfo
	 *
	 * @return array
	 */
	protected function transformFromDriverFileInfoArrayToFileObjectFormat(array $fileInfo) {
		$mappingInfo = array(
			// 'driverKey' => 'fileProperty' Key is from the driver, value is for the property in the file
			'size' => 'size',
			'atime' => NULL,
			'mtime' => 'modification_date',
			'ctime' => 'creation_date',
			'mimetype' => 'mime_type'
		);
		$mappedFileInfo = array();
		foreach ($fileInfo as $key => $value) {
			if (array_key_exists($key, $mappingInfo)) {
				if ($mappingInfo[$key] !== NULL) {
					$mappedFileInfo[$mappingInfo[$key]] = $value;
				}
			} else {
				$mappedFileInfo[$key] = $value;
			}
		}
		return $mappedFileInfo;
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
	 * Returns an instance of the FileIndexRepository
	 *
	 * @return MetaDataRepository
	 */
	protected function getMetaDataRepository() {
		return MetaDataRepository::getInstance();
	}

	/**
	 * Returns the ResourceFactory
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected function getResourceFactory() {
		return \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
	}

	/**
	 * Returns an instance of the FileIndexRepository
	 *
	 * @return ExtractorRegistry
	 */
	protected function getExtractorRegistry() {
		return ExtractorRegistry::getInstance();
	}
}
