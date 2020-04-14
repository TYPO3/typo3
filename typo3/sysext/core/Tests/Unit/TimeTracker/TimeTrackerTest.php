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

namespace TYPO3\CMS\Core\Tests\Unit\TimeTracker;

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
    public function getParseTimeReturnsZeroOrOneIfNoValuesAreSet(): void
    {
        $parseTime = (new TimeTracker())->getParseTime();
        self::assertLessThanOrEqual(1, $parseTime);
    }

    /**
     * @test
     */
    public function getParseTimeReturnsTotalParseTimeInMilliseconds(): void
    {
        $subject = new TimeTracker();
        $subject->start();
        sleep(1);
        $subject->finish();
        self::assertLessThan(1040, $subject->getParseTime());
    }
}
