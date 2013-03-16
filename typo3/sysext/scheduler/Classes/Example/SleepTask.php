<?php
namespace TYPO3\CMS\Scheduler\Example;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 François Suter <francois@typo3.org>
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
 * Class "tx_scheduler_SleepTask" provides a task that sleeps for some time
 * This is useful for testing parallel executions
 *
 * @author 		François Suter <francois@typo3.org>
 */
class SleepTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Number of seconds the task should be sleeping for
	 *
	 * @var integer $sleepTime
	 */
	public $sleepTime = 10;

	/**
	 * Function executed from the Scheduler.
	 * Goes to sleep ;-)
	 *
	 * @return boolean
	 */
	public function execute() {
		$time = 10;
		if (!empty($this->sleepTime)) {
			$time = $this->sleepTime;
		}
		sleep($time);
		return TRUE;
	}

	/**
	 * This method returns the sleep duration as additional information
	 *
	 * @return string Information to display
	 */
	public function getAdditionalInformation() {
		return $GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:label.sleepTime') . ': ' . $this->sleepTime;
	}

}


?>