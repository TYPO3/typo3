<?php
namespace TYPO3\CMS\Scheduler\CronCommand;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Markus Friedrich (markus.friedrich@dkd.de)
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
 * This class provides calulations for the cron command format.
 *
 * @author Markus Friedrich <markus.friedrich@dkd.de>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class CronCommand {

	/**
	 * Normalized sections of the cron command.
	 * Allowed are comma separated lists of integers and the character '*'
	 *
	 * field          lower and upper bound
	 * -----          --------------
	 * minute         0-59
	 * hour           0-23
	 * day of month   1-31
	 * month          1-12
	 * day of week    1-7
	 *
	 * @var array $cronCommandSections
	 */
	protected $cronCommandSections;

	/**
	 * Timestamp of next execution date.
	 * This value starts with 'now + 1 minute' if not set externally
	 * by unit tests. After a call to calculateNextValue() it holds the timestamp of
	 * the next execution date which matches the cron command restrictions.
	 */
	protected $timestamp;

	/**
	 * Constructor
	 *
	 * @api
	 * @param string $cronCommand The cron command can hold any combination documented as valid
	 * @param bool|int $timestamp Optional start time, used in unit tests
	 * @return \TYPO3\CMS\Scheduler\CronCommand\CronCommand
	 */
	public function __construct($cronCommand, $timestamp = FALSE) {
		$cronCommand = \TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand::normalize($cronCommand);
		// Explode cron command to sections
		$this->cronCommandSections = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $cronCommand);
		// Initialize the values with the starting time
		// This takes care that the calculated time is always in the future
		if ($timestamp === FALSE) {
			$timestamp = strtotime('+1 minute');
		} else {
			$timestamp += 60;
		}
		$this->timestamp = $this->roundTimestamp($timestamp);
	}

	/**
	 * Calculates the date of the next execution.
	 *
	 * @api
	 * @return void
	 */
	public function calculateNextValue() {
		$newTimestamp = $this->getTimestamp();
		// Calculate next minute and hour field
		$loopCount = 0;
		while (TRUE) {
			$loopCount++;
			// If there was no match within two days, cron command is invalid.
			// The second day is needed to catch the summertime leap in some countries.
			if ($loopCount > 2880) {
				throw new \RuntimeException('Unable to determine next execution timestamp: Hour and minute combination is invalid.', 1291494126);
			}
			if ($this->minuteAndHourMatchesCronCommand($newTimestamp)) {
				break;
			}
			$newTimestamp += 60;
		}
		$loopCount = 0;
		while (TRUE) {
			$loopCount++;
			// A date must match within the next 4 years, this high number makes
			// sure leap year cron command configuration are caught.
			// If the loop runs longer than that, the cron command is invalid.
			if ($loopCount > 1464) {
				throw new \RuntimeException('Unable to determine next execution timestamp: Day of month, month and day of week combination is invalid.', 1291501280);
			}
			if ($this->dayMatchesCronCommand($newTimestamp)) {
				break;
			}
			$newTimestamp += $this->numberOfSecondsInDay($newTimestamp);
		}
		$this->timestamp = $newTimestamp;
	}

	/*
	 * Get next timestamp
	 *
	 * @api
	 * @return integer Unix timestamp
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Get cron command sections. Array of strings, each containing either
	 * a list of comma separated integers or *
	 *
	 * @return array command sections:
	 */
	public function getCronCommandSections() {
		return $this->cronCommandSections;
	}

	/**
	 * Determine if current timestamp matches minute and hour cron command restriction.
	 *
	 * @param integer $timestamp to test
	 * @return boolean TRUE if cron command conditions are met
	 */
	protected function minuteAndHourMatchesCronCommand($timestamp) {
		$minute = intval(date('i', $timestamp));
		$hour = intval(date('G', $timestamp));
		$commandMatch = FALSE;
		if ($this->isInCommandList($this->cronCommandSections[0], $minute) && $this->isInCommandList($this->cronCommandSections[1], $hour)) {
			$commandMatch = TRUE;
		}
		return $commandMatch;
	}

	/**
	 * Determine if current timestamp matches day of month, month and day of week
	 * cron command restriction
	 *
	 * @param integer $timestamp to test
	 * @return boolean TRUE if cron command conditions are met
	 */
	protected function dayMatchesCronCommand($timestamp) {
		$dayOfMonth = date('j', $timestamp);
		$month = date('n', $timestamp);
		$dayOfWeek = date('N', $timestamp);
		$isInDayOfMonth = $this->isInCommandList($this->cronCommandSections[2], $dayOfMonth);
		$isInMonth = $this->isInCommandList($this->cronCommandSections[3], $month);
		$isInDayOfWeek = $this->isInCommandList($this->cronCommandSections[4], $dayOfWeek);
		// Quote from vixiecron:
		// Note: The day of a command's execution can be specified by two fields — day of month, and day of week.
		// If both fields are restricted (i.e., aren't  *),  the  command will be run when either field
		// matches the current time.  For example, `30 4 1,15 * 5' would cause
		// a command to be run at 4:30 am on the 1st and 15th of each month, plus every Friday.
		$isDayOfMonthRestricted = (string) $this->cronCommandSections[2] === '*' ? FALSE : TRUE;
		$isDayOfWeekRestricted = (string) $this->cronCommandSections[4] === '*' ? FALSE : TRUE;
		$commandMatch = FALSE;
		if ($isInMonth) {
			if ($isInDayOfMonth && $isDayOfMonthRestricted || $isInDayOfWeek && $isDayOfWeekRestricted || $isInDayOfMonth && !$isDayOfMonthRestricted && $isInDayOfWeek && !$isDayOfWeekRestricted) {
				$commandMatch = TRUE;
			}
		}
		return $commandMatch;
	}

	/**
	 * Determine if a given number validates a cron command section. The given cron
	 * command must be a 'normalized' list with only comma separated integers or '*'
	 *
	 * @param string $commandExpression: cron command
	 * @param integer $numberToMatch: number to look up
	 * @return boolean TRUE if number is in list
	 */
	protected function isInCommandList($commandExpression, $numberToMatch) {
		$inList = FALSE;
		if ((string) $commandExpression === '*') {
			$inList = TRUE;
		} else {
			$inList = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($commandExpression, $numberToMatch);
		}
		return $inList;
	}

	/**
	 * Helper method to calculate number of seconds in a day.
	 *
	 * This is not always 86400 (60*60*24) and depends on the timezone:
	 * Some countries like Germany have a summertime / wintertime switch,
	 * on every last sunday in march clocks are forwarded by one hour (set from 2:00 to 3:00),
	 * and on last sunday of october they are set back one hour (from 3:00 to 2:00).
	 * This shortens and lengthens the length of a day by one hour.
	 *
	 * @param integer $timestamp Unix timestamp
	 * @return integer Number of seconds of day
	 */
	protected function numberOfSecondsInDay($timestamp) {
		$now = mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
		// Make sure to be in next day, even if day has 25 hours
		$nextDay = $now + 60 * 60 * 25;
		$nextDay = mktime(0, 0, 0, date('n', $nextDay), date('j', $nextDay), date('Y', $nextDay));
		return $nextDay - $now;
	}

	/**
	 * Round a timestamp down to full minute.
	 *
	 * @param integer $timestamp Unix timestamp
	 * @return integer Rounded timestamp
	 */
	protected function roundTimestamp($timestamp) {
		return mktime(date('H', $timestamp), date('i', $timestamp), 0, date('n', $timestamp), date('j', $timestamp), date('Y', $timestamp));
	}

}


?>