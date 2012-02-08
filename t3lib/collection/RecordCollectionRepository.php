<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2012 Steffen Ritter <typo3@steffen-ritter.net>
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

/**
 * Implements the repository for record collections.
 *
 * @author Steffen Ritter <typo3@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_collection_RecordCollectionRepository {
	const TYPE_Static = 'static';

	/**
	 * the table name collections are stored to
	 *
	 * @var string
	 */
	protected $table = 'sys_collection';

	/**
	 * @var string
	 */
	protected $typeField = 'type';

	/**
	 * @var string
	 */
	protected $tableField = 'table_name';

	/**
	 * Finds a record collection by uid.
	 *
	 * @param integer $uid The uid to be looked up
	 * @return NULL|t3lib_collection_RecordCollection
	 */
	public function findByUid($uid) {
		if (!is_numeric($uid)) {
			throw new InvalidArgumentException('uid has to be numeric.', 1316779798);
		}

		$result = NULL;

		$data = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid',
			$this->table,
			'uid = ' . $uid . t3lib_BEfunc::deleteClause($this->table)
		);

		if ($data !== NULL) {
			$result = $this->createDomainObject($data);
		}

		return $result;
	}

	/**
	 * Finds record collections by table name.
	 *
	 * @param string $tableName Name of the table to be looked up
	 * @return t3lib_collection_RecordCollection[]
	 */
	public function findByTableName($tableName) {
		$conditions = array(
			$this->tableField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tableName, $this->table),
		);

		return $this->queryMultipleRecords($conditions);
	}

	/**
	 * Finds record collection by type.
	 *
	 * @param string $type Type to be looked up
	 * @return NULL|t3lib_collection_RecordCollection[]
	 */
	public function findByType($type) {
		$conditions = array(
			$this->typeField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table),
		);

		return $this->queryMultipleRecords($conditions);
	}

	/**
	 * Finds record collections by type and table name.
	 *
	 * @param string $type Type to be looked up
	 * @param string $tableName Name of the table to be looked up
	 * @return NULL|t3lib_collection_RecordCollection[]
	 */
	public function findByTypeAndTableName($type, $tableName) {
		$conditions = array(
			$this->typeField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($type, $this->table),
			$this->tableField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tableName, $this->table),
		);

		return $this->queryMultipleRecords($conditions);
	}

	/**
	 * Deletes a record collection by uid.
	 *
	 * @param integer $uid uid to be deleted
	 */
	public function deleteByUid($uid) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$this->table, 'uid=' . intval($uid),
			array('deleted' => 1, 'tstamp' => $GLOBALS['EXEC_TIME'])
		);
	}

	/**
	 * Queries for multiple records for the given conditions.
	 *
	 * @param array $conditions Conditions concatenated with AND for query
	 * @return NULL|t3lib_collection_RecordCollection[]
	 */
	protected function queryMultipleRecords(array $conditions = array()) {
		$result = NULL;

		$data = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->table,
			implode(' AND ', $conditions) . t3lib_BEfunc::deleteClause($this->table)
		);

		if ($data !== NULL) {
			$result = $this->createMultipleDomainObjects($data);
		}

		return $result;
	}

	/**
	 * Creates a record collection domain object.
	 *
	 * @param array $record Database record to be reconstituted
	 * @return t3lib_collection_RecordCollection
	 */
	protected function createDomainObject(array $record) {
		switch ($record['type']) {
			case self::TYPE_Static:
				$collection = t3lib_collection_StaticRecordCollection::load($record['uid']);
				break;
			default:
				throw new RuntimeException('Unknown record collection type "' . $record['type'], 1328646798);
		}

		return $collection;
	}

	/**
	 * Creates multiple record collection domain objects.
	 *
	 * @param array $data Array of multiple database records to be reconstituted
	 * @return t3lib_collection_RecordCollection[]
	 */
	protected function createMultipleDomainObjects(array $data) {
		$collections = array();

		foreach ($data as $collection) {
			$collections[] = $this->createDomainObject($collection);
		}

		return $collections;
	}
}
?>