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

namespace TYPO3\CMS\Core\Tests\Unit\Charset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Charset\CharsetProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CharsetConverterTest extends UnitTestCase
{
    #[Test]
    public function utf8ToNumberArray(): void
    {
        $string = "\xF0\x9F\x98\x82 &ndash; a joyful emoji";
        $expectedArray = [
            '😂',
            ' ',
            '–',
            ' ',
            'a',
            ' ',
            'j',
            'o',
            'y',
            'f',
            'u',
            'l',
            ' ',
            'e',
            'm',
            'o',
            'j',
            'i',
        ];
        self::assertSame($expectedArray, (new CharsetConverter(new CharsetProvider()))->utf8_to_numberarray($string));
    }

    public static function utf8CharMappingDataProvider(): array
    {
        return [
            'scandinavian input' => [
                'Näe ja koe',
                // See issue #20612 - this is actually a wrong transition, but the way the method currently works
                'Naee ja koe',
            ],
            'german input' => [
                'Größere Änderungswünsche Weißräm',
                'Groessere AEnderungswuensche Weissraem',
            ],
        ];
    }

    #[DataProvider('utf8CharMappingDataProvider')]
    #[Test]
    public function utf8CharMapping(string $input, string $expectedString): void
    {
        self::assertSame($expectedString, (new CharsetConverter(new CharsetProvider()))->utf8_char_mapping($input));
    }
}
