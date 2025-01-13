<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Resource;

use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Search\Result\FileSearchResultInterface;
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
 */
class Folder implements FolderInterface
{
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
     * The filters this folder should use for a filelist.
     *
     * @var callable[]
     */
    protected $fileAndFolderNameFilters = [];

    /**
     * Modes for filter usage in getFiles()/getFolders()
     */
    public const FILTER_MODE_NO_FILTERS = 0;
    // Merge local filters into storage's filters
    public const FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS = 1;
    // Only use the filters provided by the storage
    public const FILTER_MODE_USE_STORAGE_FILTERS = 2;
    // Only use the filters provided by the current class
    public const FILTER_MODE_USE_OWN_FILTERS = 3;

    /**
     * Initialization of the folder
     *
     * @param string $identifier
     * @param string $name
     */
    public function __construct(ResourceStorage $storage, $identifier, $name)
    {
        $this->storage = $storage;
        $this->identifier = $identifier;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the full path of this folder, from the root.
     *
     * @param string $rootId ID of the root folder, NULL to auto-detect
     *
     * @return string
     */
    public function getReadablePath($rootId = null)
    {
        if ($rootId === null) {
            // Find first matching filemount and use that as root
            foreach ($this->storage->getFileMounts() as $fileMount) {
                if ($this->storage->isWithinFolder($fileMount['folder'], $this)) {
                    $rootId = $fileMount['folder']->getIdentifier();
                    break;
                }
            }
            if ($rootId === null) {
                $rootId = $this->storage->getRootLevelFolder()->getIdentifier();
            }
        }
        $readablePath = '/';
        if ($this->identifier !== $rootId) {
            try {
                $readablePath = $this->getParentFolder()->getReadablePath($rootId);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                // May no access to parent folder (e.g. because of mount point)
                $readablePath = '/';
            }
        }
        return $readablePath . ($this->name ? $this->name . '/' : '');
    }

    /**
     * Sets a new name of the folder
     * currently this does not trigger the "renaming process"
     * as the name is more seen as a label
     *
     * @param string $name The new name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getStorage(): ResourceStorage
    {
        return $this->storage;
    }

    /**
     * Returns the path of this folder inside the storage. It depends on the
     * type of storage whether this is a real path or just some unique identifier.
     *
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getHashedIdentifier(): string
    {
        return $this->storage->hashFileIdentifier($this->identifier);
    }

    /**
     * Returns a combined identifier of this folder, i.e. the storage UID and
     * the folder identifier separated by a colon ":".
     *
     * @return string Combined storage and folder identifier, e.g. StorageUID:folder/path/
     */
    public function getCombinedIdentifier()
    {
        return $this->getStorage()->getUid() . ':' . $this->getIdentifier();
    }

    /**
     * Returns a publicly accessible URL for this folder
     *
     * WARNING: Access to the folder may be restricted by further means, e.g. some
     * web-based authentication. You have to take care of this yourself.
     *
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl()
    {
        return $this->getStorage()->getPublicUrl($this);
    }

    /**
     * Returns a list of files in this folder, optionally filtered. There are several filter modes available, see the
     * FILTER_MODE_* constants for more information.
     *
     * For performance reasons the returned items can also be limited to a given range
     *
     * @param int $start The item to start at
     * @param int $numberOfItems The number of items to return
     * @param int $filterMode The filter mode to use for the filelist.
     * @param bool $recursive
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return \TYPO3\CMS\Core\Resource\File[]
     */
    public function getFiles($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false, $sort = '', $sortRev = false)
    {
        // Fallback for compatibility with the old method signature variable $useFilters that was used instead of $filterMode
        if ($filterMode === false) {
            $useFilters = false;
            $backedUpFilters = [];
        } else {
            [$backedUpFilters, $useFilters] = $this->prepareFiltersInStorage($filterMode);
        }

        $fileObjects = $this->storage->getFilesInFolder($this, $start, $numberOfItems, $useFilters, $recursive, $sort, $sortRev);

        $this->restoreBackedUpFiltersInStorage($backedUpFilters);

        return $fileObjects;
    }

    /**
     * Returns a file search result based on the given demand.
     * The result also includes matches in meta data fields that are defined in TCA.
     *
     * @param int $filterMode The filter mode to use for the found files
     */
    public function searchFiles(FileSearchDemand $searchDemand, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS): FileSearchResultInterface
    {
        [$backedUpFilters, $useFilters] = $this->prepareFiltersInStorage($filterMode);
        $searchResult = $this->storage->searchFiles($searchDemand, $this, $useFilters);
        $this->restoreBackedUpFiltersInStorage($backedUpFilters);

        return $searchResult;
    }

    /**
     * Returns amount of all files within this folder, optionally filtered by
     * the given pattern
     *
     * @param bool $recursive
     * @return int
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFileCount(array $filterMethods = [], $recursive = false)
    {
        return $this->storage->countFilesInFolder($this, true, $recursive);
    }

    /**
     * Returns the object for a subfolder of the current folder, if it exists.
     *
     * @throws \InvalidArgumentException
     */
    public function getSubfolder(string $name): Folder
    {
        if (!$this->storage->hasFolderInFolder($name, $this)) {
            throw new \InvalidArgumentException('Folder "' . $name . '" does not exist in "' . $this->identifier . '"', 1329836110);
        }
        return $this->storage->getFolderInFolder($name, $this);
    }

    /**
     * @param int $start The item to start at
     * @param int $numberOfItems The number of items to return
     * @param int $filterMode The filter mode to use for the filelist.
     * @param bool $recursive
     * @phpstan-return array<array-key, Folder>
     */
    public function getSubfolders($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false): array
    {
        [$backedUpFilters, $useFilters] = $this->prepareFiltersInStorage($filterMode);
        $folderObjects = $this->storage->getFoldersInFolder($this, $start, $numberOfItems, $useFilters, $recursive);
        $this->restoreBackedUpFiltersInStorage($backedUpFilters);
        return $folderObjects;
    }

    /**
     * Adds a file from the local server disk. If the file already exists and
     * overwriting is disabled,
     *
     * @param string $localFilePath
     * @param string $fileName
     * @param string|DuplicationBehavior $conflictMode
     * @return File The file object
     * @throws ExistingTargetFileNameException
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function addFile($localFilePath, $fileName = null, $conflictMode = DuplicationBehavior::CANCEL)
    {
        $fileName = $fileName ?: PathUtility::basename($localFilePath);

        if (!$conflictMode instanceof DuplicationBehavior) {
            trigger_error(
                'Using the non-native enumeration TYPO3\CMS\Core\Resource\DuplicationBehavior in Folder->addFile()'
                . ' will stop working in TYPO3 v14.0. Use native TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior instead.',
                E_USER_DEPRECATED
            );
            $conflictMode = DuplicationBehavior::tryFrom($conflictMode) ?? DuplicationBehavior::getDefaultDuplicationBehaviour();
        }

        return $this->storage->addFile($localFilePath, $this, $fileName, $conflictMode);
    }

    /**
     * Adds an uploaded file into the Storage.
     *
     * @param array|UploadedFileInterface $uploadedFileData Information about the uploaded file given by $_FILES['file1']
     *                                                      or a PSR-7 UploadedFileInterface object
     * @param string|DuplicationBehavior $conflictMode
     * @return FileInterface The file object
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function addUploadedFile(array|UploadedFileInterface $uploadedFileData, $conflictMode = DuplicationBehavior::CANCEL)
    {
        if (!$conflictMode instanceof DuplicationBehavior) {
            trigger_error(
                'Using the non-native enumeration TYPO3\CMS\Core\Resource\DuplicationBehavior in Folder->addUploadedFile()'
                . ' will stop working in TYPO3 v14.0. Use native TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior instead.',
                E_USER_DEPRECATED
            );
            $conflictMode = DuplicationBehavior::tryFrom($conflictMode) ?? DuplicationBehavior::getDefaultDuplicationBehaviour();
        }

        return $this->storage->addUploadedFile($uploadedFileData, $this, null, $conflictMode);
    }

    /**
     * Renames this folder.
     */
    public function rename(string $newName): self
    {
        return $this->storage->renameFolder($this, $newName);
    }

    /**
     * Deletes this folder from its storage. This also means that this object becomes useless.
     *
     * @param bool $deleteRecursively
     */
    public function delete($deleteRecursively = true): bool
    {
        return $this->storage->deleteFolder($this, $deleteRecursively);
    }

    /**
     * Creates a new blank file
     *
     * @param string $fileName
     * @return File The new file object
     */
    public function createFile($fileName)
    {
        return $this->storage->createFile($fileName, $this);
    }

    /**
     * Creates a new folder
     *
     * @param string $folderName
     * @return Folder The new folder object
     */
    public function createFolder($folderName)
    {
        return $this->storage->createFolder($folderName, $this);
    }

    /**
     * Copies folder to a target folder
     *
     * @param Folder $targetFolder Target folder to copy to.
     * @param string $targetFolderName an optional destination fileName
     * @param string|DuplicationBehavior $conflictMode
     * @return Folder New (copied) folder object.
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function copyTo(Folder $targetFolder, $targetFolderName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        if (!$conflictMode instanceof DuplicationBehavior) {
            trigger_error(
                'Using the non-native enumeration TYPO3\CMS\Core\Resource\DuplicationBehavior in Folder->copyTo()'
                . ' will stop working in TYPO3 v14.0. Use native TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior instead.',
                E_USER_DEPRECATED
            );
            $conflictMode = DuplicationBehavior::tryFrom($conflictMode) ?? DuplicationBehavior::getDefaultDuplicationBehaviour();
        }

        return $targetFolder->getStorage()->copyFolder($this, $targetFolder, $targetFolderName, $conflictMode);
    }

    /**
     * Moves folder to a target folder
     *
     * @param Folder $targetFolder Target folder to move to.
     * @param string $targetFolderName an optional destination fileName
     * @param string|DuplicationBehavior $conflictMode
     * @return Folder New (copied) folder object.
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function moveTo(Folder $targetFolder, $targetFolderName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        if (!$conflictMode instanceof DuplicationBehavior) {
            trigger_error(
                'Using the non-native enumeration TYPO3\CMS\Core\Resource\DuplicationBehavior in Folder->moveTo()'
                . ' will stop working in TYPO3 v14.0. Use native TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior instead.',
                E_USER_DEPRECATED
            );
            $conflictMode = DuplicationBehavior::tryFrom($conflictMode) ?? DuplicationBehavior::getDefaultDuplicationBehaviour();
        }

        return $targetFolder->getStorage()->moveFolder($this, $targetFolder, $targetFolderName, $conflictMode);
    }

    /**
     * Checks if a file exists in this folder
     */
    public function hasFile(string $name): bool
    {
        return $this->storage->hasFileInFolder($name, $this);
    }

    /**
     * Fetches a file from a folder, must be a direct descendant of a folder.
     */
    public function getFile(string $fileName): ?FileInterface
    {
        if ($this->storage->hasFileInFolder($fileName, $this)) {
            return $this->storage->getFileInFolder($fileName, $this);
        }
        return null;
    }

    /**
     * Checks if a folder exists in this folder.
     */
    public function hasFolder(string $name): bool
    {
        return $this->storage->hasFolderInFolder($name, $this);
    }

    /**
     * Check if a file operation (= action) is allowed on this folder
     *
     * @param string $action Action that can be read, write or delete
     * @return bool
     */
    public function checkActionPermission($action)
    {
        try {
            return $this->getStorage()->checkFolderActionPermission($action, $this);
        } catch (ResourcePermissionsUnavailableException $e) {
            return false;
        }
    }

    /**
     * Updates the properties of this folder, e.g. after re-indexing or moving it.
     *
     * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
     *
     * @param array $properties
     * @internal
     */
    public function updateProperties(array $properties)
    {
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
     * @param int $filterMode The filter mode to use; one of the FILTER_MODE_* constants
     * @return array The backed up filters as an array (NULL if filters were not backed up) and whether to use filters or not (bool)
     */
    protected function prepareFiltersInStorage($filterMode)
    {
        $backedUpFilters = null;
        $useFilters = true;

        switch ($filterMode) {
            case self::FILTER_MODE_USE_OWN_FILTERS:
                $backedUpFilters = $this->storage->getFileAndFolderNameFilters();
                $this->storage->setFileAndFolderNameFilters($this->fileAndFolderNameFilters);

                break;

            case self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS:
                if (!empty($this->fileAndFolderNameFilters)) {
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
                $useFilters = false;

                break;
        }
        return [$backedUpFilters, $useFilters];
    }

    /**
     * Restores the filters of a storage.
     *
     * @param array $backedUpFilters The filters to restore; might be NULL if no filters have been backed up, in
     *                               which case this method does nothing.
     * @see prepareFiltersInStorage()
     */
    protected function restoreBackedUpFiltersInStorage($backedUpFilters)
    {
        if ($backedUpFilters !== null) {
            $this->storage->setFileAndFolderNameFilters($backedUpFilters);
        }
    }

    /**
     * Sets the filters to use when listing files. These are only used if the filter mode is one of
     * FILTER_MODE_USE_OWN_FILTERS and FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS
     */
    public function setFileAndFolderNameFilters(array $filters)
    {
        $this->fileAndFolderNameFilters = $filters;
    }

    /**
     * Returns the role of this folder (if any). See FolderInterface::ROLE_* constants for possible values.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->storage->getRole($this);
    }

    /**
     * Returns the parent folder.
     *
     * In non-hierarchical storages, that always is the root folder.
     *
     * The parent folder of the root folder is the root folder.
     *
     * @throws InsufficientFolderAccessPermissionsException
     */
    public function getParentFolder(): FolderInterface
    {
        return $this->getStorage()->getFolder($this->getStorage()->getFolderIdentifierFromFileIdentifier($this->getIdentifier()));
    }

    /**
     * Returns the modification time of the file as Unix timestamp
     */
    public function getModificationTime(): int
    {
        return (int)$this->storage->getFolderInfo($this)['mtime'];
    }

    /**
     * Returns the creation time of the file as Unix timestamp
     */
    public function getCreationTime(): int
    {
        return (int)$this->storage->getFolderInfo($this)['ctime'];
    }
}
