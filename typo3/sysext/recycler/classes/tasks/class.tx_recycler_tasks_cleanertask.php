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

		$dateBefore = strtotime('-' . $this->getPeriod() . ' days');

		$queryParts = array();
		$queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] . ' = 1';
		$queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] . ' < ' . $dateBefore;

		$db->exec_DELETEquery($tableName, implode(' AND ', $queryParts));

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
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/tasks/class.tx_recycler_task_cleanertask.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/tasks/class.tx_recycler_task_cleanertask.php']);
}

?>