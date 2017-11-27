<?php
namespace TYPO3\CMS\Core\Resource;

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
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for accessing files
 * it also serves as the public API for the indexing part of files in general
 */
class ProcessedFileRepository extends AbstractRepository
{
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
     * @param FileInterface $originalFile
     * @param string $taskType
     * @param array $configuration
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
     * @param array $databaseRow
     * @return ProcessedFile
     */
    protected function createDomainObject(array $databaseRow)
    {
        $originalFile = $this->factory->getFileObject((int)$databaseRow['original']);
        $originalFile->setStorage($this->factory->getStorageObject($originalFile->getProperty('storage')));
        $taskType = $databaseRow['task_type'];
        $configuration = unserialize($databaseRow['configuration']);

        return GeneralUtility::makeInstance(
            $this->objectType,
            $originalFile,
            $taskType,
            $configuration,
            $databaseRow
        );
    }

    /**
     * @param ResourceStorage $storage
     * @param string $identifier
     *
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
                        $queryBuilder->createNamedParameter($storage->getUid(), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'identifier',
                        $queryBuilder->createNamedParameter($identifier, \PDO::PARAM_STR)
                    )
                )
                ->execute()
                ->fetch();

            if ($databaseRow) {
                $processedFileObject = $this->createDomainObject($databaseRow);
            }
        }
        return $processedFileObject;
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
                $insertFields
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
            $updateFields['tstamp'] = time();

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
            $connection->update(
                $this->table,
                $updateFields,
                [
                    'uid' => (int)$uid
                ]
            );
        }
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\FileInterface $file
     * @param string $taskType The task that should be executed on the file
     * @param array $configuration
     *
     * @return ProcessedFile
     */
    public function findOneByOriginalFileAndTaskTypeAndConfiguration(FileInterface $file, $taskType, array $configuration)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        $databaseRow = $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'original',
                    $queryBuilder->createNamedParameter($file->getUid(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('task_type', $queryBuilder->createNamedParameter($taskType, \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq(
                    'configurationsha1',
                    $queryBuilder->createNamedParameter(sha1(serialize($configuration)), \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetch();

        if (is_array($databaseRow)) {
            $processedFile = $this->createDomainObject($databaseRow);
        } else {
            $processedFile = $this->createNewProcessedFileObject($file, $taskType, $configuration);
        }
        return $processedFile;
    }

    /**
     * @param FileInterface $file
     * @return ProcessedFile[]
     * @throws \InvalidArgumentException
     */
    public function findAllByOriginalFile(FileInterface $file)
    {
        if (!$file instanceof File) {
            throw new \InvalidArgumentException('Parameter is no File object but got type "'
                . (is_object($file) ? get_class($file) : gettype($file)) . '"', 1382006142);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $result = $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'original',
                    $queryBuilder->createNamedParameter($file->getUid(), \PDO::PARAM_INT)
                )
            )
            ->execute();

        $itemList = [];
        while ($row = $result->fetch()) {
            $itemList[] = $this->createDomainObject($row);
        }
        return $itemList;
    }

    /**
     * Removes all processed files and also deletes the associated physical files
     *
     * @param int|null $storageUid If not NULL, only the processed files of the given storage are removed
     * @return int Number of failed deletions
     */
    public function removeAll($storageUid = null)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $result = $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->neq('identifier', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR))
            )
            ->execute();

        $logger = $this->getLogger();
        $errorCount = 0;

        while ($row = $result->fetch()) {
            if ($storageUid && (int)$storageUid !== (int)$row['storage']) {
                continue;
            }
            try {
                $file = $this->createDomainObject($row);
                $file->getStorage()->setEvaluatePermissions(false);
                $file->delete(true);
            } catch (\Exception $e) {
                $logger->error(
                    'Failed to delete file "' . $row['identifier'] . '" in storage uid ' . $row['storage'] . '.',
                    [
                        'exception' => $e
                    ]
                );
                ++$errorCount;
            }
        }

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table)
            ->truncate($this->table);

        return $errorCount;
    }

    /**
     * Removes all array keys which cannot be persisted
     *
     * @param array $data
     *
     * @return array
     */
    protected function cleanUnavailableColumns(array $data)
    {
        // As determining the table columns is a costly operation this is done only once during runtime and cached then
        if (empty($this->tableColumns[$this->table])) {
            $this->tableColumns[$this->table] = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->table)
                ->getSchemaManager()
                ->listTableColumns($this->table);
        }

        return array_intersect_key($data, $this->tableColumns[$this->table]);
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}
