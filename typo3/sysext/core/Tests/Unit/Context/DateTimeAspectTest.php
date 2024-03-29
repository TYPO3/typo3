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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DateTimeAspectTest extends UnitTestCase
{
    #[Test]
    public function getDateTimeReturnsSameObject(): void
    {
        $dateObject = new \DateTimeImmutable('2018-07-15', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        $result = $subject->getDateTime();
        self::assertSame($dateObject, $result);
    }

    #[Test]
    public function getThrowsExceptionOnInvalidArgument(): void
    {
        $this->expectException(AspectPropertyNotFoundException::class);
        $this->expectExceptionCode(1527778767);
        $dateObject = new \DateTimeImmutable('2018-07-15', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        $subject->get('football');
    }

    #[Test]
    public function getTimestampReturnsInteger(): void
    {
        $dateObject = new \DateTimeImmutable('2018-07-15', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        $timestamp = $subject->get('timestamp');
        self::assertIsInt($timestamp);
    }

    #[Test]
    public function getTimezoneReturnsUtcTimezoneOffsetWhenDateTimeIsInitializedFromUnixTimestamp(): void
    {
        $dateObject = new \DateTimeImmutable('@12345');
        $subject = new DateTimeAspect($dateObject);
        self::assertSame('+00:00', $subject->get('timezone'));
    }

    #[Test]
    public function getTimezoneReturnsGivenTimezoneOffsetWhenDateTimeIsInitializedFromIso8601Date(): void
    {
        $dateObject = new \DateTimeImmutable('2004-02-12T15:19:21+05:00');
        $subject = new DateTimeAspect($dateObject);
        self::assertSame('+05:00', $subject->get('timezone'));
    }

    public static function dateFormatValuesDataProvider(): array
    {
        return [
            'timestamp' => [
                'timestamp',
                1531648805,
            ],
            'iso' => [
                'iso',
                '2018-07-15T13:00:05+03:00',
            ],
            'timezone' => [
                'timezone',
                'Europe/Moscow',
            ],
            'full' => [
                'full',
                new \DateTimeImmutable('2018-07-15T13:00:05', new \DateTimeZone('Europe/Moscow')),
            ],
            'accessTime' => [
                'accessTime',
                1531648800,
            ],
        ];
    }

    /**
     * @param string $key
     * @param string $expectedResult
     */
    #[DataProvider('dateFormatValuesDataProvider')]
    #[Test]
    public function getReturnsValidInformationFromProperty($key, $expectedResult): void
    {
        $dateObject = new \DateTimeImmutable('2018-07-15T13:00:05', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        self::assertEquals($expectedResult, $subject->get($key));
    }
}
