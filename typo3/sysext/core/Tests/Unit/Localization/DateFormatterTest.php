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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DateFormatterTest extends UnitTestCase
{
    public static function formatDateProvider(): \Generator
    {
        yield 'regular formatting - no locale' => [
            '2023.02.02 AD at 13:05:00 UTC',
            "yyyy.MM.dd G 'at' HH:mm:ss zzz",
        ];
        yield 'full - no locale' => [
            'Thursday, February 2, 2023 at 1:05:00 PM Coordinated Universal Time',
            'FULL',
        ];
        yield 'full - locale C' => [
            'Thursday, February 2, 2023 at 1:05:00 PM Coordinated Universal Time',
            'FULL',
            new Locale('C'),
        ];
        yield 'long - no locale' => [
            'February 2, 2023 at 1:05:00 PM UTC',
            'LONG',
        ];
        yield 'long - locale C' => [
            'February 2, 2023 at 1:05:00 PM UTC',
            'LONG',
            new Locale('C'),
        ];
        yield 'medium - no locale' => [
            'Feb 2, 2023, 1:05:00 PM',
            'MEDIUM',
        ];
        yield 'medium - locale C' => [
            'Feb 2, 2023, 1:05:00 PM',
            'MEDIUM',
            new Locale('C'),
        ];
        yield 'medium with int - no locale' => [
            'Feb 2, 2023, 1:05:00 PM',
            \IntlDateFormatter::MEDIUM,
        ];
        yield 'medium with int - locale C' => [
            'Feb 2, 2023, 1:05:00 PM',
            \IntlDateFormatter::MEDIUM,
            new Locale('C'),
        ];
        yield 'medium with int as string - no locale' => [
            'Feb 2, 2023, 1:05:00 PM',
            (string)\IntlDateFormatter::MEDIUM,
        ];
        yield 'medium with int as string - locale C' => [
            'Feb 2, 2023, 1:05:00 PM',
            (string)\IntlDateFormatter::MEDIUM,
            new Locale('C'),
        ];
        yield 'short - no locale' => [
            '2/2/23, 1:05 PM',
            'SHORT',
        ];
        yield 'short - locale C' => [
            '2/2/23, 1:05 PM',
            'SHORT',
            new Locale('C'),
        ];
        yield 'short in lowercase - no locale' => [
            '2/2/23, 1:05 PM',
            'short',
        ];
        yield 'short in lowercase - locale C' => [
            '2/2/23, 1:05 PM',
            'short',
            new Locale('C'),
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

    #[DataProvider('formatDateProvider')]
    #[Test]
    public function formatFormatsCorrectly(string $expected, mixed $format, string|Locale|null $locale = 'C'): void
    {
        $input = new \DateTimeImmutable('2023-02-02 13:05:00');
        $subject = new DateFormatter();
        self::assertEquals($expected, $subject->format($input, $format, $locale));
    }
}
