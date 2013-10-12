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
		'uid', 'pid',	'missing', 'type', 'storage', 'identifier',
		'extension', 'mime_type', 'name', 'title', 'sha1', 'size', 'creation_date',
		'modification_date', 'width', 'height', 'description', 'alternative'
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
			'*',
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
			'*',
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
			'*',
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
		} else {
			$data = array_intersect_key($file->getProperties(), array_flip($this->fields));
			$this->getDatabase()->exec_INSERTquery($this->table, $data);
			$file->updateProperties(array('uid' => $this->getDatabase()->sql_insert_id()));
		}
	}

	/**
	 * Checks if a file is indexed
	 *
	 * @param File $file
	 * @return bool
	 */
	public function hasIndexRecord(File $file) {
		if (intval($file->getUid()) > 0) {
			$where = 'uid=' . intval($file->getUid());

		} else {
			$where = sprintf(
				'storage=%u AND identifier=%s',
				intval($file->getStorage()->getUid()),
				$this->getDatabase()->fullQuoteStr($file->getIdentifier(), $this->table)
			);
		}
		return $this->getDatabase()->exec_SELECTcountRows('uid', $this->table, $where) === 1;
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
			$this->getDatabase()->exec_UPDATEquery($this->table, 'uid=' . intval($file->getUid()), $updateRow);
		}
	}
}
