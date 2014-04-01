<?php

namespace TYPO3\CMS\Core\Resource\Index;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Repository Class as an abstraction layer to sys_file_metadata
 *
 * Every access to table sys_file_metadata which is not handled by TCEmain
 * has to use this Repository class
 */
class MetaDataRepository implements SingletonInterface {

	/**
	 * @var string
	 */
	protected $tableName = 'sys_file_metadata';

	/**
	 * Internal storage for database table fields
	 *
	 * @var array
	 */
	protected $tableFields = array();

	/**
	 * Wrapper method for getting DatabaseConnection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns array of meta-data properties
	 *
	 * @param File $file
	 * @return array
	 */
	public function findByFile(File $file) {
		return $this->findByFileUid($file->getUid());
	}

	/**
	 * Retrieves metadata for file
	 *
	 * @param int $uid
	 * @return array
	 * @throws \RuntimeException
	 */
	public function findByFileUid($uid) {
		$uid = (int)$uid;
		if ($uid <= 0) {
			throw new \RuntimeException('Metadata can only be retrieved for indexed files.', 1381590731);
		}
		$record = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', $this->tableName, 'file = ' . $uid . $this->getGeneralWhereClause());

		if ($record === FALSE) {
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
	protected function getGeneralWhereClause() {
		return ' AND sys_language_uid IN (0,-1) AND pid=0';
	}

	/**
	 * Create empty
	 *
	 * @param int $fileUid
	 * @param array $additionalFields
	 * @return array
	 */
	public function createMetaDataRecord($fileUid, array $additionalFields = array()) {
		$emptyRecord =  array(
			'file' => (int)$fileUid,
			'pid' => 0,
			'crdate' => $GLOBALS['EXEC_TIME'],
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0
		);
		$emptyRecord = array_merge($emptyRecord, $additionalFields);
		$this->getDatabaseConnection()->exec_INSERTquery($this->tableName, $emptyRecord);
		$record = $emptyRecord;
		$record['uid'] = $this->getDatabaseConnection()->sql_insert_id();

		$this->emitRecordCreated($record);

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
	public function update($fileUid, array $data) {
		if (count($this->tableFields) === 0) {
			$this->tableFields = $this->getDatabaseConnection()->admin_get_fields($this->tableName);
		}
		$updateRow = array_intersect_key($data, $this->tableFields);
		if (array_key_exists('uid', $updateRow)) {
			unset($updateRow['uid']);
		}
		$row = $this->findByFileUid($fileUid);
		if (count($updateRow) > 0) {
			$updateRow['tstamp'] = time();
			$this->getDatabaseConnection()->exec_UPDATEquery($this->tableName, 'uid = ' . (int)$row['uid'], $updateRow);

			$this->emitRecordUpdated(array_merge($row, $updateRow));
		}
	}

	/**
	 * Remove all metadata records for a certain file from the database
	 *
	 * @param integer $fileUid
	 * @return void
	 */
	public function removeByFileUid($fileUid) {
		$this->getDatabaseConnection()->exec_DELETEquery($this->tableName, 'file=' . (int)$fileUid);
		$this->emitRecordDeleted($fileUid);
	}

	/**
	 * Get the SignalSlot dispatcher
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		return $this->getObjectManager()->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
	}

	/**
	 * Get the ObjectManager
	 *
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * Signal that is called after a record has been loaded from database
	 * Allows other places to do extension of metadata at runtime or
	 * for example translation and workspace overlay
	 *
	 * @param \ArrayObject $data
	 * @signal
	 */
	protected function emitRecordPostRetrievalSignal(\ArrayObject $data) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', 'recordPostRetrieval', array($data));
	}

	/**
	 * Signal that is called after an IndexRecord is updated
	 *
	 * @param array $data
	 * @signal
	 */
	protected function emitRecordUpdated(array $data) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', 'recordUpdated', array($data));
	}

	/**
	 * Signal that is called after an IndexRecord is created
	 *
	 * @param array $data
	 * @signal
	 */
	protected function emitRecordCreated(array $data) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', 'recordCreated', array($data));
	}

	/**
	 * Signal that is called after an IndexRecord is deleted
	 *
	 * @param integer $fileUid
	 * @signal
	 */
	protected function emitRecordDeleted($fileUid) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', 'recordDeleted', array($fileUid));
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
	 */
	public static function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
	}
}
