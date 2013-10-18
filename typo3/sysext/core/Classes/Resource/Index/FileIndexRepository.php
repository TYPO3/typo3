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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\File;

/**
 * Repository Class as an abstraction layer to sys_file
 *
 * Every access to table sys_file_metadata which is not handled by TCEmain
 * has to use this Repository class.
 *
 * This is meant for FAL internal use only!.
 */
class FileIndexRepository implements SingletonInterface {

	/**
	 * @var string
	 */
	protected $table = 'sys_file';

	/**
	 * A list of properties which are to be persisted
	 *
	 * @var array
	 */
	protected $fields = array(
		'uid', 'pid', 'missing', 'type', 'storage', 'identifier', 'extension',
		'mime_type', 'name', 'sha1', 'size', 'creation_date', 'modification_date',
	);

	/**
	 * Gets database instance
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabase() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns an Instance of the Repository
	 *
	 * @return FileIndexRepository
	 */
	public static function getInstance() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
	}

	/**
	 * Retrieves Index record for a given $combinedIdentifier
	 *
	 * @param string $combinedIdentifier
	 * @return array|bool
	 */
	public function findOneByCombinedIdentifier($combinedIdentifier) {
		list($storageUid, $identifier) = GeneralUtility::trimExplode(':', $combinedIdentifier, FALSE, 2);
		return $this->findOneByStorageUidAndIdentifier($storageUid, $identifier);
	}

	/**
	 * Retrieves Index record for a given $fileUid
	 *
	 * @param int $fileUid
	 * @return array|bool
	 */
	public function findOneByUid($fileUid) {
		$row = $this->getDatabase()->exec_SELECTgetSingleRow(
			implode(',', $this->fields),
			$this->table,
			'uid=' . intval($fileUid)
		);
		return is_array($row) ? $row : FALSE;
	}

	/**
	 * Retrieves Index record for a given $storageUid and $identifier
	 *
	 * @param int $storageUid
	 * @param string $identifier
	 * @return array|bool
	 *
	 * @internal only for use from FileRepository
	 */
	public function findOneByStorageUidAndIdentifier($storageUid, $identifier) {
		$row = $this->getDatabase()->exec_SELECTgetSingleRow(
			implode(',', $this->fields),
			$this->table,
			sprintf('storage=%u AND identifier=%s', intval($storageUid), $this->getDatabase()->fullQuoteStr($identifier, $this->table))
		);
		return is_array($row) ? $row : FALSE;
	}

	/**
	 * Retrieves Index record for a given $fileObject
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $fileObject
	 * @return array|bool
	 *
	 * @internal only for use from FileRepository
	 */
	public function findOneByFileObject(\TYPO3\CMS\Core\Resource\FileInterface $fileObject) {
		$storageUid = $fileObject->getStorage()->getUid();
		$identifier = $fileObject->getIdentifier();
		return $this->findOneByStorageUidAndIdentifier($storageUid, $identifier);
	}

	/**
	 * Returns all indexed files which match the content hash
	 * Used by the indexer to detect already present files
	 *
	 * @param string $hash
	 * @return mixed
	 */
	public function findByContentHash($hash) {
		if (!preg_match('/^[0-9a-f]{40}$/i', $hash)) {
			return array();
		}
		$resultRows = $this->getDatabase()->exec_SELECTgetRows(
			implode(',', $this->fields),
			$this->table,
			'sha1=' . $this->getDatabase()->fullQuoteStr($hash, $this->table)
		);
		return $resultRows;
	}

	/**
	 * Adds a file to the index
	 *
	 * @param File $file
	 */
	public function add(File $file) {
		if ($this->hasIndexRecord($file)) {
			$this->update($file);
			if ($file->_getPropertyRaw('uid') === NULL) {
				$file->updateProperties($this->findOneByFileObject($file));
			}
		} else {
			$data = array_intersect_key($file->getProperties(), array_flip($this->fields));
			$this->getDatabase()->exec_INSERTquery($this->table, $data);
			$data['uid'] = $this->getDatabase()->sql_insert_id();
			$file->updateProperties(array('uid' => $data['uid']));
			$this->emitRecordCreated($data);
		}
	}

	/**
	 * Checks if a file is indexed
	 *
	 * @param File $file
	 * @return bool
	 */
	public function hasIndexRecord(File $file) {
		return $this->getDatabase()->exec_SELECTcountRows('uid', $this->table, $this->getWhereClauseForFile($file)) >= 1;
	}

	/**
	 * Updates the index record in the database
	 *
	 * @param File $file
	 */
	public function update(File $file) {
		$updatedProperties = array_intersect($this->fields, $file->getUpdatedProperties());
		$updateRow = array();
		foreach ($updatedProperties as $key) {
			$updateRow[$key] = $file->getProperty($key);
		}
		if (count($updateRow) > 0) {
			$updateRow['tstamp'] = time();
			$this->getDatabase()->exec_UPDATEquery($this->table, $this->getWhereClauseForFile($file), $updateRow);
			$this->emitRecordUpdated(array_intersect_key($file->getProperties(), array_flip($this->fields)));
		}
	}

	/**
	 * Returns a where clause to find a file in database
	 *
	 * @param File $file
	 *
	 * @return string
	 */
	protected function getWhereClauseForFile(File $file) {
		if (intval($file->_getPropertyRaw('uid')) > 0) {
			$where = 'uid=' . intval($file->getUid());
		} else {
			$where = sprintf(
				'storage=%u AND identifier=%s',
				intval($file->getStorage()->getUid()),
				$this->getDatabase()->fullQuoteStr($file->_getPropertyRaw('identifier'), $this->table)
			);
		}
		return $where;
	}

	/**
	 * Remove a sys_file record from the database
	 *
	 * @param integer $fileUid
	 * @return void
	 */
	public function remove($fileUid) {
		$this->getDatabase()->exec_DELETEquery($this->table, 'uid=' . intval($fileUid));
		$this->emitRecordDeleted($fileUid);
	}

	/*
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
	 * Signal that is called after an IndexRecord is updated
	 *
	 * @param array $data
	 * @signal
	 */
	protected function emitRecordUpdated(array $data) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository', 'recordUpdated', array($data));
	}

	/**
	 * Signal that is called after an IndexRecord is created
	 *
	 * @param array $data
	 * @signal
	 */
	protected function emitRecordCreated(array $data) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository', 'recordCreated', array($data));
	}

	/**
	 * Signal that is called after an IndexRecord is deleted
	 *
	 * @param integer $fileUid
	 * @signal
	 */
	protected function emitRecordDeleted($fileUid) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository', 'recordDeleted', array($fileUid));
	}
}