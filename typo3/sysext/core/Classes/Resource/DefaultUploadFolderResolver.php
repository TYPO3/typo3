<?php

declare(strict_types=1);

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

/**
 * Finds the best matching upload folder for a specific backend user
 * when uploading or selecting files, based on UserTSconfig or PageTSconfig
 */
class DefaultUploadFolderResolver
{
    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function resolve(BackendUserAuthentication $user, int $pid = null, string $table = null, string $field = null): Folder | bool
    {
        $uploadFolder = $this->getDefaultUploadFolderForUser($user);
        $uploadFolder = $this->getDefaultUploadFolderForPage($pid) ?? $uploadFolder;

        $uploadFolder = $this->eventDispatcher->dispatch(
            new AfterDefaultUploadFolderWasResolvedEvent($uploadFolder, $pid, $table, $field)
        )->getUploadFolder() ?? $uploadFolder;

        $uploadFolder = $uploadFolder ?? $this->getDefaultUploadFolder($user);

        return $uploadFolder instanceof Folder ? $uploadFolder : false;
    }

    public function getDefaultUploadFolderForUser(BackendUserAuthentication $backendUser): ?Folder
    {
        $uploadFolder = $backendUser->getTSConfig()['options.']['defaultUploadFolder'] ?? '';

        return $this->resolveFolder($uploadFolder);
    }

    public function getDefaultUploadFolderForPage(?int $pid): ?Folder
    {
        $uploadFolder = BackendUtility::getPagesTSconfig($pid)['options.']['defaultUploadFolder'] ?? '';

        return $this->resolveFolder($uploadFolder);
    }

    protected function resolveFolder(string $uploadPath): ?Folder
    {
        $uploadFolder = null;

        if ($uploadPath) {
            try {
                $uploadFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($uploadPath);
            } catch (FolderDoesNotExistException $e) {
            }
        }

        return $uploadFolder;
    }

    /**
     * Detects the first default folder of the first storage that the backend user has access to.
     * If the default storage is not available, all other storages are then checked as well.
     *
     * @param BackendUserAuthentication $backendUser
     * @return Folder|null
     */
    protected function getDefaultUploadFolder(BackendUserAuthentication $backendUser): ?Folder
    {
        $uploadFolder = null;

        foreach ($backendUser->getFileStorages() as $storage) {
            if ($storage->isDefault() && $storage->isWritable()) {
                try {
                    $uploadFolder = $storage->getDefaultFolder();
                    if ($uploadFolder->checkActionPermission('write')) {
                        break;
                    }
                    $uploadFolder = null;
                } catch (Exception $folderAccessException) {
                    // If the folder is not accessible (no permissions / does not exist) we skip this one.
                }
                break;
            }
        }
        if (!$uploadFolder instanceof Folder) {
            foreach ($backendUser->getFileStorages() as $storage) {
                if ($storage->isWritable()) {
                    try {
                        $uploadFolder = $storage->getDefaultFolder();
                        if ($uploadFolder->checkActionPermission('write')) {
                            break;
                        }
                        $uploadFolder = null;
                    } catch (Exception $folderAccessException) {
                        // If the folder is not accessible (no permissions / does not exist) try the next one.
                    }
                }
            }
        }
        return $uploadFolder;
    }
}
