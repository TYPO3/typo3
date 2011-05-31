<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Andy Grunwald <andreas.grunwald@wmdb.de>
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
 * Class "tx_scheduler_SaveTaskObjectStateTask" provides a task that shows the option
 * to save the task object state after execution.
 *
 * @author		Andy Grunwald <andreas.grunwald@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_scheduler
 */
class tx_scheduler_SaveTaskObjectStateTask extends tx_scheduler_Task {

	/**
	 * A generic string which will be saved after execution
	 *
	 * @var	string		$savedString
	 */
	 protected $savedString = 'Not executed';

	/**
	 * Method executed from the Scheduler.
	 * Generate a random string :)
	 *
	 * @return	void
	 */
	public function execute() {
			// Enable the option to save the object state after execution
		$this->enableSavingTheTaskObjectState();

			// Generate a random string
		$this->savedString = uniqid(time(), TRUE);

		return TRUE;
	}

	/**
	 * This method returns the generic string as additional information
	 *
	 * @return	string	Information to display
	 */
	public function getAdditionalInformation() {
		return $GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:label.savedString') . ': ' . $this->savedString;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/examples/class.tx_scheduler_savetaskobjectstatetask.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/examples/class.tx_scheduler_savetaskobjectstatetask.php']);
}

?>