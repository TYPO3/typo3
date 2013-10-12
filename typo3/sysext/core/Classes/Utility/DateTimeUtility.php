<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Alexander Opitz <opitz.alexander@googlemail.com>
 *  (c) 2014 Markus Klein <klein.t3@mfc-linz.at>
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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class has functions for handling date and time. Especially differences between two dates as readable string.
 *
 * @author Alexander Opitz <opitz@pluspol.info>
 * @author Markus Klein <klein.t3@mfc-linz.at>
 */
class DateTimeUtility {

	/**
	 * @var int for usage with the getSimpleAgeString or round function
	 */
	const CEIL = 10;

	/**
	 * @var int for usage with the getSimpleAgeString or round function
	 */
	const FLOOR = 11;

	/**
	 * Returns the given microtime as milliseconds.
	 *
	 * @param string $microtime Microtime as "msec sec" string given by php function microtime
	 * @return int Microtime input string converted to an int (milliseconds)
	 */
	static public function convertMicrotime($microtime) {
		$parts = explode(' ', $microtime);
		return (int)round(($parts[0] + $parts[1]) * 1000);
	}

	/**
	 * Gets the unixtime as milliseconds.
	 *
	 * @return int The unixtime as milliseconds
	 */
	static public function milliseconds() {
		return (int)round(microtime(TRUE) * 1000);
	}

	/**
	 * Returns a string representation of the difference between timestamps in minutes / hours / days / months / years
	 * with a given label.
	 *
	 * @param int $startTime Unix timestamp for calculating difference of
	 * @param int $endTime Unix timestamp for calculating difference to
	 * @param string|array|NULL $labels Labels should be something like ' mins| hrs| days| months| yrs| min| hour| day| month| year'
	 *  This value is typically delivered by this function call:
	 *  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears')
	 *  Or using the array returned by splitTimeUnitsFromLabel()
	 * @return string Formatted time difference
	 */
	static public function getTimeDiffStringUnix($startTime, $endTime, $labels = NULL) {
		return static::getTimeDiffString(
			new \DateTime('@' . $startTime),
			new \DateTime('@' . $endTime),
			$labels
		);
	}

	/**
	 * Returns a string representation of the difference between DateTimes in minutes / hours / days / months / years
	 * with a given label.
	 *
	 * @param \DateTime $startDateTime Unix timestamp for calculating difference of
	 * @param \DateTime $endDateTime Unix timestamp for calculating difference to
	 * @param string|array|NULL $labels Labels should be something like ' mins| hrs| days| months| yrs| min| hour| day| month| year'
	 *  This value is typically delivered by this function call:
	 *  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears')
	 *  Or using the array returned by splitTimeUnitsFromLabel()
	 * @return string Formatted time difference
	 */
	static public function getTimeDiffString(\DateTime $startDateTime, \DateTime $endDateTime, $labels = NULL) {
		if (!is_array($labels) || empty($labels)) {
			$labels = static::splitTimeUnitsFromLabel($labels);
		}

		$dateDiff = $startDateTime->diff($endDateTime);

		if ($dateDiff->y > 0) {
			$value = $dateDiff->y;
			$label =  'year';
		} elseif ($dateDiff->m > 0 && isset($labels['months'])) {
			$value = $dateDiff->m;
			$label =  'month';
		} elseif ($dateDiff->days > 0) {
			$value = $dateDiff->days;
			$label =  'day';
		} elseif ($dateDiff->h > 0) {
			$value = $dateDiff->h;
			$label =  'hour';
		} else {
			$value = $dateDiff->i;
			$label =  'min';
		}

		// Get real label depending on singular/plural
		$label = $labels[$label . ($value === 1 ? '' : 's')];

		if ($dateDiff->invert === 1) {
			$value *= -1;
		}

		return $value . $label;
	}

	/**
	 * Converts the old plural, old singular/plural and the new singular/plural pipe split string
	 * into an array with known unit names as keys.
	 *
	 * @param string|NULL $labels Labels should be something like ' min| hrs| days| months| yrs| min| hour| day| month| year'
	 *  This value is typically delivered by this function call:
	 *  $GLOBALS['LANG']->sL("LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears")
	 * @return array String split array
	 */
	static public function splitTimeUnitsFromLabel($labels = NULL) {
		if (NULL === $labels) {
			$lang = self::getLanguageService();
			$tsfe = self::getTypoScriptFrontendController();
			if ($lang) {
				$labels = $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears');
			} elseif ($tsfe) {
				$labels = $tsfe->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears');
			}
			// Couldn't determine from local config so take default value.
			if (empty($labels)) {
				$labels = ' min| hrs| days| months| yrs| min| hour| day| month| year';
			}
		} else {
			$labels = str_replace('"', '', $labels);
		}

		$labelArr = explode('|', $labels);
		$resultArr = array();

		if (count($labelArr) === 4) {
			// Old plural labels string, add plural as singular
			$labelArr = array_merge($labelArr, $labelArr);
		}

		switch (count($labelArr)) {
			case 8:
				// Old singular and plural labels string
				$resultArr['min'] = $labelArr[4];
				$resultArr['mins'] = $labelArr[0];
				$resultArr['hour'] = $labelArr[5];
				$resultArr['hours'] = $labelArr[1];
				$resultArr['day'] = $labelArr[6];
				$resultArr['days'] = $labelArr[2];
				$resultArr['year'] = $labelArr[7];
				$resultArr['years'] = $labelArr[3];
				break;
			case 10:
				// New singular and plural labels string (with month)
				$resultArr['min'] = $labelArr[5];
				$resultArr['mins'] = $labelArr[0];
				$resultArr['hour'] = $labelArr[6];
				$resultArr['hours'] = $labelArr[1];
				$resultArr['day'] = $labelArr[7];
				$resultArr['days'] = $labelArr[2];
				$resultArr['month'] = $labelArr[8];
				$resultArr['months'] = $labelArr[3];
				$resultArr['year'] = $labelArr[9];
				$resultArr['years'] = $labelArr[4];
				break;
			default:
				static::splitTimeUnitsFromLabel(NULL);
		}

		return $resultArr;
	}

