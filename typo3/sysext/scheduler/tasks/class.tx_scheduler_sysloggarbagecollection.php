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
 * Garbage collection of sys_log table.
 *
 * This task deletes log entries from sys_log table older than
 * the number of days given in additional field.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage scheduler
 */
class tx_scheduler_SysLogGarbageCollection extends tx_scheduler_Task {

	/**
	 * @var integer Number of days
	 */
	public $numberOfDays = 180;

	/**
	 * Execute garbage collection, called by scheduler.
	 *
	 * @return void
	 */
	public function execute() {
		$deleteTimestamp = strtotime('-' . $this->numberOfDays . 'days');

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'sys_log',
			'tstamp < ' . $deleteTimestamp
		);

		return TRUE;
	}
} // End of class

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_sysloggarbagecollection.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_sysloggarbagecollection.php']);
}

?>