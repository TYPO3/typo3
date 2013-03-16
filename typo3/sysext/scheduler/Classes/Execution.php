<?php
namespace TYPO3\CMS\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Christian Jul Jensen (julle@typo3.org)
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
 * This class manages the logic of a particular execution of a task
 *
 * @author 	François Suter <francois@typo3.org>
 * @author 	Christian Jul Jensen <julle@typo3.org>
 * @author 	Markus Friedrich <markus.friedrich@dkd.de>
 */
class Execution {

	/**
	 * Start date of a task (timestamp)
	 *
	 * @var 	integer	$start
	 */
	protected $start;

	/**
	 * End date of a task (timestamp)
	 *
	 * @var 	integer	$end
	 */
	protected $end;

	/**
	 * Interval between executions (in seconds)
	 *
	 * @var 	integer	$interval
	 */
	protected $interval;

	/**
	 * Flag for concurrent executions: TRUE if allowed, FALSE otherwise (default)
	 *
	 * @var 	boolean	$multiple
	 */
	protected $multiple = FALSE;

	/**
	 * The cron command string of this task,
	 *
	 * @var 	string		$cronCmd
	 */
	protected $cronCmd;

	/**
	 * This flag is used to mark a new single execution
	 * See explanations in method setIsNewSingleExecution()
	 *
	 * @var 	boolean		$isNewSingleExecution
	 * @see 	tx_scheduler_Execution::setIsNewSingleExecution()
	 */
	protected $isNewSingleExecution = FALSE;

	/**********************************
	 * Setters and getters
	 **********************************/
	/**
	 * This method is used to set the start date
	 *
	 * @param integer $start Start date (timestamp)
	 * @return void
	 */
	public function setStart($start) {
		$this->start = $start;
	}

	/**
	 * This method is used to get the start date
	 *
	 * @return integer Start date (timestamp)
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * This method is used to set the end date
	 *
	 * @param integer $end End date (timestamp)
	 * @return void
	 */
	public function setEnd($end) {
		$this->end = $end;
	}

	/**
	 * This method is used to get the end date
	 *
	 * @return integer End date (timestamp)
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * This method is used to set the interval
	 *
	 * @param integer $interval Interval (in seconds)
	 * @return void
	 */
	public function setInterval($interval) {
		$this->interval = $interval;
	}

	/**
	 * This method is used to get the interval
	 *
	 * @return integer Interval (in seconds)
	 */
	public function getInterval() {
		return $this->interval;
	}

	/**
	 * This method is used to set the multiple execution flag
	 *
	 * @param boolean $multiple TRUE if concurrent executions are allowed, FALSE otherwise
	 * @return void
	 */
	public function setMultiple($multiple) {
		$this->multiple = $multiple;
	}

	/**
	 * This method is used to get the multiple execution flag
	 *
	 * @return boolean TRUE if concurrent executions are allowed, FALSE otherwise
	 */
	public function getMultiple() {
		return $this->multiple;
	}

	/**
	 * Set the value of the cron command
	 *
	 * @param string $cmd Cron command, using cron-like syntax
	 * @return void
	 */
	public function setCronCmd($cmd) {
		$this->cronCmd = $cmd;
	}

	/**
	 * Get the value of the cron command
	 *
	 * @return string Cron command, using cron-like syntax
	 */
	public function getCronCmd() {
		return $this->cronCmd;
	}

	/**
	 * Set whether this is a newly created single execution.
	 * This is necessary for the following reason: if a new single-running task
	 * is created and its start date is in the past (even for only a few seconds),
	 * the next run time calculation (which happens upon saving) will disable
	 * that task, because it was meant to run only once and is in the past.
	 * Setting this flag to TRUE preserves this task for a single run.
	 * Upon next execution, this flag is set to FALSE.
	 *
	 * @param boolean $isNewSingleExecution Is newly created single execution?
	 * @return 	void
	 * @see tx_scheduler_Execution::getNextExecution()
	 */
	public function setIsNewSingleExecution($isNewSingleExecution) {
		$this->isNewSingleExecution = $isNewSingleExecution;
	}

	/**
	 * Get whether this is a newly created single execution
	 *
	 * @return boolean Is newly created single execution?
	 */
	public function getIsNewSingleExecution() {
		return $this->isNewSingleExecution;
	}

	/**********************************
	 * Execution calculations and logic
	 **********************************/
	/**
	 * This method gets or calculates the next execution date
	 *
	 * @return integer Timestamp of the next execution
	 * @throws \OutOfBoundsException
	 */
	public function getNextExecution() {
		if ($this->getIsNewSingleExecution()) {
			$this->setIsNewSingleExecution(FALSE);
			return $this->start;
		}
		if (!$this->isEnded()) {
			// If the schedule has not yet run out, find out the next date
			if (!$this->isStarted()) {
				// If the schedule hasn't started yet, next date is start date
				$date = $this->start;
			} else {
				// If the schedule has already started, calculate next date
				if ($this->cronCmd) {
					// If it uses cron-like syntax, calculate next date
					$date = $this->getNextCronExecution();
				} elseif ($this->interval == 0) {
					// If not and there's no interval either, it's a singe execution: use start date
					$date = $this->start;
				} else {
					// Otherwise calculate date based on interval
					$now = time();
					$date = $now + $this->interval - ($now - $this->start) % $this->interval;
				}
				// If date is in the future, throw an exception
				if (!empty($this->end) && $date > $this->end) {
					throw new \OutOfBoundsException('Next execution date is past end date.', 1250715528);
				}
			}
		} else {
			// The event has ended, throw an exception
			throw new \OutOfBoundsException('Task is past end date.', 1250715544);
		}
		return $date;
	}

	/**
	 * Calculates the next execution from a cron command
	 *
	 * @return integer Next execution (timestamp)
	 */
	public function getNextCronExecution() {
		/** @var $cronCmd \TYPO3\CMS\Scheduler\CronCommand\CronCommand */
		$cronCmd = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\CronCommand\\CronCommand', $this->getCronCmd());
		$cronCmd->calculateNextValue();
		return $cronCmd->getTimestamp();
	}

	/**
	 * Checks if the schedule for a task is started or not
	 *
	 * @return boolean TRUE if the schedule is already active, FALSE otherwise
	 */
	public function isStarted() {
		return $this->start < time();
	}

	/**
	 * Checks if the schedule for a task is passed or not
	 *
	 * @return boolean TRUE if the schedule is not active anymore, FALSE otherwise
	 */
	public function isEnded() {
		if (empty($this->end)) {
			// If no end is defined, the schedule never ends
			$result = FALSE;
		} else {
			// Otherwise check if end is in the past
			$result = $this->end < time();
		}
		return $result;
	}

}


?>