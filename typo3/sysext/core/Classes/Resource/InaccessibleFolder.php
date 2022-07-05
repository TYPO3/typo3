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
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    protected function throwInaccessibleException()
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
     * @return never
     */
    public function setName($name)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns a publicly accessible URL for this folder
     *
     * WARNING: Access to the folder may be restricted by further means, e.g. some
     * web-based authentication. You have to take care of this yourself.
     *
     * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver). Deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     * @return never
     */
    public function getPublicUrl($relativeToCurrentScript = false)
    {
        // @deprecated $relativeToCurrentScript since v11, will be removed in TYPO3 v12.0
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
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getFiles($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false, $sort = '', $sortRev = false)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns amount of all files within this folder, optionally filtered by
     * the given pattern
     *
     * @param array $filterMethods
     * @param bool $recursive
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getFileCount(array $filterMethods = [], $recursive = false)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Returns the object for a subfolder of the current folder, if it exists.
     *
     * @param string $name Name of the subfolder
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getSubfolder($name)
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
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getSubfolders($start = 0, $numberOfItems = 0, $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive = false)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Adds a file from the local server disk. If the file already exists and
     * overwriting is disabled,
     *
     * @param string $localFilePath
     * @param string $fileName
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function addFile($localFilePath, $fileName = null, $conflictMode = DuplicationBehavior::CANCEL)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Adds an uploaded file into the Storage.
     *
     * @param array $uploadedFileData contains information about the uploaded file given by $_FILES['file1']
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function addUploadedFile(array $uploadedFileData, $conflictMode = DuplicationBehavior::CANCEL)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Renames this folder.
     *
     * @param string $newName
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function rename($newName)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Deletes this folder from its storage. This also means that this object becomes useless.
     *
     * @param bool $deleteRecursively
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function delete($deleteRecursively = true)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Creates a new blank file
     *
     * @param string $fileName
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function createFile($fileName)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Creates a new folder
     *
     * @param string $folderName
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function createFolder($folderName)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Copies folder to a target folder
     *
     * @param Folder $targetFolder Target folder to copy to.
     * @param string $targetFolderName an optional destination fileName
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function copyTo(Folder $targetFolder, $targetFolderName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Moves folder to a target folder
     *
     * @param Folder $targetFolder Target folder to move to.
     * @param string $targetFolderName an optional destination fileName
     * @param string $conflictMode a value of the DuplicationBehavior enumeration
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function moveTo(Folder $targetFolder, $targetFolderName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Checks if a file exists in this folder
     *
     * @param string $name
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function hasFile($name)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Checks if a folder exists in this folder.
     *
     * @param string $name
     * @return never
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function hasFolder($name)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Updates the properties of this folder, e.g. after re-indexing or moving it.
     *
     * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
     *
     * @param array $properties
     * @return never
     * @internal
     */
    public function updateProperties(array $properties)
    {
        $this->throwInaccessibleException();
    }

    /**
     * Sets the filters to use when listing files. These are only used if the filter mode is one of
     * FILTER_MODE_USE_OWN_FILTERS and FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS
     *
     * @param array $filters
     * @return never
     */
    public function setFileAndFolderNameFilters(array $filters)
    {
        $this->throwInaccessibleException();
    }

    /**
     * @return int
     */
    public function getModificationTime()
    {
        return 0;
    }

    /**
     * @return int
     */
    public function getCreationTime()
    {
        return 0;
    }
}
