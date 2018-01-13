<?php

namespace TYPO3\CMS\Core\Resource\Index;

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

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\File as FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
        if (!empty($record['newlyCreated'])) {
            if ($file->getType() === File::FILETYPE_IMAGE && $file->getStorage()->getDriverType() === 'Local') {
                $fileNameAndPath = $file->getForLocalProcessing(false);

                $imageInfo = GeneralUtility::makeInstance(FileType\ImageInfo::class, $fileNameAndPath);

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
            ->execute()
            ->fetch();

        if (empty($record)) {
            $record = $this->createMetaDataRecord($uid);
        }

        $passedData = new \ArrayObject($record);

        $this->emitRecordPostRetrievalSignal($passedData);
        return $passedData->getArrayCopy();
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
            'l10n_diffsource' => ''
        ];
        $emptyRecord = array_merge($emptyRecord, $additionalFields);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $connection->insert(
            $this->tableName,
            $emptyRecord,
            ['l10n_diffsource' => Connection::PARAM_LOB]
        );

        $record = $emptyRecord;
        $record['uid'] = $connection->lastInsertId($this->tableName);
        $record['newlyCreated'] = true;

        $this->emitRecordCreatedSignal($record);

        return $record;
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
        if (empty($this->tableFields)) {
            $this->tableFields = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->tableName)
                ->getSchemaManager()
                ->listTableColumns($this->tableName);
        }
        $updateRow = array_intersect_key($data, $this->tableFields);
        if (array_key_exists('uid', $updateRow)) {
            unset($updateRow['uid']);
        }
        $row = $this->findByFileUid($fileUid);
        if (!empty($updateRow)) {
            $updateRow['tstamp'] = time();
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
            $types = [];
            if ($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
                // mssql needs to set proper PARAM_LOB and others to update fields
                $tableDetails = $connection->getSchemaManager()->listTableDetails($this->tableName);
                foreach ($updateRow as $columnName => $columnValue) {
                    $types[$columnName] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
            }
            $connection->update(
                    $this->tableName,
                    $updateRow,
                    [
                        'uid' => (int)$row['uid']
                    ],
                    $types
                );

            $this->emitRecordUpdatedSignal(array_merge($row, $updateRow));
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
                    'file' => (int)$fileUid
                ]
            );
        $this->emitRecordDeletedSignal($fileUid);
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return $this->getObjectManager()->get(Dispatcher::class);
    }

    /**
     * Get the ObjectManager
     *
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * Signal that is called after a record has been loaded from database
     * Allows other places to do extension of metadata at runtime or
     * for example translation and workspace overlay
     *
     * @param \ArrayObject $data
     */
    protected function emitRecordPostRetrievalSignal(\ArrayObject $data)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, 'recordPostRetrieval', [$data]);
    }

    /**
     * Signal that is called after an IndexRecord is updated
     *
     * @param array $data
     */
    protected function emitRecordUpdatedSignal(array $data)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, 'recordUpdated', [$data]);
    }

    /**
     * Signal that is called after an IndexRecord is created
     *
     * @param array $data
     */
    protected function emitRecordCreatedSignal(array $data)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, 'recordCreated', [$data]);
    }

    /**
     * Signal that is called after an IndexRecord is deleted
     *
     * @param int $fileUid
     */
    protected function emitRecordDeletedSignal($fileUid)
    {
        $this->getSignalSlotDispatcher()->dispatch(self::class, 'recordDeleted', [$fileUid]);
    }

    /**
     * @return MetaDataRepository
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }
}
