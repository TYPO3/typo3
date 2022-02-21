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

use Doctrine\DBAL\Platforms\PostgreSQL94Platform as PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Resource\Event\EnrichFileMetaDataEvent;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository Class as an abstraction layer to sys_file_metadata
 *
 * Every access to table sys_file_metadata which is not handled by DataHandler
 * has to use this Repository class
 */
class MetaDataRepository implements SingletonInterface
{
    /**
     * @var string
     */
    protected $tableName = 'sys_file_metadata';

    /**
     * Internal storage for database table fields
     *
     * @var array
     */
    protected $tableFields = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns array of meta-data properties
     *
     * @param File $file
     * @return array
     */
    public function findByFile(File $file)
    {
        $record = $this->findByFileUid($file->getUid());

        // It could be possible that the meta information is freshly
        // created and inserted into the database. If this is the case
        // we have to take care about correct meta information for width and
        // height in case of an image.
        // This logic can be transferred into a custom PSR-14 event listener in the future by just using
        // the AfterMetaDataCreated event.
        if (!empty($record['crdate']) && (int)$record['crdate'] === $GLOBALS['EXEC_TIME']) {
            if ($file->getType() === File::FILETYPE_IMAGE && $file->getStorage()->getDriverType() === 'Local') {
                $fileNameAndPath = $file->getForLocalProcessing(false);

                $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $fileNameAndPath);

                $additionalMetaInformation = [
                    'width' => $imageInfo->getWidth(),
                    'height' => $imageInfo->getHeight(),
                ];

                $this->update($file->getUid(), $additionalMetaInformation);
            }
            $record = $this->findByFileUid($file->getUid());
        }

        return $record;
    }

    /**
     * Retrieves metadata for file
     *
     * @param int $uid
     * @return array
     * @throws InvalidUidException
     */
    public function findByFileUid($uid)
    {
        $uid = (int)$uid;
        if ($uid <= 0) {
            throw new InvalidUidException('Metadata can only be retrieved for indexed files. UID: "' . $uid . '"', 1381590731);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $record = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAssociative();

        if (empty($record)) {
            return [];
        }

        return $this->eventDispatcher->dispatch(new EnrichFileMetaDataEvent($uid, (int)$record['uid'], $record))->getRecord();
    }

    /**
     * Create empty
     *
     * @param int $fileUid
     * @param array $additionalFields
     * @return array
     */
    public function createMetaDataRecord($fileUid, array $additionalFields = [])
    {
        $emptyRecord = [
            'file' => (int)$fileUid,
            'pid' => 0,
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0,
            'l10n_diffsource' => '',
        ];
        $additionalFields = array_intersect_key($additionalFields, $this->getTableFields());
        $emptyRecord = array_merge($emptyRecord, $additionalFields);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $connection->insert(
            $this->tableName,
            $emptyRecord,
            ['l10n_diffsource' => Connection::PARAM_LOB]
        );

        $record = $emptyRecord;
        $record['uid'] = $connection->lastInsertId($this->tableName);

        return $this->eventDispatcher->dispatch(new AfterFileMetaDataCreatedEvent($fileUid, (int)$record['uid'], $record))->getRecord();
    }

    /**
     * Updates the metadata record in the database
     *
     * @param int $fileUid the file uid to update
     * @param array $data Data to update
     * @internal
     */
    public function update($fileUid, array $data)
    {
        $updateRow = array_intersect_key($data, $this->getTableFields());
        if (array_key_exists('uid', $updateRow)) {
            unset($updateRow['uid']);
        }
        $row = $this->findByFileUid($fileUid);
        if (!empty($updateRow)) {
            $updateRow['tstamp'] = time();
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
            $types = [];
            $platform = $connection->getDatabasePlatform();
            if ($platform instanceof SQLServerPlatform || $platform instanceof PostgreSQLPlatform) {
                // mssql and postgres needs to set proper PARAM_LOB and others to update fields.
                $tableDetails = $connection->createSchemaManager()->listTableDetails($this->tableName);
                foreach ($updateRow as $columnName => $columnValue) {
                    $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
            }
            $connection->update(
                $this->tableName,
                $updateRow,
                [
                    'uid' => (int)$row['uid'],
                ],
                $types
            );

            $this->eventDispatcher->dispatch(new AfterFileMetaDataUpdatedEvent($fileUid, (int)$row['uid'], array_merge($row, $updateRow)));
        }
    }

    /**
     * Remove all metadata records for a certain file from the database
     *
     * @param int $fileUid
     */
    public function removeByFileUid($fileUid)
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName)
            ->delete(
                $this->tableName,
                [
                    'file' => (int)$fileUid,
                ]
            );

        $this->eventDispatcher->dispatch(new AfterFileMetaDataDeletedEvent((int)$fileUid));
    }

    /**
     * Gets the fields that are available in the table
     *
     * @return array
     */
    protected function getTableFields(): array
    {
        if (empty($this->tableFields)) {
            $this->tableFields = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->tableName)
                ->createSchemaManager()
                ->listTableColumns($this->tableName);
        }

        return $this->tableFields;
    }

    /**
     * @return MetaDataRepository
     * @deprecated will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.
     */
    public static function getInstance()
    {
        trigger_error(__CLASS__ . '::getInstance() will be removed in TYPO3 v12.0. Use Dependency Injection or GeneralUtility::makeInstance() if DI is not possible.', E_USER_DEPRECATED);
        return GeneralUtility::makeInstance(self::class);
    }
}
