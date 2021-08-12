<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Scheduler\Tests\Unit\CronCommand\AccessibleProxies;

use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;

/**
 * Accessible proxy with protected methods made public.
 */
class NormalizeCommandAccessibleProxy extends NormalizeCommand
{
    public static function convertKeywordsToCronCommand($cronCommand): string
    {
        return parent::convertKeywordsToCronCommand($cronCommand);
    }

    public static function normalizeFields($cronCommand): string
    {
        return parent::normalizeFields($cronCommand);
    }

    public static function normalizeMonthAndWeekdayField($expression, $isMonthField = true): string
    {
        return parent::normalizeMonthAndWeekdayField($expression, $isMonthField);
    }

    public static function normalizeIntegerField($expression, $lowerBound = 0, $upperBound = 59): string
    {
        return parent::normalizeIntegerField($expression, $lowerBound, $upperBound);
    }

    public static function splitFields($cronCommand): array
    {
        return parent::splitFields($cronCommand);
    }

    public static function convertRangeToListOfValues($range): string
    {
        return parent::convertRangeToListOfValues($range);
    }

    public static function reduceListOfValuesByStepValue($stepExpression): string
    {
        return parent::reduceListOfValuesByStepValue($stepExpression);
    }

    public static function normalizeMonthAndWeekday($expression, $isMonth = true): string
    {
        return parent::normalizeMonthAndWeekday($expression, $isMonth);
    }

    public static function normalizeMonth($month): int
    {
        return parent::normalizeMonth($month);
    }

    public static function normalizeWeekday($weekday): int
    {
        return parent::normalizeWeekday($weekday);
    }
}
