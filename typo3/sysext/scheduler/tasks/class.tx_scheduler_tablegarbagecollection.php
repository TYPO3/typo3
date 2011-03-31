<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Garbage collection of tables.
 *
 * This task deletes rows from tables older than the given number of days.
 * Available tables are given by
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['table']
 * with key as table name and value as timestamp field
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage scheduler
 */
class tx_scheduler_TableGarbageCollection extends tx_scheduler_Task {

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
	 * @return void
	 */
	public function execute() {
		$deleteTimestamp = strtotime('-' . $this->numberOfDays . 'days');

			// Sanitize table and determine according timestamp field
		$tableConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'];
		foreach ($tableConfigurations as $number => $configuration) {
			if (isset($configuration['table']) && $configuration['table'] === $this->table) {
				if (!isset($configuration['timestampField'])) {
					throw new Exception(
						'tx_scheduler_TableGarbageCollection misconfiguration: No timestampField defined for table ' . $configuration['table'],
						1308170146
					);
				}
				$timestampField = $configuration['timestampField'];
				break;
			}
		}
		if (!isset($timestampField)) {
			throw new Exception(
				'tx_scheduler_TableGarbageCollection misconfiguration: No table configuration found for table ' . $this->table,
				1308170346
			);
		}

		$GLOBALS['TYPO3_DB']->DELETEquery(
			$this->table,
			$timestampField . ' < ' . $deleteTimestamp
		);

		return TRUE;
	}

	/**
	 * This method returns the selected table as additional information
	 *
	 * @return string Information to display
	 */
	public function getAdditionalInformation() {
		return $GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.additionalInformationTable') . ': ' . $this->table;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_tablegarbagecollection.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_tablegarbagecollection.php']);
}

?>