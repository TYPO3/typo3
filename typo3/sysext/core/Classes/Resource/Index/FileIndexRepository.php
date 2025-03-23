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

namespace TYPO3\CMS\Core\Resource\Index;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedToIndexEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMarkedAsMissingEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRemovedFromIndexEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileUpdatedInIndexEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository Class as an abstraction layer to sys_file
 *
 * Every access to table sys_file_metadata which is not handled by DataHandler
 * has to use this Repository class.
 *
 * @internal This is meant for FAL internal use only!
 */
class FileIndexRepository implements SingletonInterface
{
    protected string $table = 'sys_file';

    /**
     * A list of properties which are to be persisted
     */
    protected array $fields = [
        'uid', 'pid', 'missing', 'type', 'storage', 'identifier', 'identifier_hash', 'extension',
        'mime_type', 'name', 'sha1', 'size', 'creation_date', 'modification_date', 'folder_hash',
    ];

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Retrieves Index record for a given $fileUid
     */
    public function findOneByUid(int $fileUid): array|false
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $row = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? $row : false;
    }

    /**
     * Retrieves Index record for a given $storageUid and $identifier
     *
     * @internal only for use from FileRepository
     */
    public function findOneByStorageUidAndIdentifierHash(int $storageUid, string $identifierHash): array|false
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $row = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('identifier_hash', $queryBuilder->createNamedParameter($identifierHash))
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? $row : false;
    }

    /**
     * Retrieves Index record for a given $storageUid and $identifier
     *
     * @internal only for use from FileRepository
     */
    public function findOneByStorageAndIdentifier(ResourceStorage $storage, string $identifier): array|false
    {
        $identifierHash = $storage->hashFileIdentifier($identifier);
        return $this->findOneByStorageUidAndIdentifierHash($storage->getUid(), $identifierHash);
    }

    /**
     * Retrieves Index record for a given $fileObject
     *
     * @internal only for use from FileRepository
     */
    public function findOneByFileObject(FileInterface $fileObject): array|false
    {
        return $this->findOneByStorageAndIdentifier($fileObject->getStorage(), $fileObject->getIdentifier());
    }

    /**
     * Returns all indexed files which match the content hash
     * Used by the indexer to detect already present files
     */
    public function findByContentHash(string $hash): array
    {
        if (!preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        return $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('sha1', $queryBuilder->createNamedParameter($hash))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Find all records for files in a Folder
     */
    public function findByFolder(Folder $folder): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $result = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'folder_hash',
                    $queryBuilder->createNamedParameter($folder->getHashedIdentifier())
                ),
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($folder->getStorage()->getUid(), Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $resultRows = [];
        while ($row = $result->fetchAssociative()) {
            $resultRows[$row['identifier']] = $row;
        }

        return $resultRows;
    }

    /**
     * Find all records for files in an array of Folders
     *
     * @param Folder[] $folders
     */
    public function findByFolders(array $folders, bool $includeMissing = true, ?string $fileName = null): array
    {
        $storageUids = [];
        $folderIdentifiers = [];

        foreach ($folders as $folder) {
            $storageUids[] = $folder->getStorage()->getUid();
            $folderIdentifiers[] = $folder->getHashedIdentifier();
        }

        $storageUids = array_unique($storageUids);
        $folderIdentifiers = array_unique($folderIdentifiers);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->in(
                    'folder_hash',
                    $queryBuilder->createNamedParameter($folderIdentifiers, Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->in(
                    'storage',
                    $queryBuilder->createNamedParameter($storageUids, Connection::PARAM_INT_ARRAY)
                )
            );

        if (isset($fileName)) {
            $nameParts = str_getcsv($fileName, ' ', '"', '\\');
            foreach ($nameParts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->like(
                            'name',
                            $queryBuilder->createNamedParameter(
                                '%' . $queryBuilder->escapeLikeWildcards($part) . '%'
                            )
                        )
                    );
                }
            }
        }

        if (!$includeMissing) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)));
        }

        $result = $queryBuilder->executeQuery();

        $fileRecords = [];
        while ($fileRecord = $result->fetchAssociative()) {
            $fileRecords[$fileRecord['identifier']] = $fileRecord;
        }

        return $fileRecords;
    }

    /**
     * Adds a file to the index
     */
    public function add(File $file): void
    {
        if ($this->hasIndexRecord($file)) {
            $this->update($file);
            if ($file->_getPropertyRaw('uid') === null) {
                $file->updateProperties($this->findOneByFileObject($file));
            }
        } else {
            $file->updateProperties(['uid' => $this->insertRecord($file->getProperties())]);
        }
    }

    /**
     * Add data from record (at indexing time)
     */
    public function addRaw(array $data): array
    {
        $data['uid'] = $this->insertRecord($data);
        return $data;
    }

    /**
     * Helper to reduce code duplication
     */
    protected function insertRecord(array $data): int
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        $data['tstamp'] = time();
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->insert(
            $this->table,
            $data
        );
        $data['uid'] = (int)$connection->lastInsertId();
        $this->updateRefIndex($data['uid']);
        $this->eventDispatcher->dispatch(new AfterFileAddedToIndexEvent($data['uid'], $data));
        return $data['uid'];
    }

    /**
     * Checks if a file is indexed
     */
    public function hasIndexRecord(File $file): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        if ((int)$file->_getPropertyRaw('uid') > 0) {
            $constraints = [
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT)),
            ];
        } else {
            $constraints = [
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($file->getStorage()->getUid(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($file->_getPropertyRaw('identifier'))
                ),
            ];
        }

        $count = $queryBuilder
            ->count('uid')
            ->from($this->table)
            ->where(...$constraints)
            ->executeQuery()
            ->fetchOne();

        return (bool)$count;
    }

    /**
     * Updates the index record in the database
     */
    public function update(File $file): void
    {
        $updatedProperties = array_intersect($this->fields, $file->getUpdatedProperties());
        $updateRow = [];
        foreach ($updatedProperties as $key) {
            $updateRow[$key] = $file->getProperty($key);
        }
        if (!empty($updateRow)) {
            if ((int)$file->_getPropertyRaw('uid') > 0) {
                $constraints = ['uid' => $file->getUid()];
            } else {
                $constraints = [
                    'storage' => $file->getStorage()->getUid(),
                    'identifier' => $file->_getPropertyRaw('identifier'),
                ];
            }

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
            $updateRow['tstamp'] = time();

            $connection->update(
                $this->table,
                $updateRow,
                $constraints
            );

            $this->updateRefIndex($file->getUid());
            $this->eventDispatcher->dispatch(new AfterFileUpdatedInIndexEvent($file, array_intersect_key($file->getProperties(), array_flip($this->fields)), $updateRow));
        }
    }

    /**
     * Finds the files needed for second indexer step
     */
    public function findInStorageWithIndexOutstanding(ResourceStorage $storage, int $limit = -1): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $rows = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->quoteIdentifier('last_indexed')),
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage->getUid(), Connection::PARAM_INT))
            )
            ->orderBy('tstamp', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }

    /**
     * Helper function for the Indexer to detect missing files
     *
     * @param int[] $uidList
     */
    public function findInStorageAndNotInUidList(ResourceStorage $storage, array $uidList): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($storage->getUid(), Connection::PARAM_INT)
                )
            );

        if (!empty($uidList)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid',
                    array_map(intval(...), $uidList)
                )
            );
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Updates the timestamp when the file indexer extracted metadata
     */
    public function updateIndexingTime(int $fileUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->update(
            $this->table,
            [
                'last_indexed' => time(),
            ],
            [
                'uid' => $fileUid,
            ]
        );
    }

    /**
     * Marks given file as missing in sys_file
     */
    public function markFileAsMissing(int $fileUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->update(
            $this->table,
            [
                'missing' => 1,
            ],
            [
                'uid' => $fileUid,
            ]
        );
        $this->eventDispatcher->dispatch(new AfterFileMarkedAsMissingEvent($fileUid));
    }

    /**
     * Remove a sys_file record from the database
     */
    public function remove(int $fileUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->delete(
            $this->table,
            [
                'uid' => $fileUid,
            ]
        );
        $this->updateRefIndex($fileUid);
        $this->eventDispatcher->dispatch(new AfterFileRemovedFromIndexEvent($fileUid));
    }

    /**
     * Update Reference Index (sys_refindex) for a file
     */
    public function updateRefIndex(int $id): void
    {
        $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
        $refIndexObj->updateRefIndexTable($this->table, $id);
    }
}
