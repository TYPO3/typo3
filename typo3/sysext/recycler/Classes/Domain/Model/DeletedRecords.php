<?php
namespace TYPO3\CMS\Recycler\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Julian Kleinhans <typo3@kj187.de>
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
 * Model class for the 'recycler' extension.
 *
 * @author 	Julian Kleinhans <typo3@kj187.de>
 */
class DeletedRecords {

	/**
	 * Array with all deleted rows
	 *
	 * @var 	array
	 */
	protected $deletedRows = array();

	/**
	 * String with the global limit
	 *
	 * @var 	string
	 */
	protected $limit = '';

	/**
	 * Array with all avaiable FE tables
	 *
	 * @var 	array
	 */
	protected $table = array();

	/**
	 * Object from helper class
	 *
	 * @var \TYPO3\CMS\Recycler\Utility\RecyclerUtility
	 */
	protected $recyclerHelper;

	/**
	 * Array with all label fields drom different tables
	 *
	 * @var 	array
	 */
	public $label;

	/**
	 * Array with all title fields drom different tables
	 *
	 * @var 	array
	 */
	public $title;

	/************************************************************
	 * GET DATA FUNCTIONS
	 *
	 *
	 ************************************************************/
	/**
	 * Load all deleted rows from $table
	 * If table is not set, it iterates the TCA tables
	 *
	 * @param 	integer		$id: UID from selected page
	 * @param 	string		$table: Tablename
	 * @param 	integer		$depth: How many levels recursive
	 * @param 	integer		$limit: MySQL LIMIT
	 * @param 	string		$filter: Filter text
	 * @return 	recycler_model_delRecords
	 */
	public function loadData($id, $table, $depth, $limit = '', $filter = '') {
		// set the limit
		$this->limit = trim($limit);
		if ($table) {
			if (array_key_exists($table, $GLOBALS['TCA'])) {
				$this->table[] = $table;
				$this->setData($id, $table, $depth, $GLOBALS['TCA'][$table]['ctrl'], $filter);
			}
		} else {
			foreach ($GLOBALS['TCA'] as $tableKey => $tableValue) {
				// only go into this table if the limit allows it
				if ($this->limit != '') {
					$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->limit);
					// abort loop if LIMIT 0,0
					if ($parts[0] == 0 && $parts[1] == 0) {
						break;
					}
				}
				$this->table[] = $tableKey;
				$this->setData($id, $tableKey, $depth, $tableValue['ctrl'], $filter);
			}
		}
		return $this;
	}

	/**
	 * Find the total count of deleted records
	 *
	 * @param 	integer		$id: UID from record
	 * @param 	string		$table: Tablename from record
	 * @param 	integer		$depth: How many levels recursive
	 * @param 	string		$filter: Filter text
	 * @return 	void
	 */
	public function getTotalCount($id, $table, $depth, $filter) {
		$deletedRecords = $this->loadData($id, $table, $depth, '', $filter)->getDeletedRows();
		$countTotal = 0;
		foreach ($this->table as $tableName) {
			$countTotal += count($deletedRecords[$tableName]);
		}
		return $countTotal;
	}

	/**
	 * Set all deleted rows
	 *
	 * @param 	integer		$id: UID from record
	 * @param 	string		$table: Tablename from record
	 * @param 	integer		$depth: How many levels recursive
	 * @param 	array		$ctrl: TCA CTRL Array
	 * @param 	string		$filter: Filter text
	 * @return 	void
	 */
	protected function setData($id = 0, $table, $depth, $tcaCtrl, $filter) {
		$id = intval($id);
		if (array_key_exists('delete', $tcaCtrl)) {
			// find the 'deleted' field for this table
			$deletedField = \TYPO3\CMS\Recycler\Utility\RecyclerUtility::getDeletedField($table);
			// create the filter WHERE-clause
			if (trim($filter) != '') {
				$filterWhere = ' AND (' . (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($filter) ? 'uid = ' . $filter . ' OR pid = ' . $filter . ' OR ' : '') . $tcaCtrl['label'] . ' LIKE "%' . $this->escapeValueForLike($filter, $table) . '%"' . ')';
			}
			// get the limit
			if ($this->limit != '') {
				// count the number of deleted records for this pid
				$deletedCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', $table, $deletedField . '<>0 AND pid = ' . $id . $filterWhere);
				// split the limit
				$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->limit);
				$offset = $parts[0];
				$rowCount = $parts[1];
				// subtract the number of deleted records from the limit's offset
				$result = $offset - $deletedCount;
				// if the result is >= 0
				if ($result >= 0) {
					// store the new offset in the limit and go into the next depth
					$offset = $result;
					$this->limit = implode(',', array($offset, $rowCount));
					// do NOT query this depth; limit also does not need to be set, we set it anyways
					$allowQuery = FALSE;
					$allowDepth = TRUE;
					$limit = '';
				} else {
					// the offset for the temporary limit has to remain like the original offset
					// in case the original offset was just crossed by the amount of deleted records
					if ($offset != 0) {
						$tempOffset = $offset;
					} else {
						$tempOffset = 0;
					}
					// set the offset in the limit to 0
					$newOffset = 0;
					// convert to negative result to the positive equivalent
					$absResult = abs($result);
					// if the result now is > limit's row count
					if ($absResult > $rowCount) {
						// use the limit's row count as the temporary limit
						$limit = implode(',', array($tempOffset, $rowCount));
						// set the limit's row count to 0
						$this->limit = implode(',', array($newOffset, 0));
						// do not go into new depth
						$allowDepth = FALSE;
					} else {
						// if the result now is <= limit's row count
						// use the result as the temporary limit
						$limit = implode(',', array($tempOffset, $absResult));
						// subtract the result from the row count
						$newCount = $rowCount - $absResult;
						// store the new result in the limit's row count
						$this->limit = implode(',', array($newOffset, $newCount));
						// if the new row count is > 0
						if ($newCount > 0) {
							// go into new depth
							$allowDepth = TRUE;
						} else {
							// if the new row count is <= 0 (only =0 makes sense though)
							// do not go into new depth
							$allowDepth = FALSE;
						}
					}
					// allow query for this depth
					$allowQuery = TRUE;
				}
			} else {
				$limit = '';
				$allowDepth = TRUE;
				$allowQuery = TRUE;
			}
			// query for actual deleted records
			if ($allowQuery) {
				$recordsToCheck = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField($table, $deletedField, '1', ' AND pid = ' . $id . $filterWhere, '', '', $limit, FALSE);
				if ($recordsToCheck) {
					$this->checkRecordAccess($table, $recordsToCheck);
				}
			}
			// go into depth
			if ($allowDepth && $depth >= 1) {
				// check recursively for elements beneath this page
				$resPages = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . $id, '', 'sorting');
				if (is_resource($resPages)) {
					while ($rowPages = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resPages)) {
						$this->setData($rowPages['uid'], $table, $depth - 1, $tcaCtrl, $filter);
						// some records might have been added, check if we still have the limit for further queries
						if ('' != $this->limit) {
							$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->limit);
							// abort loop if LIMIT 0,0
							if ($parts[0] == 0 && $parts[1] == 0) {
								break;
							}
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($resPages);
				}
			}
			$this->label[$table] = $tcaCtrl['label'];
			$this->title[$table] = $tcaCtrl['title'];
		}
	}

	/**
	 * Checks whether the current backend user has access to the given records.
	 *
	 * @param 	string		$table: Name of the table
	 * @param 	array		$rows: Record row
	 * @return 	void
	 */
	protected function checkRecordAccess($table, array $rows) {
		foreach ($rows as $key => $row) {
			if (\TYPO3\CMS\Recycler\Utility\RecyclerUtility::checkAccess($table, $row)) {
				$this->setDeletedRows($table, $row);
			}
		}
	}

	/**
	 * Escapes a value to be used for like in a database query.
	 * There is a special handling for the characters '%' and '_'.
	 *
	 * @param 	string		$value: The value to be escaped for like conditions
	 * @param 	string		$tableName: The name of the table the query should be used for
	 * @return 	string		The escaped value to be used for like conditions
	 */
	protected function escapeValueForLike($value, $tableName) {
		return $GLOBALS['TYPO3_DB']->escapeStrForLike($GLOBALS['TYPO3_DB']->quoteStr($value, $tableName), $tableName);
	}

	/************************************************************
	 * DELETE FUNCTIONS
	 ************************************************************/
	/**
	 * Delete element from any table
	 *
	 * @param 	string		$recordArray: Representation of the records
	 * @return 	void
	 */
	public function deleteData($recordsArray) {
		$recordsArray = json_decode($recordsArray);
		if (is_array($recordsArray)) {
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->start('', '');
			$tce->disableDeleteClause();
			foreach ($recordsArray as $key => $record) {
				$tce->deleteEl($record[0], $record[1], TRUE, TRUE);
			}
			return TRUE;
		}
		return FALSE;
	}

	/************************************************************
	 * UNDELETE FUNCTIONS
	 ************************************************************/
	/**
	 * Undelete records
	 * If $recursive is TRUE all records below the page uid would be undelete too
	 *
	 * @param 	string		$recordArray: Representation of the records
	 * @param 	boolean		$recursive: TRUE/FALSE
	 * @return 	boolean
	 */
	public function undeleteData($recordsArray, $recursive = FALSE) {
		$result = FALSE;
		$depth = 999;
		$recordsArray = json_decode($recordsArray);
		if (is_array($recordsArray)) {
			$this->deletedRows = array();
			$cmd = array();
			foreach ($recordsArray as $key => $row) {
				$cmd[$row[0]][$row[1]]['undelete'] = 1;
				if ($row[0] == 'pages' && $recursive == TRUE) {
					$this->loadData($row[1], '', $depth, '');
					$childRecords = $this->getDeletedRows();
					if (count($childRecords) > 0) {
						foreach ($childRecords as $table => $childRows) {
							foreach ($childRows as $childKey => $childRow) {
								$cmd[$table][$childRow['uid']]['undelete'] = 1;
							}
						}
					}
				}
			}
			if ($cmd) {
				$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$tce->start(array(), $cmd);
				$tce->process_cmdmap();
				$result = TRUE;
			}
		}
		return $result;
	}

	/************************************************************
	 * SETTER FUNCTIONS
	 ************************************************************/
	/**
	 * Set deleted rows
	 *
	 * @param 	string		$table: Tablename
	 * @param 	array		$row: Deleted record row
	 * @return 	void
	 */
	public function setDeletedRows($table, array $row) {
		$this->deletedRows[$table][] = $row;
	}

	/************************************************************
	 * GETTER FUNCTIONS
	 ************************************************************/
	/**
	 * Get deleted Rows
	 *
	 * @return 	array		$this->deletedRows: Array with all deleted rows from TCA
	 */
	public function getDeletedRows() {
		return $this->deletedRows;
	}

	/**
	 * Get table
	 *
	 * @return 	array		$this->table: Array with table from TCA
	 */
	public function getTable() {
		return $this->table;
	}

}


?>