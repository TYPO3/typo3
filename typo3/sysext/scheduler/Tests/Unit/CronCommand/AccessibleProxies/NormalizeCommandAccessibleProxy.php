<?php
namespace TYPO3\CMS\Scheduler\Tests\Unit\CronCommand\AccessibleProxies;

/*
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

use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;

/**
 * Accessible proxy with protected methods made public.
 */
class NormalizeCommandAccessibleProxy extends NormalizeCommand {

	static public function convertKeywordsToCronCommand($cronCommand) {
		return parent::convertKeywordsToCronCommand($cronCommand);
	}

	static public function normalizeFields($cronCommand) {
		return parent::normalizeFields($cronCommand);
	}

	static public function normalizeMonthAndWeekdayField($expression, $isMonthField = TRUE) {
		return parent::normalizeMonthAndWeekdayField($expression, $isMonthField);
	}

	static public function normalizeIntegerField($expression, $lowerBound = 0, $upperBound = 59) {
		return parent::normalizeIntegerField($expression, $lowerBound, $upperBound);
	}

	static public function splitFields($cronCommand) {
		return parent::splitFields($cronCommand);
	}

	static public function convertRangeToListOfValues($range) {
		return parent::convertRangeToListOfValues($range);
	}

	static public function reduceListOfValuesByStepValue($stepExpression) {
		return parent::reduceListOfValuesByStepValue($stepExpression);
	}

	static public function normalizeMonthAndWeekday($expression, $isMonth = TRUE) {
		return parent::normalizeMonthAndWeekday($expression, $isMonth);
	}

	static public function normalizeMonth($month) {
		return parent::normalizeMonth($month);
	}

	static public function normalizeWeekday($weekday) {
		return parent::normalizeWeekday($weekday);
	}
}
