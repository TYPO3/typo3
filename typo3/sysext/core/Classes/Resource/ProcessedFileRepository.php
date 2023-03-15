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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for accessing files
 * it also serves as the public API for the indexing part of files in general
 */
class ProcessedFileRepository extends AbstractRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The main object type of this class. In some cases (fileReference) this
     * repository can also return FileReference objects, implementing the
     * common FileInterface.
     *
     * @var string
     */
    protected $objectType = ProcessedFile::class;

    /**
     * Main File object storage table. Note that this repository also works on
     * the sys_file_reference table when returning FileReference objects.
     *
     * @var string
     */
    protected $table = 'sys_file_processedfile';

    /**
     * As determining the table columns is a costly operation this is done only once during runtime and cached then
     *
     * @var array
     * @see cleanUnavailableColumns()
     */
    protected $tableColumns = [];

    /**
     * Creates this object.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Creates a ProcessedFile object from a file object and a processing configuration
     *
     * @param string $taskType
     * @return ProcessedFile
     */
    public function createNewProcessedFileObject(FileInterface $originalFile, $taskType, array $configuration)
    {
        return GeneralUtility::makeInstance(
            $this->objectType,
            $originalFile,
            $taskType,
            $configuration
        );
    }

    /**
     * @return ProcessedFile
     */
    protected function createDomainObject(array $databaseRow)
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

        return GeneralUtility::makeInstance(
            $this->objectType,
            $originalFile,
            $taskType,
            $configuration,
            $databaseRow
        );
    }

    /**
     * @param string $identifier
     * @return ProcessedFile|null
     */
    public function findByStorageAndIdentifier(ResourceStorage $storage, $identifier)
    {
        $processedFileObject = null;
        if ($storage->hasFile($identifier)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
            $databaseRow = $queryBuilder
                ->select('*')
                ->from($this->table)
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
     * Count processed files by storage. This is used in the install tool
     * to render statistics of processed files.
     */
    public function countByStorage(ResourceStorage $storage): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);
        return (int)$queryBuilder
            ->count('uid')
            ->from($this->table)
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
     * Adds a processedfile object in the database
     *
     * @param ProcessedFile $processedFile
     */
    public function add($processedFile)
    {
        if ($processedFile->isPersisted()) {
            $this->update($processedFile);
        } else {
            $insertFields = $processedFile->toArray();
            $insertFields['crdate'] = $insertFields['tstamp'] = time();
            $insertFields = $this->cleanUnavailableColumns($insertFields);

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);

            $connection->insert(
                $this->table,
                $insertFields,
                ['configuration' => Connection::PARAM_LOB]
            );

            $uid = $connection->lastInsertId($this->table);
            $processedFile->updateProperties(['uid' => $uid]);
        }
    }

    /**
     * Updates an existing file object in the database
     *
     * @param ProcessedFile $processedFile
     */
    public function update($processedFile)
    {
        if ($processedFile->isPersisted()) {
            $uid = (int)$processedFile->getUid();
            $updateFields = $this->cleanUnavailableColumns($processedFile->toArray());
            unset($updateFields['uid']);
            $updateFields['tstamp'] = time();

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
            $connection->update(
                $this->table,
                $updateFields,
                [
                    'uid' => (int)$uid,
                ],
                ['configuration' => Connection::PARAM_LOB]
            );
        }
    }

    /**
     * @param string $taskType The task that should be executed on the file
     *
     * @return ProcessedFile
     */
    public function findOneByOriginalFileAndTaskTypeAndConfiguration(File $file, $taskType, array $configuration)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        $databaseRow = $queryBuilder
            ->select('*')
            ->from($this->table)
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
     * @throws \InvalidArgumentException
     */
    public function findAllByOriginalFile(FileInterface $file)
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('Parameter is no File object but got type "' . get_debug_type($file) . '"', 1382006142);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $result = $queryBuilder
            ->select('*')
            ->from($this->table)
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
    public function removeAll($storageUid = null)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);
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
            ->from($this->table)
            ->where(...$where)
            ->executeQuery();

        $errorCount = 0;

        while ($row = $result->fetchAssociative()) {
            if ($storageUid && (int)$storageUid !== (int)$row['storage']) {
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
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->table)
                ->truncate($this->table);
        } else {
            // else remove db rows of this storage only
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->table)
                ->delete($this->table, ['storage' => $storageUid], [Connection::PARAM_INT]);
        }

        return $errorCount;
    }

    /**
     * Removes all array keys which cannot be persisted
     *
     *
     * @return array
     */
    protected function cleanUnavailableColumns(array $data)
    {
        // As determining the table columns is a costly operation this is done only once during runtime and cached then
        if (empty($this->tableColumns[$this->table])) {
            $this->tableColumns[$this->table] = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->table)
                ->createSchemaManager()
                ->listTableColumns($this->table);
        }

        return array_intersect_key($data, $this->tableColumns[$this->table]);
    }
}
