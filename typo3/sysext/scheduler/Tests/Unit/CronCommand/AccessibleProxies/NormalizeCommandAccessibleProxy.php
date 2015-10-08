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
class NormalizeCommandAccessibleProxy extends NormalizeCommand
{
    public static function convertKeywordsToCronCommand($cronCommand)
    {
        return parent::convertKeywordsToCronCommand($cronCommand);
    }

    public static function normalizeFields($cronCommand)
    {
        return parent::normalizeFields($cronCommand);
    }

    public static function normalizeMonthAndWeekdayField($expression, $isMonthField = true)
    {
        return parent::normalizeMonthAndWeekdayField($expression, $isMonthField);
    }

    public static function normalizeIntegerField($expression, $lowerBound = 0, $upperBound = 59)
    {
        return parent::normalizeIntegerField($expression, $lowerBound, $upperBound);
    }

    public static function splitFields($cronCommand)
    {
        return parent::splitFields($cronCommand);
    }

    public static function convertRangeToListOfValues($range)
    {
        return parent::convertRangeToListOfValues($range);
    }

    public static function reduceListOfValuesByStepValue($stepExpression)
    {
        return parent::reduceListOfValuesByStepValue($stepExpression);
    }

    public static function normalizeMonthAndWeekday($expression, $isMonth = true)
    {
        return parent::normalizeMonthAndWeekday($expression, $isMonth);
    }

    public static function normalizeMonth($month)
    {
        return parent::normalizeMonth($month);
    }

    public static function normalizeWeekday($weekday)
    {
        return parent::normalizeWeekday($weekday);
    }
}
