<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Remove old entries from tables.
 *
 * This task deletes rows from tables older than the given number of days.
 *
 * Available tables must be registered in
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables']
 * See ext_localconf.php of scheduler extension for an example
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class TableGarbageCollectionTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * @var boolean True if all tables should be cleaned up
	 */
	public $allTables = FALSE;

	/**
	 * @var integer Number of days
	 */
	public $numberOfDays = 180;

	/**
	 * @var string Table to clean up
	 */
	public $table = '';

	/**
	 * Execute garbage collection, called by scheduler.
	 *
	 * @throws \RuntimeException if configured table was not cleaned up
	 * @return boolean TRUE if task run was successful
	 */
	public function execute() {
		$tableConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables'];
		$tableHandled = FALSE;
		foreach ($tableConfigurations as $tableName => $configuration) {
			if ($this->allTables || $tableName === $this->table) {
				$this->handleTable($tableName, $configuration);
				$tableHandled = TRUE;
			}
		}
		if (!$tableHandled) {
			throw new \RuntimeException('TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask misconfiguration: ' . $this->table . ' does not exist in configuration', 1308354399);
		}
		return TRUE;
	}

	/**
	 * Execute clean up of a specific table
	 *
	 * @throws \RuntimeException If table configuration is broken
	 * @param string $table The table to handle
	 * @param array $configuration Clean up configuration
	 * @return boolean TRUE if cleanup was successful
	 */
	protected function handleTable($table, array $configuration) {
		if (!empty($configuration['expireField'])) {
			$field = $configuration['expireField'];
			$dateLimit = $GLOBALS['EXEC_TIME'];
			// If expire field value is 0, do not delete
			// Expire field = 0 means no expiration
			$where = $field . ' <= \'' . $dateLimit . '\' AND ' . $field . ' > \'0\'';
		} elseif (!empty($configuration['dateField'])) {
			$field = $configuration['dateField'];
			if (!$this->allTables) {
				$deleteTimestamp = strtotime('-' . $this->numberOfDays . 'days');
			} else {
				if (!isset($configuration['expirePeriod'])) {
					throw new \RuntimeException('TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask misconfiguration: No expirePeriod defined for table ' . $table, 1308355095);
				}
				$deleteTimestamp = strtotime('-' . $configuration['expirePeriod'] . 'days');
			}
			$where = $configuration['dateField'] . ' < ' . $deleteTimestamp;
		} else {
			throw new \RuntimeException('TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask misconfiguration: Either expireField or dateField must be defined for table ' . $table, 1308355268);
		}
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
		$error = $GLOBALS['TYPO3_DB']->sql_error();
		if ($error) {
			throw new \RuntimeException('TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask failed for table ' . $this->table . ' with error: ' . $error, 1308255491);
		}
		return TRUE;
	}

	/**
	 * This method returns the selected table as additional information
	 *
	 * @return string Information to display
	 */
	public function getAdditionalInformation() {
		if ($this->allTables) {
			$message = $GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.additionalInformationAllTables');
		} else {
			$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.additionalInformationTable'), $this->table);
		}
		return $message;
	}

}


?>