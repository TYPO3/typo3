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
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;

/**
 * A representation for an inaccessible folder.
 *
 * If a folder has execution rights you can list it's contents
 * despite the access rights on the subfolders. If a subfolder
 * has no rights it has to be shown anyhow, but marked as
 * inaccessible.
 */
class InaccessibleFolder extends Folder
{
    /**
     * Throws an Exception,
     * used to prevent duplicate code in all the methods
     *
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    protected function throwInaccessibleException(): never
    {
        throw new InsufficientFolderReadPermissionsException(
            'You are trying to use a method on the inaccessible folder "' . $this->getName() . '".',
            1390290029
        );
    }

    /**
     * Sets a new name of the folder
     * currently this does not trigger the "renaming process"
     * as the name is more seen as a label
     *
     * @param string $name The new name
     */
    public function setName($name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns a publicly accessible URL for this folder
     *
     * WARNING: Access to the folder may be restricted by further means, e.g. some
     * web-based authentication. You have to take care of this yourself.
     */
    public function getPublicUrl(): never
    {
        $this->throwInaccessibleException();
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
     * @param string $sort
     * @param bool $sortRev
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getFiles($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false, $sort = '', $sortRev = false): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns amount of all files within this folder, optionally filtered by
     * the given pattern
     *
     * @param bool $recursive
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getFileCount(array $filterMethods = [], $recursive = false): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns the object for a subfolder of the current folder, if it exists.
     *
     * @param string $name Name of the subfolder
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getSubfolder($name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns a list of subfolders
     *
     * @param int $start The item to start at
     * @param int $numberOfItems The number of items to return
     * @param int $filterMode The filter mode to use for the filelist.
     * @param bool $recursive
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getSubfolders($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Adds a file from the local server disk. If the file already exists and
     * overwriting is disabled,
     *
     * @param string $localFilePath
     * @param string $fileName
     * @param string|DuplicationBehavior $conflictMode
     * @throws Exception\InsufficientFolderReadPermissionsException
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function addFile($localFilePath, $fileName = null, $conflictMode = DuplicationBehavior::CANCEL): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Adds an uploaded file into the Storage.
     *
     * @param array|UploadedFileInterface $uploadedFileData contains information about the uploaded file given by $_FILES['file1']
     * @param string|DuplicationBehavior $conflictMode
     * @throws Exception\InsufficientFolderReadPermissionsException
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function addUploadedFile(array|UploadedFileInterface $uploadedFileData, $conflictMode = DuplicationBehavior::CANCEL): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Renames this folder.
     *
     * @param string $newName
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function rename($newName): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Deletes this folder from its storage. This also means that this object becomes useless.
     *
     * @param bool $deleteRecursively
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function delete($deleteRecursively = true): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Creates a new blank file
     *
     * @param string $fileName
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function createFile($fileName): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Creates a new folder
     *
     * @param string $folderName
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function createFolder($folderName): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Copies folder to a target folder
     *
     * @param Folder $targetFolder Target folder to copy to.
     * @param string $targetFolderName an optional destination fileName
     * @param string|DuplicationBehavior $conflictMode
     * @throws Exception\InsufficientFolderReadPermissionsException
     * @todo change $conflictMode parameter type to DuplicationBehavior in TYPO3 v14.0
     */
    public function copyTo(Folder $targetFolder, $targetFolderName = null, $conflictMode = DuplicationBehavior::RENAME): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Moves folder to a target folder
     *
     * @param Folder $targetFolder Target folder to move to.
     * @param string $targetFolderName an optional destination fileName
     * @param string|DuplicationBehavior $conflictMode
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function moveTo(Folder $targetFolder, $targetFolderName = null, $conflictMode = DuplicationBehavior::RENAME): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Checks if a file exists in this folder
     *
     * @param string $name
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function hasFile($name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Checks if a folder exists in this folder.
     *
     * @param string $name
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function hasFolder($name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Updates the properties of this folder, e.g. after re-indexing or moving it.
     *
     * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
     *
     * @param array $properties
     * @internal
     */
    public function updateProperties(array $properties): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * Sets the filters to use when listing files. These are only used if the filter mode is one of
     * FILTER_MODE_USE_OWN_FILTERS and FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS
     */
    public function setFileAndFolderNameFilters(array $filters): never
    {
        $this->throwInaccessibleException();
    }

    public function getModificationTime(): int
    {
        return 0;
    }

    public function getCreationTime(): int
    {
        return 0;
    }
}
