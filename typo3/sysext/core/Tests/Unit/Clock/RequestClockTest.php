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

namespace TYPO3\CMS\Core\Tests\Unit\Clock;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Clock\RequestClock;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RequestClockTest extends UnitTestCase
{
    #[Test]
    public function nowReturnsSameInstantOnEveryCall(): void
    {
        $frozenAt = new \DateTimeImmutable('2026-08-10 12:00:00');
        $subject = new RequestClock($frozenAt);
        self::assertSame($frozenAt, $subject->now());
        self::assertSame($frozenAt, $subject->now());
    }

    #[Test]
    public function createFreezesClockAtGlobalExecutionTime(): void
    {
        $GLOBALS['EXEC_TIME'] = 1700000000;
        $subject = RequestClock::create();
        self::assertSame(1700000000, $subject->now()->getTimestamp());
    }
}
