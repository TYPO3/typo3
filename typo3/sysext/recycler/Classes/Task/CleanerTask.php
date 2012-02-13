<?php
namespace TYPO3\CMS\Recycler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 */
class CleanerTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * @var integer The time period, after which the rows are deleted
	 */
	protected $period = 0;

	/**
	 * @var array The tables to clean
	 */
	protected $tcaTables = array();

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection = NULL;

	/**
	 * The main method of the task. Iterates through
	 * the tables and calls the cleaning function
	 *
	 * @return boolean Returns TRUE on successful execution, FALSE on error
	 */
	public function execute() {
		$success = TRUE;
		$tables = $this->getTcaTables();
		foreach ($tables as $table) {
			if ($this->cleanTable($table) === FALSE) {
				$success = FALSE;
			}
		}

		return $success;
	}

	/**
	 * Executes the delete-query for the given table
	 *
	 * @param string $tableName
	 * @return boolean
	 */
	protected function cleanTable($tableName) {
		$queryParts = array();
		if (isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
			$queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] . ' = 1';
			if ($GLOBALS['TCA'][$tableName]['ctrl']['tstamp']) {
				$dateBefore = strtotime('-' . $this->getPeriod() . ' days');
				$queryParts[] = $GLOBALS['TCA'][$tableName]['ctrl']['tstamp'] . ' < ' . $dateBefore;
			}
			$where = implode(' AND ', $queryParts);

			$this->checkFileResourceFieldsBeforeDeletion($tableName, $where);

			$this->getDatabaseConnection()->exec_DELETEquery($tableName, $where);
		}

		$success = $this->getDatabaseConnection()->sql_error() === '' ? TRUE : FALSE;
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
			implode(', ', $this->getTcaTables())
		);

		$message .= '; ';

		$message .= sprintf(
			$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskDescriptionDays'),
			$this->getPeriod()
		);

		return $message;
	}

	/**
	 * Sets the period after which a row is deleted
	 *
	 * @param integer $period
	 */
	public function setPeriod($period) {
		$this->period = (int) $period;
	}

	/**
	 * Returns the period after which a row is deleted
	 *
	 * @return integer
	 */
	public function getPeriod() {
		return $this->period;
	}

	/**
	 * Sets the TCA-tables which are cleaned
	 *
	 * @param array $tcaTables
	 */
	public function setTcaTables($tcaTables = array()) {
		$this->tcaTables = $tcaTables;
	}

	/**
	 * Returns the TCA-tables which are cleaned
	 *
	 * @return array
	 */
	public function getTcaTables() {
		return $this->tcaTables;
	}

	/**
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	public function setDatabaseConnection($databaseConnection) {
		$this->databaseConnection = $databaseConnection;
	}

	/**
	 * Returns the DB-object
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	public function getDatabaseConnection() {
		if ($this->databaseConnection === NULL) {
			$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		}
		return $this->databaseConnection;
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
	 * @return void
	 */
	protected function deleteFilesForTable($table, $where, array $fieldList) {
		$rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
			implode(',', $fieldList),
			$table,
			$where
		);
		foreach ($rows as $row) {
			foreach ($fieldList as $fieldName) {
				$uploadDir = PATH_site . $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['uploadfolder'] . '/';
				$fileList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $row[$fieldName]);
				foreach ($fileList as $fileName) {
					@unlink($uploadDir . $fileName);
				}
			}
		}
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
				if ($fieldConfiguration['config']['type'] == 'group'
					&& $fieldConfiguration['config']['internal_type'] == 'file'
				) {
					$result[] = $fieldName;
					break;
				}
			}
		}
		return $result;
	}
}

?>