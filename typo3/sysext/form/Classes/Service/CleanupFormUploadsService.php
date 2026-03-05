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

namespace TYPO3\CMS\Form\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Service to find and clean up old form upload folders.
 *
 * When ext:form handles file uploads, it creates sub-folders named
 * `form_<40-hex-chars>` inside the configured upload folder (default:
 * `1:/user_upload/`). Over time, these folders accumulate — both from
 * completed and incomplete form submissions.
 *
 * Since uploaded files are not moved upon form submission, there is no
 * way to distinguish between folders from completed and abandoned
 * submissions. This service identifies form upload folders based on
 * their age (modification time) and provides methods to list and delete them.
 *
 * @internal
 */
class CleanupFormUploadsService
{
    /**
     * Regex matching the folder naming convention used by
     * UploadedFileReferenceConverter::importUploadedResource():
     * `form_` followed by exactly 40 hex characters (HMAC output).
     */
    private const FORM_UPLOAD_FOLDER_PATTERN = '/^form_[a-f0-9]{40}$/';

    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Finds expired form upload folders in the given upload folders.
     *
     * A folder is considered expired when:
     * 1. Its name matches the `form_<40-hex-chars>` pattern
     * 2. Its modification time is older than the given maximum age
     *
     * @param int $maximumAge Maximum age in seconds. Folders older than this are considered expired.
     * @param list<string> $uploadFolderIdentifiers List of combined folder identifiers to scan
     *                                              (e.g. ['1:/user_upload/', '2:/custom_uploads/']).
     * @return list<Folder> List of expired form upload folders
     */
    public function getExpiredFolders(int $maximumAge, array $uploadFolderIdentifiers): array
    {
        $cutoffTimestamp = time() - $maximumAge;
        $expiredFolders = [];

        foreach ($uploadFolderIdentifiers as $folderIdentifier) {
            $expiredFolders = [
                ...$expiredFolders,
                ...$this->findExpiredFoldersInParent($folderIdentifier, $cutoffTimestamp),
            ];
        }

        return $expiredFolders;
    }

    /**
     * Deletes the given folders and returns a result summary.
     *
     * @param list<Folder> $folders Folders to delete
     * @return array{deleted: int, failed: int, errors: list<array{folder: string, message: string}>}
     */
    public function deleteFolders(array $folders): array
    {
        $deleted = 0;
        $failed = 0;
        $errors = [];

        foreach ($folders as $folder) {
            try {
                $folder->delete(true);
                $deleted++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'folder' => $folder->getCombinedIdentifier(),
                    'message' => $e->getMessage(),
                ];
                $this->logger->error(
                    'Failed to delete form upload folder "{folder}": {message}',
                    ['folder' => $folder->getCombinedIdentifier(), 'message' => $e->getMessage()]
                );
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Find expired form upload folders in a specific parent folder.
     *
     * @return list<Folder>
     */
    private function findExpiredFoldersInParent(string $folderIdentifier, int $cutoffTimestamp): array
    {
        $expiredFolders = [];

        try {
            $parentFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderIdentifier);
        } catch (FolderDoesNotExistException | InsufficientFolderAccessPermissionsException | \InvalidArgumentException $e) {
            $this->logger->warning(
                'Could not access upload folder "{folder}": {message}',
                ['folder' => $folderIdentifier, 'message' => $e->getMessage()]
            );
            return $expiredFolders;
        }

        foreach ($parentFolder->getSubfolders() as $subFolder) {
            if ($this->isExpiredFormUploadFolder($subFolder, $cutoffTimestamp)) {
                $expiredFolders[] = $subFolder;
            }
        }

        return $expiredFolders;
    }

    /**
     * Determines whether a folder is an expired form upload folder.
     *
     * A folder is considered an expired form upload folder when:
     * 1. Its name matches the exact `form_<40-hex-chars>` pattern
     *    (as generated by UploadedFileReferenceConverter::importUploadedResource())
     * 2. Its modification time is older than the cutoff timestamp
     */
    private function isExpiredFormUploadFolder(Folder $folder, int $cutoffTimestamp): bool
    {
        if (preg_match(self::FORM_UPLOAD_FOLDER_PATTERN, $folder->getName()) !== 1) {
            return false;
        }

        return $folder->getModificationTime() < $cutoffTimestamp;
    }
}
