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

namespace TYPO3\CMS\Core\Tests\Unit\Localization;

use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DateFormatterTest extends UnitTestCase
{
    protected static function formatDateProvider(): \Generator
    {
        yield 'regular formatting - no locale' => [
            '2023.02.02 AD at 13:05:00 UTC',
            "yyyy.MM.dd G 'at' HH:mm:ss zzz",
        ];
        yield 'full - no locale' => [
            'Thursday, February 2, 2023 at 13:05:00 Coordinated Universal Time',
            'FULL',
        ];
        yield 'long - no locale' => [
            'February 2, 2023 at 13:05:00 UTC',
            'LONG',
        ];
        yield 'medium - no locale' => [
            'Feb 2, 2023, 13:05:00',
            'MEDIUM',
        ];
        yield 'medium with int - no locale' => [
            'Feb 2, 2023, 13:05:00',
            \IntlDateFormatter::MEDIUM,
        ];
        yield 'medium with int as string - no locale' => [
            'Feb 2, 2023, 13:05:00',
            (string)\IntlDateFormatter::MEDIUM,
        ];
        yield 'short - no locale' => [
            '2/2/23, 13:05',
            'SHORT',
        ];
        yield 'short in lowercase - no locale' => [
            '2/2/23, 13:05',
            'short',
        ];
        yield 'regular formatting - en-US locale' => [
            '2023.02.02 AD at 13:05:00 UTC',
            "yyyy.MM.dd G 'at' HH:mm:ss zzz",
            'en-US',
        ];
        yield 'full - en-US locale' => [
            'Thursday, February 2, 2023 at 1:05:00 PM Coordinated Universal Time',
            'FULL',
            'en-US',
        ];
        yield 'long - en-US locale' => [
            'February 2, 2023 at 1:05:00 PM UTC',
            'LONG',
            'en-US',
        ];
        yield 'medium - en-US locale' => [
            'Feb 2, 2023, 1:05:00 PM',
            'MEDIUM',
            'en-US',
        ];
        yield 'short - en-US locale' => [
            '2/2/23, 1:05 PM',
            'SHORT',
            'en-US',
        ];
        yield 'regular formatting - german locale' => [
            '2023.02.02 n. Chr. um 13:05:00 UTC',
            "yyyy.MM.dd G 'um' HH:mm:ss zzz",
            'de-DE',
        ];
        yield 'full - german locale' => [
            'Donnerstag, 2. Februar 2023 um 13:05:00 Koordinierte Weltzeit',
            'FULL',
            'de-DE',
        ];
        yield 'long - german locale' => [
            '2. Februar 2023 um 13:05:00 UTC',
            'LONG',
            'de-DE',
        ];
        yield 'medium - german locale' => [
            '02.02.2023, 13:05:00',
            'MEDIUM',
            'de-DE',
        ];
        yield 'short - german locale' => [
            '02.02.23, 13:05',
            'SHORT',
            'de-DE',
        ];
        yield 'custom date only - german locale' => [
            '02. Februar 2023',
            'dd. MMMM yyyy',
            'de-DE',
        ];
        yield 'custom time only - german locale' => [
            '13:05:00',
            'HH:mm:ss',
            new Locale('de'),
        ];
    }

    /**
     * @test
     * @dataProvider formatDateProvider
     */
    public function formatFormatsCorrectly(string $expected, mixed $format, string|Locale|null $locale = 'C'): void
    {
        $input = new \DateTimeImmutable('2023-02-02 13:05:00');
        $subject = new DateFormatter();
        self::assertEquals($expected, $subject->format($input, $format, $locale));
    }
}
