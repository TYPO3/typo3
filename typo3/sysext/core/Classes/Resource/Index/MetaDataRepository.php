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
	 * Wrapper method for getting DatabaseConnection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabase() {
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
		$uid = intval($uid);
		if ($uid <= 0) {
			throw new \RuntimeException('Metadata can only be retrieved for indexed files.', 1381590731);
		}
		$record = $this->getDatabase()->exec_SELECTgetSingleRow('*', $this->tableName, 'file = ' . $uid);

		if ($record === FALSE) {
			$record = $this->createMetaDataRecord($uid);
		}

		return $record;
	}

	/**
	 * General Where-Clause which is needed to fetch only language 0 and live record.
	 *
	 * @return string
	 */
	protected function getGeneralWhereClause() {
		return ' AND 1=1';
	}
	/**
	 * Create empty
	 *
	 * @param int $fileUid
	 * @return array
	 */
	protected function createMetaDataRecord($fileUid) {
		$emptyRecord =  array(
			'file' => intval($fileUid),
			'pid' => 0,
			'crdate' => $GLOBALS['EXEC_TIME'],
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'cruser_id' => TYPO3_MODE == 'BE' ? $GLOBALS['BE_USER']->user['uid'] : 0
		);
		$this->getDatabase()->exec_INSERTquery($this->tableName, $emptyRecord);
		$record = $emptyRecord;
		$record['uid'] = $this->getDatabase()->sql_insert_id();

		return $record;
	}

	/**
	 * Updates the metadata record in the database
	 *
	 * @internal
	 * @param int $fileUid the file uid to update
	 * @param array $data Data to update
	 */
	public function update($fileUid, array $data) {
		$updateRow = array_intersect_key($data, $this->getDatabase()->admin_get_fields($this->tableName));
		if (array_key_exists('uid', $data)) {
			unset($data['uid']);
		}
		$row = $this->findByFileUid($fileUid);
		if (count($updateRow) > 0) {
			$updateRow['tstamp'] = time();
			$this->getDatabase()->exec_UPDATEquery($this->tableName, 'uid = ' . intval($row['uid']), $updateRow);
		}
	}
}