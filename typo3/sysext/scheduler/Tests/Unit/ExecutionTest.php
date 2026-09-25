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

namespace TYPO3\CMS\Scheduler\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExecutionTest extends UnitTestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    public static function nextIntervalExecutionKeepsLocalWallClockTimeDataProvider(): array
    {
        return [
            'daily task started before spring DST switch, evaluated after it' => [
                'Europe/Berlin',
                '2023-03-23 23:59:00',
                86400,
                '2023-04-05 12:00:00',
                '2023-04-05 23:59:00',
            ],
            'daily task evaluated on the day of the spring DST switch' => [
                'Europe/Zurich',
                '2023-03-23 23:59:00',
                86400,
                '2023-03-26 12:00:00',
                '2023-03-26 23:59:00',
            ],
            'daily task started before autumn DST switch, evaluated after it' => [
                'Europe/Berlin',
                '2023-10-25 23:59:00',
                86400,
                '2023-11-05 12:00:00',
                '2023-11-05 23:59:00',
            ],
            'multi-day interval keeps local time' => [
                'Europe/Berlin',
                '2023-03-23 23:59:00',
                86400 * 3,
                '2023-04-05 12:00:00',
                '2023-04-07 23:59:00',
            ],
            'daily task in a timezone without DST is unchanged' => [
                'Asia/Tokyo',
                '2023-03-23 23:59:00',
                86400,
                '2023-04-05 12:00:00',
                '2023-04-05 23:59:00',
            ],
        ];
    }

    #[DataProvider('nextIntervalExecutionKeepsLocalWallClockTimeDataProvider')]
    #[Test]
    public function calculateNextIntervalExecutionKeepsLocalWallClockTime(
        string $timezone,
        string $start,
        int $interval,
        string $now,
        string $expected
    ): void {
        date_default_timezone_set($timezone);
        $execution = Execution::createRecurringExecution(strtotime($start), $interval);

        $result = $execution->calculateNextIntervalExecution(strtotime($now));

        self::assertSame($expected, date('Y-m-d H:i:s', $result));
    }

    #[Test]
    public function calculateNextIntervalExecutionKeepsStrictPeriodForSubDayIntervals(): void
    {
        date_default_timezone_set('Europe/Berlin');
        $start = strtotime('2023-03-23 23:59:00');
        $execution = Execution::createRecurringExecution($start, 3600);
        $now = strtotime('2023-04-05 12:30:00');

        $result = $execution->calculateNextIntervalExecution($now);

        self::assertSame(0, ($result - $start) % 3600);
        self::assertGreaterThan($now, $result);
        self::assertLessThanOrEqual($now + 3600, $result);
    }
}
