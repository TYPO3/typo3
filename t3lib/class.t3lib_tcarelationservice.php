<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Steffen Ritter <steffen.ritter@typo3.org>
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
 * Class as helper tool to resolve relations, configured by $TCA
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 * @package typo3
 * @subpackage t3lib
 */
class t3lib_TcaRelationService {

	/**
	 * The relatedTableFallback we are dealing with
	 *
	 * @var string
	 */
	protected $localTable;


	/**
	 * The field which defines the relation on table
	 *
	 * @var string
	 */
	protected $localRelationField = NULL;

	/**
	 * The relatedTableFallback the relation is built to
	 *
	 * @var string
	 */
	protected $foreignTable = NULL;

	/**
	 * The field which defines the relation on foreignTable
	 *
	 * @var string
	 */
	protected $foreignRelationField = NULL;

	/**
	 * Creates a new instance of the relation Service
	 *
	 * @param string $table The table for which we want to create relations
	 * @param string|NULL $field The table column which defines the relation
	 * @param string|NULL $relatedTable
	 * @param string|NULL $relatedField
	 */
	public function __construct($table, $field = NULL, $relatedTable = NULL, $relatedField = NULL) {
		$this->localTable = $table;
		$this->localRelationField = $field;
		$this->foreignTable = $relatedTable;
		$this->foreignRelationField = $relatedField;

		t3lib_div::loadTCA($table);

		if ($field !== NULL) {
			$this->foreignTable = $this->detectRelatedTable($table, $field, $relatedTable);
			$this->foreignRelationField = $this->detectForeignRelationField($table, $field, $relatedField);
		} elseif ($relatedTable !== NULL && $relatedField !== NULL) {
			$this->localRelationField = $this->detectForeignRelationField($relatedTable, $relatedField, $field);
		}
	}

	/**
	 * Detects the database table a TCA configuration on $table in column $relationField references
	 *
	 * @throws t3lib_error_Exception
	 * @param string $table
	 * @param string $relationField
	 * @param string|NULL $relatedTableFallback
	 * @param bool $throwException
	 *
	 * @return string|NULL
	 */
	protected function detectRelatedTable($table, $relationField, $relatedTableFallback, $throwException = TRUE) {
		$columnConfiguration = $GLOBALS['TCA'][$table]['columns'][$relationField]['config'];
		$relatedTable = NULL;
		switch ($columnConfiguration['type']) {
			case 'inline':
			case 'select':
				$relatedTable = $columnConfiguration['foreign_table'];
				break;
			case 'group':
				$tables = t3lib_div::trimExplode(',', $columnConfiguration['allowed'] , TRUE);
				if (count($tables) == 1) {
					$relatedTable = $tables[0];
				} elseif ($throwException) {
					throw new t3lib_error_Exception("The Relation Service is not able to handle TCA type 'group' with multiple allowed tables.", 1317306986);
				}
				break;
			default:
				switch ($relationField) {
					case 'pid':
						$relatedTable = 'pages';
						break;
					case 'l18n_parent':
						$relatedTable = $table;
						break;
				};
		}

		if ($relatedTable === NULL) {
			if ($relatedTableFallback === NULL && $throwException) {
				throw new t3lib_error_Exception("The Relation Service was not able to auto-detect the related table, please provide the configuration manually.", 1317306985);
			} else {
				$relatedTable = $relatedTableFallback;
			}
		}
		return $relatedTable;
	}

	/**
	 * Tries to auto-detect the field on the foreign side
	 *
	 * @param string $table
	 * @param string $relationField
	 * @param string|NULL $fieldFallback
	 * @return string|NULL
	 */
	protected function detectForeignRelationField($table, $relationField, $fieldFallback) {
		$columnConfiguration = $GLOBALS['TCA'][$table]['columns'][$relationField]['config'];

		$field = NULL;
		if (isset($columnConfiguration['MM_opposite_field'])) {
			$field = $columnConfiguration['MM_opposite_field'];
		} elseif (isset($columnConfiguration['foreign_field'])) {
			$field = $columnConfiguration['foreign_field'];
		}

		if ($this->foreignRelationField === NULL) {
			if ($fieldFallback !== NULL) {
				$field = $fieldFallback;
			} else {
				t3lib_div::loadTCA($this->foreignTable);
				$columns = array();
				foreach ($GLOBALS['TCA'][$this->foreignTable]['columns'] AS $column => $config) {
					if ($this->localTable == $this->detectRelatedTable($this->foreignTable, $column, NULL, FALSE)) {
						$columns[] = $column;
					}
				}
				if (count($columns) == 1) {
					$field = $columns[0];
				}
			}
		}

		return $field;
	}

