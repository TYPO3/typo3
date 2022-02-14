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

namespace TYPO3\CMS\Frontend\Tests\Functional\Cache;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CacheLifetimeCalculatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/fixtures.csv');
    }

    /**
     * @test
     */
    public function getFirstTimeValueForRecordReturnCorrectData(): void
    {
        $subject = new class($this->getContainer()->get('cache.core'), $this->getContainer()->get(EventDispatcherInterface::class), $this->getContainer()->get(ConnectionPool::class)) extends CacheLifetimeCalculator {
            public function getFirstTimeValueForRecord(string $tableDef, int $currentTimestamp): int
            {
                return parent::getFirstTimeValueForRecord($tableDef, $currentTimestamp);
            }
        };

        self::assertSame(
            $subject->getFirstTimeValueForRecord('tt_content:2', 1),
            2,
            'The next start/endtime should be 2'
        );
        self::assertSame(
            $subject->getFirstTimeValueForRecord('tt_content:2', 2),
            3,
            'The next start/endtime should be 3'
        );
        self::assertSame(
            $subject->getFirstTimeValueForRecord('tt_content:2', 4),
            5,
            'The next start/endtime should be 5'
        );
        self::assertSame(
            $subject->getFirstTimeValueForRecord('tt_content:2', 5),
            PHP_INT_MAX,
            'The next start/endtime should be PHP_INT_MAX as there are no more'
        );
        self::assertSame(
            $subject->getFirstTimeValueForRecord('tt_content:3', 1),
            PHP_INT_MAX,
            'Should be PHP_INT_MAX as table has not this PID'
        );
        self::assertSame(
            $subject->getFirstTimeValueForRecord('fe_groups:2', 1),
            PHP_INT_MAX,
            'Should be PHP_INT_MAX as table fe_groups has no start/endtime in TCA'
        );
    }
}
