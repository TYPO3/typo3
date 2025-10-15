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

namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;

/**
 * The aspect cleans up database records, processed files and file references
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with deleted files is a list of PSR-14 event listeners which react on file deletion.
 *
 * @internal this is a list of Event Listeners, and not part of TYPO3 Core API.
 */
final readonly class FileDeletionAspect
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private MetaDataRepository $metaDataRepository,
        private ProcessedFileRepository $processedFileRepository,
        private FileIndexRepository $fileIndexRepository,
    ) {}

    #[AsEventListener('delete-processed-files-after-add')]
    public function cleanupProcessedFilesPostFileAdd(AfterFileAddedEvent $event): void
    {
        $this->cleanupProcessedFiles($event->getFile());
    }

    #[AsEventListener('delete-processed-files-after-replace')]
    public function cleanupProcessedFilesPostFileReplace(AfterFileReplacedEvent $event): void
    {
        $this->cleanupProcessedFiles($event->getFile());
    }

    #[AsEventListener('delete-processed-files-after-delete')]
    public function removeFromRepositoryAfterFileDeleted(AfterFileDeletedEvent $event): void
    {
        $this->removeFromRepository($event->getFile());
    }

    /**
     * Cleanup database record for a deleted file
     */
    private function removeFromRepository(FileInterface $fileObject): void
    {
        // remove file from repository
        if ($fileObject instanceof File) {
            $this->cleanupProcessedFiles($fileObject);
            $this->cleanupCategoryReferences($fileObject);
            $this->fileIndexRepository->remove($fileObject->getUid());
            $this->metaDataRepository->removeByFileUid($fileObject->getUid());

            // remove all references
            $this->connectionPool->getConnectionForTable('sys_file_reference')->delete(
                'sys_file_reference',
                [
                    'uid_local' => $fileObject->getUid(),
                ]
            );
        } elseif ($fileObject instanceof ProcessedFile) {
            $this->connectionPool->getConnectionForTable('sys_file_processedfile')->delete(
                'sys_file_processedfile',
                [
                    'uid' => $fileObject->getUid(),
                ]
            );
        }
    }

    /**
     * Remove all category references of the deleted file.
     */
    private function cleanupCategoryReferences(File $fileObject): void
    {
        // Retrieve the file metadata uid which is different from the file uid.
        $metadataProperties = $fileObject->getMetaData()->get();
        $metaDataUid = (int)($metadataProperties['_ORIG_uid'] ?? $metadataProperties['uid'] ?? 0);

        if ($metaDataUid <= 0) {
            // No metadata record exists for the given file. The file might not
            // have been indexed or the meta data record was deleted manually.
            return;
        }

        $this->connectionPool->getConnectionForTable('sys_category_record_mm')->delete(
            'sys_category_record_mm',
            [
                'uid_foreign' => $metaDataUid,
                'tablenames' => 'sys_file_metadata',
            ]
        );
    }

    /**
     * Remove all processed files that belong to the given File object
     */
    private function cleanupProcessedFiles(FileInterface $fileObject): void
    {
        // only delete processed files of File objects
        if (!$fileObject instanceof File) {
            return;
        }
        foreach ($this->processedFileRepository->findAllByOriginalFile($fileObject) as $processedFile) {
            if ($processedFile->exists()) {
                $processedFile->delete(true);
            }
            $this->removeFromRepository($processedFile);
        }
    }
}