	/**
	 * Retrieves all records, which are referenced by the current record
	 *
	 * @param array $row
	 * @return array[]
	 */
	public function getRecordsWithRelationFromCurrentRecord(array $row) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->foreignTable,
			'uid IN ('. implode(',', $this->getRecordUidsWithRelationFromCurrentRecord($row)) . ')'
		);
	}

	/**
	 * Retrieves all records, which reference the current record
	 *
	 * @param array $row at least the uid and the "lookup field" must be set.
	 * @return array[]
	 */
	public function getRecordsWithRelationToCurrentRecord(array $row) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$this->foreignTable,
			'uid IN ('. implode(',', $this->getRecordUidsWithRelationToCurrentRecord($row)) . ')'
		);
	}

	/**
	 * Retrieves the uids from all records, which are referenced by the current record
	 *
	 * @param array $row at least the uid and the "lookup field" must be set.
	 * @return int[]
	 */
	public function getRecordUidsWithRelationFromCurrentRecord(array $row) {
		$relatedUids = array();

		$table = $this->localTable;
		$field = $this->localRelationField;

		$foreignTable = $this->foreignTable;
		$foreignField = $this->foreignRelationField;

		$uid = $row['uid'];
		$value = $row[$field];
		$columnConfiguration = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

		switch ((string) $columnConfiguration['type']) {
			case 'inline':
			case 'select':
				if ($columnConfiguration['MM']) {
					/** @var $dbGroup t3lib_loadDBGroup */
					$dbGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbGroup->start(
						$value,
						$foreignTable,
						$columnConfiguration['MM'],
						$uid,
						$table,
						$columnConfiguration
					);
					$relatedUids = $dbGroup->tableArray[$foreignTable];
				} elseif ($foreignField != NULL && $columnConfiguration['type'] == 'inline') {
					$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid',
						$foreignTable,
						$foreignField . '=' . intval($uid)
					);
					foreach ($records as $record) {
						$relatedUids[] = $record['uid'];
					}
				} else {
					$relatedUids = t3lib_div::intExplode(',', $value, TRUE);
				}
				break;
			case 'group':
				if ($columnConfiguration['MM']) {
					/** @var $dbGroup t3lib_loadDBGroup */
					$dbGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbGroup->start(
						$value,
						$foreignTable,
						$columnConfiguration['MM'],
						$uid,
						$table,
						$columnConfiguration
					);
					$relatedUids = $dbGroup->tableArray[$foreignTable];
				} else {
					$relatedUids = t3lib_div::intExplode(',', $value, TRUE);
				}
				break;
			default:
				$relatedUids = t3lib_div::intExplode(',', $value, TRUE);
		}

		return $relatedUids;
	}

	/**
	 * Retrieves the uids from all records, which reference the current record
	 *
	 * @param array $row at least the uid and the "lookup field" must be set.
	 * @return int[]
	 */
	public function getRecordUidsWithRelationToCurrentRecord(array $row) {
		$relatedUids = array();

		$columnConfiguration = $GLOBALS['TCA'][$this->localTable]['columns'][$this->localRelationField]['config'];
		$foreignColumnConfiguration = $GLOBALS['TCA'][$this->foreignTable]['columns'][$this->foreignRelationField]['config'];
		switch ((string) $columnConfiguration['type']) {
			case 'inline':
			case 'select':
			case 'group':
				if ($columnConfiguration['MM']) {
					/** @var $dbGroup t3lib_loadDBGroup */
					$dbGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
						// dummy field for setting "look from other site"
					if (isset($foreignColumnConfiguration['MM_oppositeField'])) {
						$columnConfiguration['MM_oppositeField'] = $foreignColumnConfiguration['MM_oppositeField'];
					} else {
						$columnConfiguration['MM_oppositeField'] = 'dummy';
					}

					$dbGroup->start(
						$row[$this->localRelationField],
						$this->foreignTable,
						$columnConfiguration['MM'],
						$row['uid'],
						$this->localTable,
						$columnConfiguration
					);
					$relatedUids = $dbGroup->tableArray[$this->foreignTable];
				} elseif ($this->foreignRelationField !== NULL) {
					$relatedUids = $this->listFieldQuery($this->foreignRelationField, $this->foreignTable, $row['uid']);
				} else {
					$relatedUids = $this->listFieldQuery($this->localRelationField, $this->localTable, $row['uid']);
				}
			break;
			default:
				if ($this->foreignRelationField !== NULL) {
					$relatedUids = $this->listFieldQuery($this->foreignRelationField, $this->foreignTable, $row['uid']);
				}
		}

		return $relatedUids;
	}

	/**
	 * Queries the relatedTabl for an field which might contain a list of uids.
	 *
	 * @param string $fieldName the name of the field to be queried
	 * @param string $table the name of the relatedTableFallback to be queried
	 * @param int $queryId the uid to search for
	 * @return int[] all uids found
	 */
	protected function listFieldQuery($fieldName, $table, $queryId) {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid',
			$table,
			$GLOBALS['TYPO3_DB']->listQuery($fieldName, intval($queryId), $table)
				. (intval($queryId) == 0 ? (' OR ' . $fieldName . ' = \'\'') : '')
		);
		$uidArray = array();
		foreach ($records as $record) {
			$uidArray[] = $record['uid'];
		}
		return $uidArray;
	}
}

?>