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

use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\File as FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository Class as an abstraction layer to sys_file_metadata
 *
 * Every access to table sys_file_metadata which is not handled by TCEmain
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
     * Wrapper method for getting DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
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
     * @throws \RuntimeException
     */
    public function findByFileUid($uid)
    {
        $uid = (int)$uid;
        if ($uid <= 0) {
            throw new InvalidUidException('Metadata can only be retrieved for indexed files. UID: "' . $uid . '"', 1381590731);
        }
        $record = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $this->tableName, 'file = ' . $uid . $this->getGeneralWhereClause());

        if ($record === false) {
            $record = $this->createMetaDataRecord($uid);
        }

        $passedData = new \ArrayObject($record);
        $this->emitRecordPostRetrievalSignal($passedData);
        return $passedData->getArrayCopy();
    }

    /**
     * General Where-Clause which is needed to fetch only language 0 and live record.
     *
     * @return string
     */
    protected function getGeneralWhereClause()
    {
        return ' AND sys_language_uid IN (0,-1) AND pid=0';
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
        $emptyRecord =  [
            'file' => (int)$fileUid,
            'pid' => 0,
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0,
            'l10n_diffsource' => ''
        ];
        $emptyRecord = array_merge($emptyRecord, $additionalFields);
        $this->getDatabaseConnection()->exec_INSERTquery($this->tableName, $emptyRecord);
        $record = $emptyRecord;
        $record['uid'] = $this->getDatabaseConnection()->sql_insert_id();
        $record['newlyCreated']  = true;

        $this->emitRecordCreatedSignal($record);

        return $record;
    }

    /**
     * Updates the metadata record in the database
     *
     * @param int $fileUid the file uid to update
     * @param array $data Data to update
     * @return void
     * @internal
     */
    public function update($fileUid, array $data)
    {
        if (empty($this->tableFields)) {
            $this->tableFields = $this->getDatabaseConnection()->admin_get_fields($this->tableName);
        }
        $updateRow = array_intersect_key($data, $this->tableFields);
        if (array_key_exists('uid', $updateRow)) {
            unset($updateRow['uid']);
        }
        $row = $this->findByFileUid($fileUid);
        if (!empty($updateRow)) {
            $updateRow['tstamp'] = time();
            $this->getDatabaseConnection()->exec_UPDATEquery($this->tableName, 'uid = ' . (int)$row['uid'], $updateRow);

            $this->emitRecordUpdatedSignal(array_merge($row, $updateRow));
        }
    }

    /**
     * Remove all metadata records for a certain file from the database
     *
     * @param int $fileUid
     * @return void
     */
    public function removeByFileUid($fileUid)
    {
        $this->getDatabaseConnection()->exec_DELETEquery($this->tableName, 'file=' . (int)$fileUid);
        $this->emitRecordDeletedSignal($fileUid);
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return $this->getObjectManager()->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }

    /**
     * Get the ObjectManager
     *
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }

    /**
     * Signal that is called after a record has been loaded from database
     * Allows other places to do extension of metadata at runtime or
     * for example translation and workspace overlay
     *
     * @param \ArrayObject $data
     * @signal
     */
    protected function emitRecordPostRetrievalSignal(\ArrayObject $data)
    {
        $this->getSignalSlotDispatcher()->dispatch(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class, 'recordPostRetrieval', [$data]);
    }

    /**
     * Signal that is called after an IndexRecord is updated
     *
     * @param array $data
     * @signal
     */
    protected function emitRecordUpdatedSignal(array $data)
    {
        $this->getSignalSlotDispatcher()->dispatch(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class, 'recordUpdated', [$data]);
    }

    /**
     * Signal that is called after an IndexRecord is created
     *
     * @param array $data
     * @signal
     */
    protected function emitRecordCreatedSignal(array $data)
    {
        $this->getSignalSlotDispatcher()->dispatch(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class, 'recordCreated', [$data]);
    }

    /**
     * Signal that is called after an IndexRecord is deleted
     *
     * @param int $fileUid
     * @signal
     */
    protected function emitRecordDeletedSignal($fileUid)
    {
        $this->getSignalSlotDispatcher()->dispatch(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class, 'recordDeleted', [$fileUid]);
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
     */
    public static function getInstance()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class);
    }
}
