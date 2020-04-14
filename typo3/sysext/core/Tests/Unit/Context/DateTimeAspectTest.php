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

use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DateTimeAspectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getDateTimeReturnsSameObject()
    {
        $dateObject = new \DateTimeImmutable('2018-07-15', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        $result = $subject->getDateTime();
        self::assertSame($dateObject, $result);
    }

    /**
     * @test
     */
    public function getThrowsExceptionOnInvalidArgument()
    {
        $this->expectException(AspectPropertyNotFoundException::class);
        $this->expectExceptionCode(1527778767);
        $dateObject = new \DateTimeImmutable('2018-07-15', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        $subject->get('football');
    }

    /**
     * @return array
     */
    public function dateFormatValuesDataProvider()
    {
        return [
            'timestamp' => [
                'timestamp',
                '1531648805'
            ],
            'iso' => [
                'iso',
                '2018-07-15T13:00:05+03:00'
            ],
            'timezone' => [
                'timezone',
                'Europe/Moscow'
            ],
            'full' => [
                'full',
                new \DateTimeImmutable('2018-07-15T13:00:05', new \DateTimeZone('Europe/Moscow'))
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dateFormatValuesDataProvider
     * @param string $key
     * @param string $expectedResult
     */
    public function getReturnsValidInformationFromProperty($key, $expectedResult)
    {
        $dateObject = new \DateTimeImmutable('2018-07-15T13:00:05', new \DateTimeZone('Europe/Moscow'));
        $subject = new DateTimeAspect($dateObject);
        self::assertEquals($expectedResult, $subject->get($key));
    }
}