	/**
	 * Returns the "age" in minutes / hours / days / months / years depending of the number of $seconds inputted.
	 *
	 * @param int $seconds Seconds could be the difference of a certain timestamp and time()
	 * @param string|array|NULL $labels Labels should be something like ' min| hrs| days| months| yrs| min| hour| day| month| year'
	 *  This value is typically delivered by this function call:
	 *  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears')
	 *  Or using the array returned by splitTimeUnitsFromLabel()
	 * @param int $method Method to use to round the result (PHP_ROUND_HALF_UP, PHP_ROUND_HALF_DOWN,
	 *  PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD - round, 10 - ceil, 11 - floor)
	 * @return string Formatted time
	 */
	static public function getSimpleAgeString($seconds, $labels = NULL, $method = PHP_ROUND_HALF_UP) {
		if (!is_array($labels) || empty($labels)) {
			$labels = static::splitTimeUnitsFromLabel($labels);
		}

		$sign = $seconds < 0 ? -1 : 1;
		$seconds = abs($seconds);
		if ($seconds < 3600) {
			$roundedResult = static::round($seconds / 60, $method);
			$seconds = $sign * $roundedResult . ($roundedResult == 1 ? $labels['mins'] : $labels['min']);
		} elseif ($seconds < 24 * 3600) {
			$roundedResult = static::round($seconds / 3600, $method);
			$seconds = $sign * $roundedResult . ($roundedResult == 1 ? $labels['hour'] : $labels['hours']);
		} elseif ($seconds < 30 * 24 * 3600 || (!isset($labels['month']) && $seconds < 365 * 24 * 3600)) {
			$roundedResult = static::round($seconds / (24 * 3600), $method);
			$seconds = $sign * $roundedResult . ($roundedResult == 1 ? $labels['day'] : $labels['days']);
		} elseif (isset($labels['month']) && $seconds < 365 * 24 * 3600) {
			$roundedResult = static::round($seconds / (30 * 24 * 3600), $method);
			$seconds = $sign * $roundedResult . ($roundedResult == 1 ? $labels['month'] : $labels['months']);
		} else {
			$roundedResult = static::round($seconds / (365 * 24 * 3600), $method);
			$seconds = $sign * $roundedResult . ($roundedResult == 1 ? $labels['year'] : $labels['years']);
		}

		return $seconds;
	}

	/**
	 * Returns a string representation of the age of a timestamps in minutes / hours / days / months / years
	 * with a given label. $GLOBALS['EXEC_TIME'] is taken as "now".
	 *
	 * @param int $time Unix timestamp for calculating age of
	 * @param string|array|NULL $labels Labels should be something like ' min| hrs| days| months| yrs| min| hour| day| month| year'
	 *  This value is typically delivered by this function call:
	 *  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears')
     *  Or using the array returned by splitTimeUnitsFromLabel()
	 * @return string Formatted time age
	 */
	static public function getAgeStringUnix($time, $labels = NULL) {
		return static::getTimeDiffStringUnix($time, $GLOBALS['EXEC_TIME'], $labels);
	}

	/**
	 * Returns a string representation of the age of a DateTime in minutes / hours / days / months / years
	 * with a given label. $GLOBALS['EXEC_TIME'] is taken as "now".
	 *
	 * @param \DateTime|$time DateTime for calculating age of
	 * @param string|array|NULL $labels Labels should be something like ' min| hrs| days| months| yrs| min| hour| day| month| year'
	 *  This value is typically delivered by this function call:
	 *  $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysMonthsYears')
	 *  Or using the array returned by splitTimeUnitsFromLabel()
	 * @return string Formatted time age
	 */
	static public function getAgeString(\DateTime $time, $labels = NULL) {
		return static::getTimeDiffString(
			$time,
			new \DateTime('@' . $GLOBALS['EXEC_TIME']),
			$labels
		);
	}

	/**
	 * Rounds the $value in the mathematical way of the choosen method.
	 *
	 * @param mixed $value The value to round
	 * @param int $method Method to use to round the result (PHP_ROUND_HALF_UP, PHP_ROUND_HALF_DOWN,
	 *  PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD, DateTimeUtility::CEIL, DateTimeUtility::FLOOR)
	 * @return int
	 */
	static public function round($value, $method = PHP_ROUND_HALF_UP) {
		switch ($method) {
			case DateTimeUtility::CEIL:
				$value = ceil($value);
				break;

			case DateTimeUtility::FLOOR:
				$value = floor($value);
				break;

			default:
				$value = round($value, 0, $method);
		}

		return (int)$value;
	}

	/**
	 * @return LanguageService|NULL
	 */
	static protected function getLanguageService() {
		return isset($GLOBALS['LANG']) ? $GLOBALS['LANG'] : NULL;
	}

	/**
	 * @return TypoScriptFrontendController|NULL
	 */
	static protected function getTypoScriptFrontendController() {
		return isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
	}
}
