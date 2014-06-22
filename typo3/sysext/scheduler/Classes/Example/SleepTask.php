<?php
namespace TYPO3\CMS\Scheduler\Example;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Provides a task that sleeps for some time
 * This is useful for testing parallel executions
 *
 * @author François Suter <francois@typo3.org>
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
		return $GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xlf:label.sleepTime') . ': ' . $this->sleepTime;
	}

}
