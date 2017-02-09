<?php
namespace TYPO3\CMS\Install\Updates;

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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Updates the checksum of sys_file_processedfile records to avoid regeneration of the thumbnails
 */
class ProcessedFileChecksumUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = '[Optional] Update sys_file_processedfile records to match new checksum calculation.';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $execute = false;

        // Check if there is a registry entry from a former run that may have been stopped
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registryEntry = $registry->get('core', 'ProcessedFileChecksumUpdate');
        if ($registryEntry !== null) {
            $execute = true;
        }

        // Enable if there are non empty sys_file_processedfile entries
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_processedfile');
        $incompleteCount = $queryBuilder->count('uid')
            ->from('sys_file_processedfile')
            ->orWhere(
                $queryBuilder->expr()->notIn('identifier', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)),
                $queryBuilder->expr()->isNull('width'),
                $queryBuilder->expr()->isNull('height')
            )->execute()->fetchColumn(0);
        if ((bool)$incompleteCount) {
            $execute = true;
        }

        if ($execute) {
            $description = 'The checksum calculation for processed files (image thumbnails) has been changed with'
                . ' TYPO3 CMS 7.3 and 6.2.13. This means that your processed files need to be updated, if you update'
                . ' from versions <strong>below TYPO3 CMS 7.3 or 6.2.13</strong>.<br />'
                . 'This can either happen on demand, when the processed file is first needed, or by executing this'
                . ' wizard, which updates all processed images at once.<br />'
                . '<strong>Important:</strong> If you have lots of processed files, you should prefer using this'
                . ' wizard, otherwise this might cause a lot of work for your server.';
        }

        return $execute;
    }

    /**
     * Performs the update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $factory = GeneralUtility::makeInstance(ResourceFactory::class);
        $fileConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_processedfile');

        $firstUid = $registry->get('core', 'ProcessedFileChecksumUpdate');

        // Remove all invalid records which hold NULL values
        $queryBuilder = $fileConnection->createQueryBuilder();
        $queryBuilder->delete('sys_file_processedfile')
            ->orWhere(
                $queryBuilder->expr()->isNull('width'),
                $queryBuilder->expr()->isNull('height')
            )
            ->execute();

        // Get all other rows
        $queryBuilder = $fileConnection->createQueryBuilder();
        $queryBuilder = $queryBuilder->select('*')
            ->from('sys_file_processedfile')
            ->orderBy('uid');
        // If there was a start trigger, use it
        if ($firstUid !== null && (int)$firstUid > 0) {
            $queryBuilder->where(
                $queryBuilder->expr()->gt(
                    'uid',
                    $queryBuilder->createNamedParameter($firstUid, \PDO::PARAM_INT)
                )
            );
        }
        $statement = $queryBuilder->execute();
        while ($processedFileRow = $statement->fetch()) {
            try {
                $storage = $factory->getStorageObject($processedFileRow['storage']);
            } catch (\InvalidArgumentException $e) {
                $storage = null;
            }
            if (!$storage) {
                // Invalid storage, delete record, we can't take care of the associated file
                $fileConnection->delete('sys_file_processedfile', ['uid' => (int)$processedFileRow['uid']]);
                $registry->set('core', 'ProcessedFileChecksumUpdate', (int)$processedFileRow['uid']);
                continue;
            }

            if ($storage->getDriverType() !== 'Local') {
                // Non-local storage, we can't treat this, skip the record and mark it done
                $registry->set('core', 'ProcessedFileChecksumUpdate', (int)$processedFileRow['uid']);
                continue;
            }

            $configuration = $storage->getConfiguration();
            if ($configuration['pathType'] === 'relative') {
                $absoluteBasePath = PATH_site . $configuration['basePath'];
            } else {
                $absoluteBasePath = $configuration['basePath'];
            }
            $filePath = rtrim($absoluteBasePath, '/') . '/' . ltrim($processedFileRow['identifier'], '/');

            try {
                $originalFile = $factory->getFileObject($processedFileRow['original']);
            } catch (\Exception $e) {
                // No original file there anymore, delete local file
                @unlink($filePath);
                $fileConnection->delete('sys_file_processedfile', ['uid' => (int)$processedFileRow['uid']]);
                $registry->set('core', 'ProcessedFileChecksumUpdate', (int)$processedFileRow['uid']);
                continue;
            }

            $processedFileObject = new ProcessedFile($originalFile, '', [], $processedFileRow);

            // calculate new checksum and name
            $newChecksum = $processedFileObject->calculateChecksum();

            // if the checksum already matches, there is nothing to do
            if ($newChecksum !== $processedFileRow['checksum']) {
                $newName = str_replace($processedFileRow['checksum'], $newChecksum, $processedFileRow['name']);
                $newIdentifier = str_replace(
                    $processedFileRow['checksum'],
                    $newChecksum,
                    $processedFileRow['identifier']
                );
                $newFilePath = str_replace($processedFileRow['checksum'], $newChecksum, $filePath);

                // rename file
                if (@rename($filePath, $newFilePath)) {
                    // save result back into database
                    $fields = [
                        'tstamp' => time(),
                        'identifier' => $newIdentifier,
                        'name' => $newName,
                        'checksum' => $newChecksum
                    ];
                    $fileConnection->update(
                        'sys_file_processedfile',
                        $fields,
                        ['uid' => (int)$processedFileRow['uid']]
                    );
                }
                // if the rename of the file failed, keep the record, but do not bother with it again
            }
            $registry->set('core', 'ProcessedFileChecksumUpdate', (int)$processedFileRow['uid']);
        }

        $registry->remove('core', 'ProcessedFileChecksumUpdate');
        $this->markWizardAsDone();
        return true;
    }
}
