<?php
declare(strict_types=1);

namespace TimeTracker;

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

/**
 * Class TimeTrackerTest
 */
class TimeTrackerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var bool
     */
    protected $backupGlobals = true;

    /**
     * @var TimeTracker
     */
    protected $timeTracker;

    protected function setUp()
    {
        $this->timeTracker = new TimeTracker();
    }

    /**
     * @test
     */
    public function getParseTimeReturnsZeroIfNoValuesAreSet()
    {
        unset(
            $GLOBALS['TYPO3_MISC']['microtime_end'],
            $GLOBALS['TYPO3_MISC']['microtime_start'],
            $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'],
            $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end']
        );
        $parseTime = $this->timeTracker->getParseTime();
        self::assertSame(0, $parseTime);
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
        $parseTime = $this->timeTracker->getParseTime();
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
        $parseTime = $this->timeTracker->getParseTime();
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
        $parseTime = $this->timeTracker->getParseTime();
        self::assertSame(10000, $parseTime);
    }
}
