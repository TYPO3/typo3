<?php
namespace TYPO3\CMS\Core\Resource\Processing;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileDeletionAspect
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with deleted files is a slot which reacts on a signal
 * on file deletion.
 *
 * The aspect cleans up database records, processed files and filereferences
 */
class FileDeletionAspect
{
    /**
     * Return a file index repository
     *
     * @return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class);
    }

    /**
     * Return a metadata repository
     *
     * @return \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
     */
    protected function getMetaDataRepository()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class);
    }

    /**
     * Return a processed file repository
     *
     * @return \TYPO3\CMS\Core\Resource\ProcessedFileRepository
     */
    protected function getProcessedFileRepository()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ProcessedFileRepository::class);
    }

    /**
     * Cleanup database record for a deleted file
     *
     * @param FileInterface $fileObject
     */
    public function removeFromRepository(FileInterface $fileObject)
    {
        // remove file from repository
        if ($fileObject instanceof File) {
            $this->cleanupProcessedFiles($fileObject);
            $this->cleanupCategoryReferences($fileObject);
            $this->getFileIndexRepository()->remove($fileObject->getUid());
            $this->getMetaDataRepository()->removeByFileUid($fileObject->getUid());

            // remove all references
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_reference')
                ->delete(
                    'sys_file_reference',
                    [
                        'uid_local' => (int)$fileObject->getUid(),
                        'table_local' => 'sys_file'
                    ]
                );
        } elseif ($fileObject instanceof ProcessedFile) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_processedfile')
                ->delete(
                    'sys_file_processedfile',
                    [
                        'uid' => (int)$fileObject->getUid()
                    ]
                );
        }
    }

    /**
     * Remove all processed files on SIGNAL_PostFileAdd
     *
     * @param FileInterface $file
     * @param string $targetFolder
     */
    public function cleanupProcessedFilesPostFileAdd(FileInterface $file, $targetFolder)
    {
        $this->cleanupProcessedFiles($file);
    }

    /**
     * Remove all processed files on SIGNAL_PostFileReplace
     *
     * @param FileInterface $file
     * @param string $localFilePath
     */
    public function cleanupProcessedFilesPostFileReplace(FileInterface $file, $localFilePath)
    {
        $this->cleanupProcessedFiles($file);
    }

    /**
     * Remove all category references of the deleted file.
     *
     * @param File $fileObject
     */
    protected function cleanupCategoryReferences(File $fileObject)
    {
        // Retrieve the file metadata uid which is different from the file uid.
        $metadataProperties = $fileObject->_getMetaData();
        $metaDataUid = $metadataProperties['_ORIG_uid'] ?? $metadataProperties['uid'];

        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_category_record_mm')
            ->delete(
                'sys_category_record_mm',
                [
                    'uid_foreign' => (int)$metaDataUid,
                    'tablenames' => 'sys_file_metadata'
                ]
            );
    }

    /**
     * Remove all processed files that belong to the given File object
     *
     * @param FileInterface $fileObject
     */
    protected function cleanupProcessedFiles(FileInterface $fileObject)
    {
        // only delete processed files of File objects
        if (!$fileObject instanceof File) {
            return;
        }

        /** @var \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile */
        foreach ($this->getProcessedFileRepository()->findAllByOriginalFile($fileObject) as $processedFile) {
            if ($processedFile->exists()) {
                $processedFile->delete(true);
            }
            $this->removeFromRepository($processedFile);
        }
    }
}
