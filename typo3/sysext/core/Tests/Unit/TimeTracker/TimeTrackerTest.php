<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\TimeTracker;

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

use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TimeTrackerTest
 */
class TimeTrackerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getParseTimeReturnsZeroOrOneIfNoValuesAreSet()
    {
        unset(
            $GLOBALS['TYPO3_MISC']['microtime_end'],
            $GLOBALS['TYPO3_MISC']['microtime_start'],
            $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'],
            $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end']
        );
        $parseTime = (new TimeTracker())->getParseTime();
        self::assertLessThanOrEqual(1, $parseTime);
    }

    /**
     * @test
     */
    public function getParseTimeReturnsTotalParseTimeInMillisecondsWithoutBeUserInitialization()
    {
        $baseValue = time();
        $GLOBALS['TYPO3_MISC']['microtime_start'] = $baseValue;
        $GLOBALS['TYPO3_MISC']['microtime_end'] = $baseValue + 10;
        $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] = $baseValue + 1;
        $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] = $baseValue + 3;
        $parseTime = (new TimeTracker())->getParseTime();
        self::assertSame(8000, $parseTime);
    }

    /**
     * @test
     */
    public function getParseTimeReturnsParseTimeIfOnlyOneBeUserTimeWasSet()
    {
        $baseValue = time();
        $GLOBALS['TYPO3_MISC']['microtime_start'] = $baseValue;
        $GLOBALS['TYPO3_MISC']['microtime_end'] = $baseValue + 10;
        $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] = $baseValue + 1;
        $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] = 0;
        $parseTime = (new TimeTracker())->getParseTime();
        self::assertSame(10000, $parseTime);
    }

    /**
     * @test
     */
    public function getParseTimeReturnsParseTimeIfNoBeUserTimeWasSet()
    {
        $baseValue = time();
        $GLOBALS['TYPO3_MISC']['microtime_start'] = $baseValue;
        $GLOBALS['TYPO3_MISC']['microtime_end'] = $baseValue + 10;
        $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] = 0;
        $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] = 0;
        $parseTime = (new TimeTracker())->getParseTime();
        self::assertSame(10000, $parseTime);
    }
}
