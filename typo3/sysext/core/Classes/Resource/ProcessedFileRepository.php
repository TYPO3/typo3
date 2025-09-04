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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A repository for accessing and storing processed files.
 *
 * This class is mainly meant to be used internally in TYPO3 for accessing via
 * FileProcessingService or custom FAL Processors.
 */
class ProcessedFileRepository implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected readonly ResourceFactory $factory,
        protected readonly TaskTypeRegistry $taskTypeRegistry
    ) {}

    /**
     * Finds a processed file matching the given UID.
     */
    public function findByUid(int $uid): ProcessedFile
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_processedfile');
        $row = $queryBuilder
            ->select('*')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($row)) {
            throw new \RuntimeException('Could not find row with UID "' . $uid . '" in table "sys_file_processedfile"', 1695122090);
        }
        return $this->createDomainObject($row);
    }

    public function findByStorageAndIdentifier(ResourceStorage $storage, string $identifier): ?ProcessedFile
    {
        $processedFileObject = null;
        if ($storage->hasFile($identifier)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_processedfile');
            $databaseRow = $queryBuilder
                ->select('*')
                ->from('sys_file_processedfile')
                ->where(
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storage->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'identifier',
                        $queryBuilder->createNamedParameter($identifier)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();

            if ($databaseRow) {
                $processedFileObject = $this->createDomainObject($databaseRow);
            }
        }
        return $processedFileObject;
    }

    /**
     * Count processed files by storage. This is used in the "Install Tool"
     * to render statistics of processed files.
     */
    public function countByStorage(ResourceStorage $storage): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_processedfile');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($storage->getUid(), Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Adds a processed file object to the database.
     */
    public function add(ProcessedFile $processedFile, TaskInterface $task): void
    {
        if ($processedFile->isPersisted()) {
            $this->update($processedFile, $task);
        } else {
            $currentTimestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
            $insertFields = $processedFile->toArray();
            $insertFields['crdate'] = $currentTimestamp;
            $insertFields['tstamp'] = $currentTimestamp;
            $insertFields['checksum'] = $task->getConfigurationChecksum();

            $insertFields = $this->cleanUnavailableColumns($insertFields);

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_processedfile');
            $connection->insert(
                'sys_file_processedfile',
                $insertFields,
                ['configuration' => Connection::PARAM_LOB]
            );

            $uid = $connection->lastInsertId();
            $processedFile->updateProperties(['uid' => $uid]);
        }
    }

    /**
     * Updates an existing file object in the database. If the file has not been
     * persisted yet, nothing changes.
     */
    public function update(ProcessedFile $processedFile, TaskInterface $task): void
    {
        if ($processedFile->isPersisted()) {
            $uid = $processedFile->getUid();
            $updateFields = $processedFile->toArray();
            $updateFields['checksum'] = $task->getConfigurationChecksum();
            $updateFields = $this->cleanUnavailableColumns($updateFields);
            unset($updateFields['uid']);
            $currentTimestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
            $updateFields['tstamp'] = $currentTimestamp;

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_processedfile');
            $connection->update(
                'sys_file_processedfile',
                $updateFields,
                [
                    'uid' => $uid,
                ],
                ['configuration' => Connection::PARAM_LOB]
            );
        }
    }

    /**
     * @param string $taskType The task that should be executed on the file
     */
    public function findOneByOriginalFileAndTaskTypeAndConfiguration(File $file, string $taskType, array $configuration): ProcessedFile
    {
        // Creating a task object to only fetch cleaned configuration properties
        $task = $this->prepareTaskObject($file, $taskType, $configuration);
        $configuration = $task->getConfiguration();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_processedfile');
        $databaseRow = $queryBuilder
            ->select('*')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq(
                    'original',
                    $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('task_type', $queryBuilder->createNamedParameter($taskType)),
                $queryBuilder->expr()->eq(
                    'configurationsha1',
                    $queryBuilder->createNamedParameter(sha1((new ConfigurationService())->serialize($configuration)))
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (is_array($databaseRow)) {
            $processedFile = $this->createDomainObject($databaseRow);
        } else {
            $processedFile = $this->createNewProcessedFileObject($file, $taskType, $configuration);
        }
        return $processedFile;
    }

    /**
     * @return ProcessedFile[]
     */
    public function findAllByOriginalFile(File $file): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_processedfile');
        $result = $queryBuilder
            ->select('*')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq(
                    'original',
                    $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $itemList = [];
        while ($row = $result->fetchAssociative()) {
            $itemList[] = $this->createDomainObject($row);
        }
        return $itemList;
    }

    /**
     * Removes all processed files and also deletes the associated physical files.
     * If a storageUid is given, only db entries and files of this storage are removed.
     *
     * @param int|null $storageUid If not NULL, only the processed files of the given storage are removed
     * @return int Number of failed deletions
     */
    public function removeAll(?int $storageUid = null): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_file_processedfile');
        $queryBuilder = $connection->createQueryBuilder();
        $where = [
            $queryBuilder->expr()->neq('identifier', $queryBuilder->createNamedParameter('')),
        ];
        if ($storageUid !== null) {
            $where[] = $queryBuilder->expr()->eq(
                'storage',
                $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
            );
        }
        $result = $queryBuilder
            ->select('*')
            ->from('sys_file_processedfile')
            ->where(...$where)
            ->executeQuery();

        $errorCount = 0;

        while ($row = $result->fetchAssociative()) {
            if ($storageUid && $storageUid !== (int)$row['storage']) {
                continue;
            }
            try {
                $file = $this->createDomainObject($row);
                $file->getStorage()->setEvaluatePermissions(false);
                $file->delete(true);
            } catch (\Exception $e) {
                $this->logger->error('Failed to delete file {identifier} in storage uid {storage}.', [
                    'identifier' => $row['identifier'],
                    'storage' => $row['storage'],
                    'exception' => $e,
                ]);
                ++$errorCount;
            }
        }

        if ($storageUid === null) {
            // Truncate entire table if not restricted to specific storage
            $connection->truncate('sys_file_processedfile');
        } else {
            // else remove db rows of this storage only
            $connection->delete('sys_file_processedfile', ['storage' => $storageUid], [Connection::PARAM_INT]);
        }

        return $errorCount;
    }

    /**
     * Creates a ProcessedFile object from a file object and a processing configuration.
     */
    protected function createNewProcessedFileObject(File $originalFile, string $taskType, array $configuration): ProcessedFile
    {
        return new ProcessedFile($originalFile, $taskType, $configuration);
    }

    protected function createDomainObject(array $databaseRow): ProcessedFile
    {
        $originalFile = $this->factory->getFileObject((int)$databaseRow['original']);
        $taskType = $databaseRow['task_type'];
        // Allow deserialization of Area class, since Area objects get serialized in configuration
        // TODO: This should be changed to json encode and decode at some point
        $configuration = unserialize(
            $databaseRow['configuration'],
            [
                'allowed_classes' => [
                    Area::class,
                ],
            ]
        );

        return new ProcessedFile($originalFile, $taskType, $configuration, $databaseRow);
    }

    /**
     * Removes all array keys which cannot be persisted.
     */
    protected function cleanUnavailableColumns(array $data): array
    {
        return array_intersect_key($data, GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_processedfile')
            ->getSchemaInformation()
            ->listTableColumnInfos('sys_file_processedfile'));
    }

    /**
     * We need a task object, so the task can define what configuration is necessary. This way, we can then
     * use a cleaned up configuration to find already processed files.
     *
     * Note: The Task object needs to be re-created with a real processed file, once we have one,
     * as the current API Design is very tightly coupled:
     * - TaskInterface has a constructor in the interface (which is bad)
     * - TaskObject requires a constituted ProcessedFile object in order to "work"
     * - Task objects are created by external services when needed for processing
     * - ProcessedFile AND TaskObject contain both the configuration, which should be avoided as well (getting smaller now).
     *
     * @todo: This should be shifted into a TaskFactory or the TaskRegistry
     */
    protected function prepareTaskObject(File $fileObject, string $taskType, array $configuration): TaskInterface
    {
        $temporaryProcessedFile = $this->createNewProcessedFileObject($fileObject, $taskType, $configuration);
        $taskObject = $this->taskTypeRegistry->getTaskForType($taskType, $temporaryProcessedFile, $configuration);
        $taskObject->sanitizeConfiguration();
        return $taskObject;
    }
}
