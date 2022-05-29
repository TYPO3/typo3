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
    /**
     * @var string
     */
    protected $table = 'sys_file';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * A list of properties which are to be persisted
     *
     * @var array
     */
    protected $fields = [
        'uid', 'pid', 'missing', 'type', 'storage', 'identifier', 'identifier_hash', 'extension',
        'mime_type', 'name', 'sha1', 'size', 'creation_date', 'modification_date', 'folder_hash',
    ];

    /**
     * Returns an Instance of the Repository
     *
     * @return FileIndexRepository
     * @deprecated will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.
     */
    public static function getInstance()
    {
        trigger_error(__CLASS__ . '::getInstance() will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(self::class);
    }

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Retrieves Index record for a given $fileUid
     *
     * @param int $fileUid
     * @return array|false
     */
    public function findOneByUid($fileUid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $row = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? $row : false;
    }

    /**
     * Retrieves Index record for a given $storageUid and $identifier
     *
     * @param int $storageUid
     * @param string $identifierHash
     * @return array|false
     *
     * @internal only for use from FileRepository
     */
    public function findOneByStorageUidAndIdentifierHash($storageUid, $identifierHash)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $row = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storageUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('identifier_hash', $queryBuilder->createNamedParameter($identifierHash))
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? $row : false;
    }

    /**
     * Retrieves Index record for a given $storageUid and $identifier
     *
     * @param ResourceStorage $storage
     * @param string $identifier
     * @return array|bool
     *
     * @internal only for use from FileRepository
     */
    public function findOneByStorageAndIdentifier(ResourceStorage $storage, $identifier)
    {
        $identifierHash = $storage->hashFileIdentifier($identifier);
        return $this->findOneByStorageUidAndIdentifierHash($storage->getUid(), $identifierHash);
    }

    /**
     * Retrieves Index record for a given $fileObject
     *
     * @param FileInterface $fileObject
     * @return array|bool
     *
     * @internal only for use from FileRepository
     */
    public function findOneByFileObject(FileInterface $fileObject)
    {
        return $this->findOneByStorageAndIdentifier($fileObject->getStorage(), $fileObject->getIdentifier());
    }

    /**
     * Returns all indexed files which match the content hash
     * Used by the indexer to detect already present files
     *
     * @param string $hash
     * @return mixed
     */
    public function findByContentHash($hash)
    {
        if (!preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $resultRows = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('sha1', $queryBuilder->createNamedParameter($hash, \PDO::PARAM_STR))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return $resultRows;
    }

    /**
     * Find all records for files in a Folder
     *
     * @param Folder $folder
     * @return array|null
     */
    public function findByFolder(Folder $folder)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->table);

        $result = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'folder_hash',
                    $queryBuilder->createNamedParameter($folder->getHashedIdentifier(), \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($folder->getStorage()->getUid(), \PDO::PARAM_INT)
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
     * @param bool $includeMissing
     * @param string $fileName
     * @return array|null
     */
    public function findByFolders(array $folders, $includeMissing = true, $fileName = null)
    {
        $storageUids = [];
        $folderIdentifiers = [];

        foreach ($folders as $folder) {
            if (!$folder instanceof Folder) {
                continue;
            }

            $storageUids[] = (int)$folder->getStorage()->getUid();
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
            $nameParts = str_getcsv($fileName, ' ');
            foreach ($nameParts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->like(
                            'name',
                            $queryBuilder->createNamedParameter(
                                '%' . $queryBuilder->escapeLikeWildcards($part) . '%',
                                \PDO::PARAM_STR
                            )
                        )
                    );
                }
            }
        }

        if (!$includeMissing) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)));
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
     *
     * @param File $file
     */
    public function add(File $file)
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
     *
     * @param array $data
     * @return array
     */
    public function addRaw(array $data)
    {
        $data['uid'] = $this->insertRecord($data);
        return $data;
    }

    /**
     * Helper to reduce code duplication
     *
     * @param array $data
     *
     * @return int
     */
    protected function insertRecord(array $data)
    {
        $data = array_intersect_key($data, array_flip($this->fields));
        $data['tstamp'] = time();
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->insert(
            $this->table,
            $data
        );
        $data['uid'] = (int)$connection->lastInsertId($this->table);
        $this->updateRefIndex($data['uid']);
        $this->eventDispatcher->dispatch(new AfterFileAddedToIndexEvent($data['uid'], $data));
        return $data['uid'];
    }

    /**
     * Checks if a file is indexed
     *
     * @param File $file
     * @return bool
     */
    public function hasIndexRecord(File $file)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        if ((int)$file->_getPropertyRaw('uid') > 0) {
            $constraints = [
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($file->getUid(), \PDO::PARAM_INT)),
            ];
        } else {
            $constraints = [
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($file->getStorage()->getUid(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($file->_getPropertyRaw('identifier'), \PDO::PARAM_STR)
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
     *
     * @param File $file
     */
    public function update(File $file)
    {
        $updatedProperties = array_intersect($this->fields, $file->getUpdatedProperties());
        $updateRow = [];
        foreach ($updatedProperties as $key) {
            $updateRow[$key] = $file->getProperty($key);
        }
        if (!empty($updateRow)) {
            if ((int)$file->_getPropertyRaw('uid') > 0) {
                $constraints = ['uid' => (int)$file->getUid()];
            } else {
                $constraints = [
                    'storage' => (int)$file->getStorage()->getUid(),
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
     *
     * @param ResourceStorage $storage
     * @param int $limit
     * @return array
     */
    public function findInStorageWithIndexOutstanding(ResourceStorage $storage, $limit = -1)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        if ((int)$limit > 0) {
            $queryBuilder->setMaxResults((int)$limit);
        }

        $rows = $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->gt('tstamp', $queryBuilder->quoteIdentifier('last_indexed')),
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storage->getUid(), \PDO::PARAM_INT))
            )
            ->orderBy('tstamp', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }

    /**
     * Helper function for the Indexer to detect missing files
     *
     * @param ResourceStorage $storage
     * @param int[] $uidList
     * @return array
     */
    public function findInStorageAndNotInUidList(ResourceStorage $storage, array $uidList)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        $queryBuilder
            ->select(...$this->fields)
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createNamedParameter($storage->getUid(), \PDO::PARAM_INT)
                )
            );

        if (!empty($uidList)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid',
                    array_map('intval', $uidList)
                )
            );
        }

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        return $rows;
    }

    /**
     * Updates the timestamp when the file indexer extracted metadata
     *
     * @param int $fileUid
     */
    public function updateIndexingTime($fileUid)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->update(
            $this->table,
            [
                'last_indexed' => time(),
            ],
            [
                'uid' => (int)$fileUid,
            ]
        );
    }

    /**
     * Marks given file as missing in sys_file
     *
     * @param int $fileUid
     */
    public function markFileAsMissing($fileUid)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->update(
            $this->table,
            [
                'missing' => 1,
            ],
            [
                'uid' => (int)$fileUid,
            ]
        );
        $this->eventDispatcher->dispatch(new AfterFileMarkedAsMissingEvent((int)$fileUid));
    }

    /**
     * Remove a sys_file record from the database
     *
     * @param int $fileUid
     */
    public function remove($fileUid)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $connection->delete(
            $this->table,
            [
                'uid' => (int)$fileUid,
            ]
        );
        $this->updateRefIndex($fileUid);
        $this->eventDispatcher->dispatch(new AfterFileRemovedFromIndexEvent((int)$fileUid));
    }

    /**
     * Update Reference Index (sys_refindex) for a file
     *
     * @param int $id Record UID
     */
    public function updateRefIndex($id)
    {
        $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
        $refIndexObj->updateRefIndexTable($this->table, $id);
    }
}
