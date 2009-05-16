<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The Query classs used to run queries against the database
 *
 * @package TYPO3
 * @subpackage Extbase
 * @version $Id$
 * @scope prototype
 */
class Tx_Extbase_Persistence_Query implements Tx_Extbase_Persistence_QueryInterface {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var Tx_Extbase_Persistence_DataMapperInterface
	 */
	protected $dataMapper;

	/**
	 * @var Tx_Extbase_Persistence_Session
	 */
	protected $persistenceSession;
		
	/**
	 * @var Tx_Extbase_Persistence_ConstraintInterface
	 */
	protected $constraint;

	/**
	 * Constructs a query object working on the given class name
	 *
	 * @param string $className
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Injects the persistence backend to fetch the data from
	 *
	 * @param t3lib_DB $persistenceBackend
	 * @return void
	 */
	public function injectPersistenceBackend(t3lib_DB $persistenceBackend) {
		$this->persistenceBackend = $persistenceBackend;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param Tx_Extbase_Persistence_DataMapperInterface $dataMapper
	 * @return void
	 */
	public function injectDataMapper(Tx_Extbase_Persistence_DataMapperInterface $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Executes the query against the database and returns the result
	 *
	 * @return array The query result as an array of objects
	 */
	public function execute() {
		$statement = $constraint->getStatement();
		$res = $this->database->exec_SELECTquery(
			'*',
			$from,
			$where . $enableFields,
			$groupBy,
			$orderBy,
			$limit
			);

		if ($res) {
			$fieldMap = $this->getFieldMapFromResult($res);
			$rows = $this->getRowsFromResult($dataMap->getTableName(), $res);
		}

		$objects = array();
		if (is_array($rows)) {
			if (count($rows) > 0) {
				$objects = $this->dataMapper->reconstituteObjects($dataMap, $fieldMap, $rows);
			}
		}
		return $objects;
	}
	
	/**
	 * The constraint used to limit the result set
	 *
	 * @param Tx_Extbase_Persistence_ConstraintInterface $constraint Some constraint, depending on the backend
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function matching(Tx_Extbase_Persistence_ConstraintInterface $constraint) {
		$this->constraint = $constraint;
		return $this;
	}
	
	protected function getRowsFromResult($tableName, $res) {
		$rows = array();
		while ($row = $this->persistenceBackend->sql_fetch_assoc($res)) {
			$row = $this->doLanguageAndWorkspaceOverlay($tableName, $row);
			if (is_array($row)) {
				$arrayKeys = range(0,count($row));
				array_fill_keys($arrayKeys, $row);
				$rows[] = $row;
			}
		}
		$this->persistenceBackend->sql_free_result($res);
		return $rows;
	}
	
	protected function getFieldMapFromResult($res) {
		$fieldMap = array();
		if ($res !== FALSE) {
			$fieldPosition = 0;
			// TODO mysql_fetch_field should be available in t3lib_db (patch core)
			while ($field = mysql_fetch_field($res)) {
				$fieldMap[$field->table][$field->name] = $fieldPosition;
				$fieldPosition++;
			}
		}
		return $fieldMap;
	}
	
}
?>