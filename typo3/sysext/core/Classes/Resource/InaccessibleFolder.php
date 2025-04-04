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
 * If a folder has execution rights you can list its contents
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
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function setName(string $name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getPublicUrl(): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getFiles(int $start = 0, int $numberOfItems = 0, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, bool $recursive = false, string $sort = '', bool $sortRev = false): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getFileCount(array $filterMethods = [], bool $recursive = false): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getSubfolder(string $name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function getSubfolders(int $start = 0, int $numberOfItems = 0, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, bool $recursive = false): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function addFile(string $localFilePath, ?string $fileName = null, DuplicationBehavior $conflictMode = DuplicationBehavior::CANCEL): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function addUploadedFile(array|UploadedFileInterface $uploadedFileData, DuplicationBehavior $conflictMode = DuplicationBehavior::CANCEL): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function rename(string $newName): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function delete(bool $deleteRecursively = true): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function createFile(string $fileName): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function createFolder(string $folderName): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function copyTo(Folder $targetFolder, ?string $targetFolderName = null, DuplicationBehavior $conflictMode = DuplicationBehavior::RENAME): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function moveTo(Folder $targetFolder, ?string $targetFolderName = null, DuplicationBehavior $conflictMode = DuplicationBehavior::RENAME): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function hasFile(string $name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @throws Exception\InsufficientFolderReadPermissionsException
     */
    public function hasFolder(string $name): never
    {
        $this->throwInaccessibleException();
    }

    /**
     * @internal
     */
    public function updateProperties(array $properties): never
    {
        $this->throwInaccessibleException();
    }

    public function setFileAndFolderNameFilters(array $filters): never
    {
        $this->throwInaccessibleException();
    }

    public function getModificationTime(): int
    {
        return 0;
    }

    public function getReadablePath(?string $rootId = null): string
    {
        return '';
    }

    public function getCreationTime(): int
    {
        return 0;
    }
}
