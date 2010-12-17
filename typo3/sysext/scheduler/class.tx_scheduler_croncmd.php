<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Markus Friedrich (markus.friedrich@dkd.de)
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
 * This class provides calulations for the cron command format
 *
 * @author		Markus Friedrich <markus.friedrich@dkd.de>
 * @package		TYPO3
 * @subpackage	tx_scheduler
 *
 * $Id$
 */
class tx_scheduler_CronCmd {

	/**
	 * Sections of the cron command
	 *
	 *	field          allowed values
	 *	-----          --------------
	 *	minute         0-59
	 *	hour           0-23
	 *	day of month   1-31
	 *	month          1-12 (or names, see below)
	 *	day of week    0-7 (0 or 7 is Sun, or use names)
	 *
	 * @var	array		$cmd_sections
	 */
	public $cmd_sections;

	/**
	 * Valid values for each part
	 *
	 * @var	array		$valid_values
	 */
	public $valid_values;

	/**
	 * Array containing the values to build the new execution date
	 *
	 * 0	=>	minute
	 * 1	=>	hour
	 * 2	=>	day
	 * 3	=>	month
	 * 4	=>	year
	 *
	 * @var	array		$values
	 */
	public $values;

	/**
	 * Constructor
	 *
	 * @param	string		$cmd: the cron command
	 * @param	integer		$tstamp: optional start time
	 * @return	void
	 */
	public function __construct($cmd, $tstamp = FALSE) {
			// Explode cmd in sections
		$this->cmd_sections = t3lib_div::trimExplode(' ', $cmd);

			// Initialize the values with the starting time
			// This takes care that the calculated time is always in the future
		if ($tstamp === FALSE) {
			$tstamp = strtotime('+1 minute');
		}
		$this->values = array(
				// Minute
			intval(date('i', $tstamp)),
				// Hour
			intval(date('G', $tstamp)),
				// Day
			intval(date('j', $tstamp)),
				// Month
			intval(date('n', $tstamp)),
				// Year
			intval(date('Y', $tstamp))
		);

			// Set valid values
		$this->valid_values = array(
			$this->getList($this->cmd_sections[0], 0, 59),
			$this->getList($this->cmd_sections[1], 0, 23),
			$this->getDayList($this->values[3], $this->values[4]),
			$this->getList($this->cmd_sections[3], 1, 12),
			$this->getList('*', intval(date('Y', $tstamp)), intval(date('Y', $tstamp)) + 1)
		);
	}

	/**
	 * Calulates the next execution
	 *
	 * @param	integer		$level: number of the current level, e.g. 2 is the day level
	 * @return	void
	 */
	public function calculateNextValue($level) {
		if (isset($this->values[$level])) {
			$current_value = &$this->values[$level];
			$next_level = $level + 1;

			if (in_array($current_value, $this->valid_values[$level])) {
				$this->calculateNextValue($next_level);
			} else {
				$next_value = $this->getNextValue($this->values[$level], $this->valid_values[$level]);
				if ($next_value === false) {
						// Set this value and prior values to the start value
					for ($i = $level; $i >= 0; $i--) {
						$this->values[$i] = $this->valid_values[$i][0];

							// Update day list if month was changed
						if ($i == 3) {
							$this->valid_values[2] = $this->getDayList($this->values[3], $this->values[4]);
						}
					}

						// Calculate next value for the next value
					for ($i = $next_level; $i <= count($this->values); $i++) {
						if (isset($this->values[$i])) {
							$increased_value = $this->getNextValue($this->values[$i], $this->valid_values[$i]);

							if ($increased_value !== false) {
								$this->values[$i] = $increased_value;

									// Update day list if month or year was changed
								if ($i >= 3) {
									$this->valid_values[2] = $this->getDayList($this->values[3], $this->values[4]);

										// Check if day had already a valid start value, if not set a new one
									if (!$this->values[2] || !in_array($this->values[2], $this->valid_values[2])) {
										$this->values[2] = $this->valid_values[2][0];
									}
								}

								break;
							} else {
								$this->values[$i] = $this->valid_values[$i][0];
							}
						}
					}

					$this->calculateNextValue($next_level);
				} else {
					if ($level == 3) {
							// Update day list if month was changed
						$this->valid_values[2] = $this->getDayList($this->values[3], $this->values[4]);
					}

					$current_value = $next_value;
					$this->calculateNextValue($next_level);
				}
			}
		}
	}

