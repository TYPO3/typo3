<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * A task that should be run regularly that deletes deleted
 * datasets from the DB.
 *
 * @author Philipp Bergsmann <p.bergsmann@opendo.at>
 * @package TYPO3
 * @subpackage tx_recycler
 */
class tx_recycler_tasks_CleanerTask extends tx_scheduler_Task {

	/**
	 * @var int $period The time period, after which the rows are deleted
	 */
	protected $period = 0;

	/**
	 * @var array $TCATables The tables to clean
	 */
	protected $TCATables = array();

	/**
	 * @var t3lib_db $DB The database-connection object
	 */
	protected $DB = NULL;

	/**
	 * The main method of the task. Iterates through
	 * the tables and calls the cleaning function
	 *
	 * @return boolean Returns TRUE on successful execution, FALSE on error
	 */
	public function execute()
	{
		$success = TRUE;

		foreach ($this->getTCATables() as $table) {
			if ($this->cleanTable($table) === FALSE) {
				$success = FALSE;
			}
		}

		return $success;
	}

	/**
	 * Executes the delete-query for the given table
	 *
	 * @param $tableName
	 * @return bool
	 */
	protected function cleanTable($tableName) {
		$db = $this->getDB();


		$queryParts = array();
		if (isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
			$queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] . ' = 1';
			if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp']) {
				$dateBefore = strtotime('-' . $this->getPeriod() . ' days');
				$queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] . ' < ' . $dateBefore;
			}
			$where = implode(' AND ', $queryParts);

			$this->checkFileResourceFieldsBeforeDeletion($tableName, $where);

			$db->exec_DELETEquery($tableName, $where);
		}

		$success = ($db->sql_error() === '') ? TRUE : FALSE;

		return $success;
	}

	/**
	 * Returns the information shown in the task-list
	 *
	 * @return string Information-text fot the scheduler task-list
	 */
	public function getAdditionalInformation() {
		$message = '';

		$message .= sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskDescriptionTables'),
				implode(', ', $this->getTCATables())
			) . '; ';

		$message .= sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskDescriptionDays'),
				$this->getPeriod()
			);

		return $message;
	}

	/**
	 * Sets the period after which a row is deleted
	 *
	 * @param int $period
	 */
	public function setPeriod($period) {
		$this->period = (int) $period;
	}

	/**
	 * Returns the period after which a row is deleted
	 *
	 * @return int
	 */
	public function getPeriod() {
		return $this->period;
	}

	/**
	 * Sets the TCA-tables which are cleaned
	 *
	 * @param array $tables
	 */
	public function setTCATables($tables = array()) {
		$this->TCATables = $tables;
	}

	/**
	 * Returns the TCA-tables which are cleaned
	 *
	 * @return array
	 */
	public function getTCATables() {
		return $this->TCATables;
	}

	/**
	 * Sets the DB-object - used for unit-tests
	 *
	 * @param t3lib_db $DB
	 */
	public function setDB(t3lib_db $DB) {
		$this->DB = $DB;
	}

	/**
	 * Returns the DB-object
	 *
	 * @return t3lib_db
	 */
	public function getDB() {
		if (is_null($this->DB)) {
			return $GLOBALS['TYPO3_DB'];
		} else {
			return $this->DB;
		}
	}

	/**
	 * Checks all resource fields for the given table and condition and makles sure that resources are freed.
	 *
	 * @param string $table
	 * @param string $where
	 * @return void
	 */
	protected function checkResourceFieldsBeforeDeletion($table, $where) {
		t3lib_div::loadTCA($table);
		$this->checkFileResourceFieldsBeforeDeletion($table, $where);
	}

	/**
	 * Checks if the table has fields for uploaded files and removes those files.
	 *
	 * @param string $table
	 * @param string $where
	 * @return void
	 */
	protected function checkFileResourceFieldsBeforeDeletion($table, $where) {
		$fieldList = $this->getFileResourceFields($table);
		if (count($fieldList) > 0) {
			$this->deleteFilesForTable($table, $where, $fieldList);
		}
	}

	/**
	 * Removes all files from the given field list in the table.
	 *
	 * @param string $table
	 * @param string $where
	 * @param array $fieldList
	 */
	protected function deleteFilesForTable($table, $where, array $fieldList) {
		$resource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',', $fieldList), $table, $where);
		while (FALSE !== ($data = ($GLOBALS['TYPO3_DB']->sql_fetch_assoc($resource)))) {
			foreach ($fieldList as $fieldName) {
				$uploadDir = PATH_site . $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['uploadfolder'] . '/';
				$fileList = t3lib_div::trimExplode(',', $data[$fieldName]);
				foreach ($fileList as $fileName) {
					@unlink($uploadDir . $fileName);
				}
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($resource);
	}

	/**
	 * Checks the $TCA for fields that can list file resources.
	 *
	 * @param string $table
	 * @return array
	 */
	protected function getFileResourceFields($table) {
		$result = array();
		if (isset($GLOBALS['TCA'][$table]['columns'])) {
			foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $fieldConfiguration) {
				if ($fieldConfiguration['config']['type'] == 'group' && $fieldConfiguration['config']['internal_type'] == 'file') {
					$result[] = $fieldName;
					break;
				}
			}
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/tasks/class.tx_recycler_task_cleanertask.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/tasks/class.tx_recycler_task_cleanertask.php']);
}

?>