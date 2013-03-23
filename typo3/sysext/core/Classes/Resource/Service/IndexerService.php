<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
/**
 * Indexer for the virtual file system
 * should only be accessed through the FileRepository for now
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class IndexerService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository
	 */
	protected $repository;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected $factory;

	/**
	 * empty constructor, nothing to do here yet
	 */
	public function __construct() {

	}

	/**
	 * Internal function to retrieve the file repository,
	 * if it does not exist, an instance will be created
	 *
	 * @return \TYPO3\CMS\Core\Resource\FileRepository
	 */
	protected function getRepository() {
		if ($this->repository === NULL) {
			$this->repository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		}
		return $this->repository;
	}

	/**
	 * Setter function for the fileFactory
	 * returns the object itself for chaining purposes
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceFactory $factory
	 * @return \TYPO3\CMS\Core\Resource\Service\IndexerService
	 */
	public function setFactory(\TYPO3\CMS\Core\Resource\ResourceFactory $factory) {
		$this->factory = $factory;
		return $this;
	}

	/**
	 * Creates or updates a file index entry from a file object.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject
	 * @param bool $updateObject Set this to FALSE to get the indexed values. You have to take care of updating the object yourself then!
	 * @return \TYPO3\CMS\Core\Resource\File|array the indexed $fileObject or an array of indexed properties.
	 */
	public function indexFile(\TYPO3\CMS\Core\Resource\File $fileObject, $updateObject = TRUE) {
		// Get the file information of this object
		$fileInfo = $this->gatherFileInformation($fileObject);
		// Signal slot BEFORE the file was indexed
		$this->emitPreFileIndexSignal($fileObject, $fileInfo);
		// @todo: this should be done via services in the future
		// @todo: this should take remote services into account
		if ($fileInfo['type'] == $fileObject::FILETYPE_IMAGE && !$fileInfo['width']) {
			$rawFileLocation = $fileObject->getForLocalProcessing(FALSE);
			list($fileInfo['width'], $fileInfo['height']) = getimagesize($rawFileLocation);
		}
		// If the file is already indexed, then the file information will
		// be updated on the existing record
		if ($fileObject->isIndexed()) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file', sprintf('uid = %d', $fileObject->getUid()), $fileInfo);
		} else {
			// Check if a file has been moved outside of FAL -- we have some
			// orphaned index record in this case we could update
			$otherFiles = $this->getRepository()->findBySha1Hash($fileInfo['sha1']);
			$movedFile = FALSE;
			/** @var $otherFile \TYPO3\CMS\Core\Resource\File */
			foreach ($otherFiles as $otherFile) {
				if (!$otherFile->exists()) {
					// @todo: create a log entry
					$movedFile = TRUE;
					$otherFile->updateProperties($fileInfo);
					$this->getRepository()->update($otherFile);
					$fileInfo['uid'] = $otherFile->getUid();
					$fileObject = $otherFile;
					// Skip the rest of the files here as we might have more files that are missing, but we can only
					// have one entry. The optimal solution would be to merge these records then, but this requires
					// some more advanced logic that we currently have not implemented.
					break;
				}
			}
			// File was not moved, so it is a new index record
			if ($movedFile === FALSE) {
				// Crdate and tstamp should not be present when updating
				// the file object, as they only relate to the index record
				$additionalInfo = array(
					'crdate' => $GLOBALS['EXEC_TIME'],
					'tstamp' => $GLOBALS['EXEC_TIME']
				);
				if (TYPO3_MODE === 'BE') {
					$additionalInfo['cruser_id'] = intval($GLOBALS['BE_USER']->user['uid']);
				}
				$indexRecord = array_merge($fileInfo, $additionalInfo);

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file', $indexRecord);
				$fileInfo['uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
			}
		}
		// Check for an error during the execution and throw an exception
		$error = $GLOBALS['TYPO3_DB']->sql_error();
		if ($error) {
			throw new \RuntimeException('Error during file indexing: "' . $error . '"', 1314455642);
		}
		// Signal slot AFTER the file was indexed
		$this->emitPostFileIndexSignal($fileObject, $fileInfo);
		if ($updateObject) {
			$fileObject->updateProperties($fileInfo);
			return $fileObject;
		} else {
			return $fileInfo;
		}
	}

	/**
	 * Indexes an array of file objects
	 * currently this is done in a simple way, however could be changed to be more performant
	 *
	 * @param \TYPO3\CMS\Core\Resource\File[] $fileObjects
	 * @return void
	 */
	public function indexFiles(array $fileObjects) {
		// emit signal
		$this->emitPreMultipleFilesIndexSignal($fileObjects);
		foreach ($fileObjects as $fileObject) {
			$this->indexFile($fileObject);
		}
		// emit signal
		$this->emitPostMultipleFilesIndexSignal($fileObjects);
	}

	/**
	 * Indexes all files in a given storage folder.
	 * currently this is done in a simple way, however could be changed to be more performant
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return int The number of indexed files.
	 */
	public function indexFilesInFolder(\TYPO3\CMS\Core\Resource\Folder $folder) {
		$numberOfIndexedFiles = 0;
		// Index all files in this folder
		$fileObjects = $folder->getFiles();
		// emit signal
		$this->emitPreMultipleFilesIndexSignal($fileObjects);
		foreach ($fileObjects as $fileObject) {
			$this->indexFile($fileObject);
			$numberOfIndexedFiles++;
		}
		// emit signal
		$this->emitPostMultipleFilesIndexSignal($fileObjects);
		// Call this function recursively for each subfolder
		$subFolders = $folder->getSubfolders();
		foreach ($subFolders as $subFolder) {
			$numberOfIndexedFiles += $this->indexFilesInFolder($subFolder);
		}
		return $numberOfIndexedFiles;
	}

	/**
	 * Fetches the information for a sys_file record
	 * based on a single file
	 * this function shouldn't be used, if someone needs to fetch the file information
	 * from a file object, should be done by getProperties etc
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file the file to fetch the information from
	 * @return array the file information as an array
	 */
	protected function gatherFileInformation(\TYPO3\CMS\Core\Resource\File $file) {
		$fileInfo = new \ArrayObject(array());
		$gatherDefaultInformation = new \stdClass();
		$gatherDefaultInformation->getDefaultFileInfo = 1;
		// signal before the files are modified
		$this->emitPreGatherFileInformationSignal($file, $fileInfo, $gatherDefaultInformation);
		// the check helps you to disable the regular file fetching,
		// so a signal could actually remotely access the service
		if ($gatherDefaultInformation->getDefaultFileInfo) {
			$storage = $file->getStorage();
			// TODO: See if we can't just return info, as it contains most of the
			// stuff we put together in array form again later here.
			$info = $storage->getFileInfo($file);
			$defaultFileInfo = array(
				'creation_date' => $info['ctime'],
				'modification_date' => $info['mtime'],
				'size' => $info['size'],
				'identifier' => $file->getIdentifier(),
				'storage' => $storage->getUid(),
				'name' => $file->getName(),
				'sha1' => $storage->hashFile($file, 'sha1'),
				'type' => $file->getType(),
				'mime_type' => $file->getMimeType(),
				'extension' => $file->getExtension()
			);
			$fileInfo = array_merge($defaultFileInfo, $fileInfo->getArrayCopy());
			$fileInfo = new \ArrayObject($fileInfo);
		}
		// signal after the file information is fetched
		$this->emitPostGatherFileInformationSignal($file, $fileInfo, $gatherDefaultInformation);
		return $fileInfo->getArrayCopy();
	}

	/**
	 * Signal that is called before the file information is fetched
	 * helpful if somebody wants to preprocess the record information
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject
	 * @param array $fileInfo
	 * @param boolean $gatherDefaultInformation
	 * @signal
	 */
	protected function emitPreGatherFileInformationSignal(\TYPO3\CMS\Core\Resource\File $fileObject, $fileInfo, $gatherDefaultInformation) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', 'preGatherFileInformation', array($fileObject, $fileInfo, $gatherDefaultInformation));
	}

	/**
	 * Signal that is called after a file object was indexed
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject
	 * @param array $fileInfo
	 * @param boolean $hasGatheredDefaultInformation
	 * @signal
	 */
	protected function emitPostGatherFileInformationSignal(\TYPO3\CMS\Core\Resource\File $fileObject, $fileInfo, $hasGatheredDefaultInformation) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', 'postGatherFileInformation', array($fileObject, $fileInfo, $hasGatheredDefaultInformation));
	}

	/**
	 * Signal that is called before a bunch of file objects are indexed
	 *
	 * @param array $fileObject
	 * @signal
	 */
	protected function emitPreMultipleFilesIndexSignal(array $fileObjectsToIndex) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', 'preMultipleFileIndex', array($fileObjectsToIndex));
	}

	/**
	 * Signal that is called after multiple file objects were indexed
	 *
	 * @param array $fileObjectsToIndex
	 * @signal
	 */
	protected function emitPostMultipleFilesIndexSignal(array $fileObjectsToIndex) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', 'postMultipleFileIndex', array($fileObjectsToIndex));
	}

	/**
	 * Signal that is called before a file object was indexed
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject
	 * @param array $fileInfo
	 * @signal
	 */
	protected function emitPreFileIndexSignal(\TYPO3\CMS\Core\Resource\File $fileObject, $fileInfo) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', 'preFileIndex', array($fileObject, $fileInfo));
	}

	/**
	 * Signal that is called after a file object was indexed
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject
	 * @param array $fileInfo
	 * @signal
	 */
	protected function emitPostFileIndexSignal(\TYPO3\CMS\Core\Resource\File $fileObject, $fileInfo) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService', 'postFileIndex', array($fileObject, $fileInfo));
	}

	/**
	 * Get the SignalSlot dispatcher
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		return $this->getObjectManager()->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
	}

	/**
	 * Get the ObjectManager
	 *
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

}


?>