	/**
	 * Builds a list of days for a certain month
	 *
	 * @param	integer		$currentMonth: number of a month
	 * @param	integer		$currentYear: a year
	 * @return	array		list of days
	 */
	protected function getDayList($currentMonth, $currentYear) {
			// Create a dummy timestamp at 6:00 of the first day of the current month and year
			// to get the number of days in the month using date()
		$dummyTimestamp = mktime(6, 0, 0, $currentMonth, 1, $currentYear);
		$max_days = date('t', $dummyTimestamp);
		$validDays = $this->getList($this->cmd_sections[2], 1, $max_days);

			// Consider special field 'day of week'
			// @TODO: Implement lists and ranges for day of week (2,3 and 1-5 and */2,3 and 1,1-5/2)
			// @TODO: Support usage of day names in day of week field ("mon", "sun", etc.)
		if ((strpos($this->cmd_sections[4], '*') === FALSE && preg_match('/[0-7]{1}/', $this->cmd_sections[4]) !== FALSE)) {
				// Unset days from 'day of month' if * is given
				// * * * * 1 results to every monday of this month
				// * * 1,2 * 1 results to every monday, plus first and second day of month
			if ($this->cmd_sections[2] == '*') {
				$validDays = array();
			}

				// Allow 0 as representation for sunday and convert to 7
			$dayOfWeek = $this->cmd_sections[4];
			if ($dayOfWeek === '0') {
				$dayOfWeek = '7';
			}

				// Get list
			for ($i = 1; $i <= $max_days; $i++) {
				if (strftime('%u', mktime(0, 0, 0, $currentMonth, $i, $currentYear)) == $dayOfWeek) {
					if (!in_array($i, $validDays)) {
						$validDays[] = $i;
					}
				}
			}
		}
		sort($validDays);

		return $validDays;
	}

	/**
	 * Builds a list of possible values from a cron command.
	 *
	 * @param	string		$definition: the command e.g. "2-8,14,0-59/20"
	 * @param	integer		$min: minimum allowed value, greater or equal zero
	 * @param	integer		$max: maximum allowed value, greater than $min
	 * @return	array		list with possible values
	 */
	protected function getList($definition, $min, $max) {
		$possibleValues = array();

		$listParts = t3lib_div::trimExplode(',', $definition, TRUE);
		foreach ($listParts as $part) {
			$possibleValues = array_merge($possibleValues, $this->getListPart($part, $min, $max));
		}

		sort($possibleValues);
		return $possibleValues;
	}

	/**
	 * Builds a list of possible values from a single part of a cron command.
	 * Parses asterisk (*), ranges (2-4) and steps (2-10/2).
	 *
	 * @param	string		$definition: a command part e.g. "2-8", "*", "0-59/20"
	 * @param	integer		$min: minimum allowed value, greater or equal zero
	 * @param	integer		$max: maximum allowed value, greater than $min
	 * @return	array		list with possible values or empty array
	 */
	protected function getListPart($definition, $min, $max) {
		$possibleValues = array();

		if ($definition == '*') {
				// Get list for the asterisk
			for ($value = $min; $value <= $max; $value++) {
				$possibleValues[] = $value;
			}
		} else if (strpos($definition, '/') !== false) {
				// Get list for step values
			list($listPart, $stepPart) = t3lib_div::trimExplode('/', $definition);
			$tempList = $this->getListPart($listPart, $min, $max);
			foreach ($tempList as $tempListValue) {
				if ($tempListValue % $stepPart == 0) {
					$possibleValues[] = $tempListValue;
				}
			}
		} else if (strpos($definition, '-') !== false) {
				// Get list for range definitions
				// Get list definition parts
			list($minValue, $maxValue) = t3lib_div::trimExplode('-', $definition);
			if ($minValue < $min) {
				$minValue = $min;
			}
			if ($maxValue > $max) {
				$maxValue = $max;
			}
			$possibleValues = $this->getListPart('*', $minValue, $maxValue);
		} else if (is_numeric($definition) && $definition >= $min && $definition <= $max) {
				// Get list for single values
			$possibleValues[] = intval($definition);
		}

		sort($possibleValues);
		return $possibleValues;
	}

	/**
	 * Returns the first value that is higher than the current value
	 * from a list of possible values
	 *
	 * @param	mixed	$currentValue: the value to be searched in the list
	 * @param	array	$listArray: the list of values
	 * @return	mixed	The value from the list right after the current value
	 */
	public function getNextValue($currentValue, array $listArray) {
		$next_value = false;

		$numValues = count($listArray);
		for ($i = 0; $i < $numValues; $i++) {
			if ($listArray[$i] > $currentValue) {
				$next_value = $listArray[$i];
				break;
			}
		}

		return $next_value;
	}

	/**
	 * Returns the timestamp for the value parts in $this->values
	 *
	 * @return	integer		unix timestamp
	 */
	public function getTstamp() {
		return mktime($this->values[1], $this->values[0], 0, $this->values[3], $this->values[2], $this->values[4]);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/scheduler/class.tx_scheduler_croncmd.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/scheduler/class.tx_scheduler_croncmd.php']);
}

?>