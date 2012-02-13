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
	 * @var int
	 */
	protected $period = 0;

	/**
	 * @var array
	 */
	protected $TCATables = array();

	/**
	 * @var t3lib_db
	 */
	protected $DB = NULL;

	/**
	 * This is the main method that is called when a task is executed
	 * It MUST be implemented by all classes inheriting from this one
	 * Note that there is no error handling, errors and failures are expected
	 * to be handled and logged by the client implementations.
	 * Should return TRUE on successful execution, FALSE on error.
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
	 * executes the delete-query for the given table
	 *
	 * @param $tableName
	 * @return bool
	 */
	protected function cleanTable($tableName) {
		global $TCA;

		$db = $this->getDB();

		$dateBefore = strtotime('-' . $this->getPeriod() . ' days');

		$queryParts = array();
		$queryParts[] = $TCA[$tableName]['ctrl']['delete'] . ' = 1';
		$queryParts[] = $TCA[$tableName]['ctrl']['tstamp'] . ' < ' . $dateBefore;

		$db->exec_DELETEquery($tableName, implode(' AND ', $queryParts));

		$success = ($db->sql_error() === '') ? TRUE : FALSE;

		return $success;
	}

	/**
	 * returns the information shown in the task-list
	 *
	 * @return string
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
	 * @param int $period
	 */
	public function setPeriod($period) {
		$this->period = (int) $period;
	}

	/**
	 * @return int
	 */
	public function getPeriod() {
		return $this->period;
	}

	/**
	 * @param array $tables
	 */
	public function setTCATables($tables = array()) {
		$this->TCATables = $tables;
	}

	/**
	 * @return array
	 */
	public function getTCATables() {
		return $this->TCATables;
	}

	/**
	 * @param t3lib_db $DB
	 */
	public function setDB(t3lib_db $DB) {
		$this->DB = $DB;
	}

	/**
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