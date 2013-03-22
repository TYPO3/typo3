<?php
namespace TYPO3\CMS\Core\Resource;

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

use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A folder that groups files in a storage. This may be a folder on the local
 * disk, a bucket in Amazon S3 or a user or a tag in Flickr.
 *
 * This object is not persisted in TYPO3 locally, but created on the fly by
 * storage drivers for the folders they "offer".
 *
 * Some folders serve as a physical container for files (e.g. folders on the
 * local disk, S3 buckets or Flickr users). Other folders just group files by a
 * certain criterion, e.g. a tag.
 * The way this is implemented depends on the storage driver.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class Folder implements FolderInterface {

	/**
	 * The storage this folder belongs to.
	 *
	 * @var ResourceStorage
	 */
	protected $storage;

	/**
	 * The identifier of this folder to identify it on the storage.
	 * On some drivers, this is the path to the folder, but drivers could also just
	 * provide any other unique identifier for this folder on the specific storage.
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * The name of this folder
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The filters this folder should use for a file list.
	 *
	 * @var callable[]
	 */
	protected $fileAndFolderNameFilters = array();

	/**
	 * Modes for filter usage in getFiles()/getFolders()
	 */
	const FILTER_MODE_NO_FILTERS = 0;
	// Merge local filters into storage's filters
	const FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS = 1;
	// Only use the filters provided by the storage
	const FILTER_MODE_USE_STORAGE_FILTERS = 2;
	// Only use the filters provided by the current class
	const FILTER_MODE_USE_OWN_FILTERS = 3;

	/**
	 * Initialization of the folder
	 *
	 * @param ResourceStorage $storage
	 * @param $identifier
	 * @param $name
	 */
	public function __construct(ResourceStorage $storage, $identifier, $name) {
		$this->storage = $storage;
		$this->identifier = rtrim($identifier, '/') . '/';
		$this->name = $name;
	}

	/**
	 * Returns the name of this folder.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets a new name of the folder
	 * currently this does not trigger the "renaming process"
	 * as the name is more seen as a label
	 *
	 * @param string $name The new name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the storage this folder belongs to.
	 *
	 * @return ResourceStorage
	 */
	public function getStorage() {
		return $this->storage;
	}

	/**
	 * Returns the path of this folder inside the storage. It depends on the
	 * type of storage whether this is a real path or just some unique identifier.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Returns a combined identifier of this folder, i.e. the storage UID and
	 * the folder identifier separated by a colon ":".
	 *
	 * @return string Combined storage and folder identifier, e.g. StorageUID:folder/path/
	 */
	public function getCombinedIdentifier() {
		// @todo $this->properties is never defined nor used here
		if (is_array($this->properties) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->properties['storage'])) {
			$combinedIdentifier = $this->properties['storage'] . ':' . $this->getIdentifier();
		} else {
			$combinedIdentifier = $this->getStorage()->getUid() . ':' . $this->getIdentifier();
		}
		return $combinedIdentifier;
	}

	/**
	 * Returns a publicly accessible URL for this folder
	 *
	 * WARNING: Access to the folder may be restricted by further means, e.g. some
	 * web-based authentication. You have to take care of this yourself.
	 *
	 * @param boolean $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl($relativeToCurrentScript = FALSE) {
		return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
	}

	/**
	 * Returns a list of files in this folder, optionally filtered. There are several filter modes available, see the
	 * FILTER_MODE_* constants for more information.
	 *
	 * For performance reasons the returned items can also be limited to a given range
	 *
	 * @param integer $start The item to start at
	 * @param integer $numberOfItems The number of items to return
	 * @param integer $filterMode The filter mode to use for the file list.
	 * @param boolean $recursive
	 * @return File[]
	 */
	public function getFiles($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = FALSE) {
		$useFilters = TRUE;

		// Fallback for compatibility with the old method signature variable $useFilters that was used instead of $filterMode
		if ($filterMode === TRUE) {
			$filterMode = self::FILTER_MODE_USE_STORAGE_FILTERS;
		} elseif ($filterMode === FALSE) {
			$useFilters = FALSE;
		} else {
			list($backedUpFilters, $useFilters) = $this->prepareFiltersInStorage($filterMode);
		}

		/** @var $factory ResourceFactory */
		$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$fileArray = $this->storage->getFileList($this->identifier, $start, $numberOfItems, $useFilters, TRUE, $recursive);
		$fileObjects = array();
		foreach ($fileArray as $fileInfo) {
			$fileObjects[$fileInfo['name']] = $factory->createFileObject($fileInfo);
		}

		$this->restoreBackedUpFiltersInStorage($backedUpFilters);

		return $fileObjects;
	}

	/**
	 * Returns amount of all files within this folder, optionally filtered by
	 * the given pattern
	 *
	 * @param array $filterMethods
	 * @param boolean $recursive
	 *
	 * @return integer
	 */
	public function getFileCount(array $filterMethods = array(), $recursive = FALSE) {
		// TODO replace by call to count()
		return count($this->storage->getFileList($this->identifier, 0, 0, $filterMethods, FALSE, $recursive));
	}

	/**
	 * Returns the object for a subfolder of the current folder, if it exists.
	 *
	 * @param string $name Name of the subfolder
	 *
	 * @throws \InvalidArgumentException
	 * @return Folder
	 */
	public function getSubfolder($name) {
		if (!$this->storage->hasFolderInFolder($name, $this)) {
			throw new \InvalidArgumentException('Folder "' . $name . '" does not exist in "' . $this->identifier . '"', 1329836110);
		}
		/** @var $factory ResourceFactory */
		$factory = ResourceFactory::getInstance();
		$folderObject = $factory->createFolderObject($this->storage, $this->identifier . $name . '/', $name);
		return $folderObject;
	}

	/**
	 * Returns a list of subfolders
	 *
	 * @param integer $start The item to start at
	 * @param integer $numberOfItems The number of items to return
	 * @param integer $filterMode The filter mode to use for the file list.
	 * @return Folder[]
	 */
	public function getSubfolders($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS) {
		list($backedUpFilters, $useFilters) = $this->prepareFiltersInStorage($filterMode);

		$folderObjects = array();
		$folderArray = $this->storage->getFolderList($this->identifier, $start, $numberOfItems, $useFilters);
		if (count($folderArray) > 0) {
			/** @var $factory ResourceFactory */
			$factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
			foreach ($folderArray as $folder) {
				$folderObjects[$folder['name']] = $factory->createFolderObject($this->storage, $folder['identifier'], $folder['name']);
			}
		}

		$this->restoreBackedUpFiltersInStorage($backedUpFilters);

		return $folderObjects;
	}

	/**
	 * Adds a file from the local server disk. If the file already exists and
	 * overwriting is disabled,
	 *
	 * @param string $localFilePath
	 * @param string $fileName
	 * @param string $conflictMode possible value are 'cancel', 'replace'
	 * @return File The file object
	 */
	public function addFile($localFilePath, $fileName = NULL, $conflictMode = 'cancel') {
		$fileName = $fileName ? $fileName : PathUtility::basename($localFilePath);
		return $this->storage->addFile($localFilePath, $this, $fileName, $conflictMode);
	}

	/**
	 * Adds an uploaded file into the Storage.
	 *
	 * @param array $uploadedFileData contains information about the uploaded file given by $_FILES['file1']
	 * @param string $conflictMode possible value are 'cancel', 'replace'
	 * @return File The file object
	 */
	public function addUploadedFile(array $uploadedFileData, $conflictMode = 'cancel') {
		return $this->storage->addUploadedFile($uploadedFileData, $this, $uploadedFileData['name'], $conflictMode);
	}

	/**
	 * Renames this folder.
	 *
	 * @param string $newName
	 * @return Folder
	 */
	public function rename($newName) {
		return $this->storage->renameFolder($this, $newName);
	}

	/**
	 * Deletes this folder from its storage. This also means that this object becomes useless.
	 *
	 * @param boolean $deleteRecursively
	 * @return boolean TRUE if deletion succeeded
	 */
	public function delete($deleteRecursively = TRUE) {
		return $this->storage->deleteFolder($this, $deleteRecursively);
	}

	/**
	 * Creates a new blank file
	 *
	 * @param string $fileName
	 * @return File The new file object
	 */
	public function createFile($fileName) {
		return $this->storage->createFile($fileName, $this);
	}

	/**
	 * Creates a new folder
	 *
	 * @param string $folderName
	 * @return Folder The new folder object
	 */
	public function createFolder($folderName) {
		return $this->storage->createFolder($folderName, $this);
	}

	/**
	 * Copies folder to a target folder
	 *
	 * @param Folder $targetFolder Target folder to copy to.
	 * @param string $targetFolderName an optional destination fileName
	 * @param string $conflictMode "overrideExistingFile", "renameNewFile" or "cancel
	 * @return Folder New (copied) folder object.
	 */
	public function copyTo(Folder $targetFolder, $targetFolderName = NULL, $conflictMode = 'renameNewFile') {
		return $targetFolder->getStorage()->copyFolder($this, $targetFolder, $targetFolderName, $conflictMode);
	}

	/**
	 * Moves folder to a target folder
	 *
	 * @param Folder $targetFolder Target folder to move to.
	 * @param string $targetFolderName an optional destination fileName
	 * @param string $conflictMode "overrideExistingFile", "renameNewFile" or "cancel
	 * @return Folder New (copied) folder object.
	 */
	public function moveTo(Folder $targetFolder, $targetFolderName = NULL, $conflictMode = 'renameNewFile') {
		return $targetFolder->getStorage()->moveFolder($this, $targetFolder, $targetFolderName, $conflictMode);
	}

	/**
	 * Checks if a file exists in this folder
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasFile($name) {
		return $this->storage->hasFileInFolder($name, $this);
	}

	/**
	 * Checks if a folder exists in this folder.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasFolder($name) {
		return $this->storage->hasFolderInFolder($name, $this);
	}

	/**
	 * Check if a file operation (= action) is allowed on this folder
	 *
	 * @param string $action Action that can be read, write or delete
	 * @return boolean
	 */
	public function checkActionPermission($action) {
		return $this->getStorage()->checkFolderActionPermission($action, $this);
	}

	/**
	 * Updates the properties of this folder, e.g. after re-indexing or moving it.
	 *
	 * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
	 *
	 * @param array $properties
	 * @return void
	 * @internal
	 */
	public function updateProperties(array $properties) {
		// Setting identifier and name to update values
		if (isset($properties['identifier'])) {
			$this->identifier = $properties['identifier'];
		}
		if (isset($properties['name'])) {
			$this->name = $properties['name'];
		}
	}

	/**
	 * Prepares the filters in this folder's storage according to a set filter mode.
	 *
	 * @param integer $filterMode The filter mode to use; one of the FILTER_MODE_* constants
	 * @return array The backed up filters as an array (NULL if filters were not backed up) and whether to use filters or not (boolean)
	 */
	protected function prepareFiltersInStorage($filterMode) {
		$backedUpFilters = NULL;
		$useFilters = TRUE;

		switch ($filterMode) {
			case self::FILTER_MODE_USE_OWN_FILTERS:
				$backedUpFilters = $this->storage->getFileAndFolderNameFilters();
				$this->storage->setFileAndFolderNameFilters($this->fileAndFolderNameFilters);

				break;

			case self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS:
				if (count($this->fileAndFolderNameFilters) > 0) {
					$backedUpFilters = $this->storage->getFileAndFolderNameFilters();
					foreach ($this->fileAndFolderNameFilters as $filter) {
						$this->storage->addFileAndFolderNameFilter($filter);
					}
				}

				break;

			case self::FILTER_MODE_USE_STORAGE_FILTERS:
				// nothing to do here

				break;

			case self::FILTER_MODE_NO_FILTERS:
				$useFilters = FALSE;

				break;
		}
		return array($backedUpFilters, $useFilters);
	}

	/**
	 * Restores the filters of a storage.
	 *
	 * @param array $backedUpFilters The filters to restore; might be NULL if no filters have been backed up, in
	 *                               which case this method does nothing.
	 * @see prepareFiltersInStorage()
	 */
	protected function restoreBackedUpFiltersInStorage($backedUpFilters) {
		if ($backedUpFilters !== NULL) {
			$this->storage->setFileAndFolderNameFilters($backedUpFilters);
		}
	}

	/**
	 * Sets the filters to use when listing files. These are only used if the filter mode is one of
	 * FILTER_MODE_USE_OWN_FILTERS and FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS
	 *
	 * @param array $filters
	 */
	public function setFileAndFolderNameFilters(array $filters) {
		$this->fileAndFolderNameFilters = $filters;
	}

	/**
	 * Returns the role of this folder (if any). See FolderInterface::ROLE_* constants for possible values.
	 *
	 * @return integer
	 */
	public function getRole() {
		return $this->storage->getRole($this);
	}
}

?>