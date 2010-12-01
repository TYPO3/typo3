<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer Migration Task
 * Class "tx_fal_MigrationTask" provides a task to move, index and reference legacy files to  fal structure
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id: $
 */
class tx_fal_MigrationTask extends tx_scheduler_Task {

	/**
	 * Tables to work on
	 *
	 * @var	array
	 */
	public $tableToWorkOn = array();

	/**
	 * Handling only a limited amount of records in one recurrenc
	 *
	 * @var	integer
	 */
	public $limit = 500;

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return	boolean		DESCRIPTION
	 */
	public function execute() {
		$databaseFieldnameIterator = t3lib_div::makeInstance('tx_fal_DatabaseFieldnameIterator');
		if ($this->tableToWorkOn) {
			$databaseFieldnameIterator->limitTablesTo($this->tableToWorkOn);
		}

		$recordIterator = t3lib_div::makeInstance('tx_fal_RecordIterator');
		$recordIterator->setLimit($this->limit);

		$migrator = t3lib_div::makeInstance('tx_fal_MigrationController');
		$migrator
			->setFieldnameIterator($databaseFieldnameIterator)
			->setRecordIterator($recordIterator)
			->setLimit($this->limit)
			->execute();

		return true;
	}

	/**
	 * This method returns
	 *
	 * @return	string	Information to display	DESCRIPTION
	 */
	public function getAdditionalInformation() {
		return $GLOBALS['LANG']->sL('LLL:EXT:fal/locallang.xml:list.label.limit') . ': ' . $this->limit;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/tasks/class.tx_fal_migrationtask.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/tasks/class.tx_fal_migrationtask.php']);
}
?>