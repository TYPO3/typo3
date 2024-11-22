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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CharsetConverterTest extends UnitTestCase
{
    #[Test]
    public function utf8DecodeACharacterToAscii(): void
    {
        $subject = new CharsetConverter();
        $string = "\x41"; // A
        // test decoding to ascii
        self::assertSame('A', $subject->utf8_decode($string, 'ascii'));
        self::assertSame('A', $subject->utf8_decode($string, 'ascii', true));
        $result = $subject->utf8_decode($string, 'ascii');
        self::assertSame('ASCII', mb_detect_encoding($result, ['ASCII', 'UTF-8']));
    }

    #[Test]
    public function utf8DecodeEuroSignCharacterToIso885915(): void
    {
        $subject = new CharsetConverter();
        $string = "\xE2\x82\xAC"; // €
        // test decoding to ascii
        self::assertSame('?', $subject->utf8_decode($string, 'ascii'));
        self::assertSame('&#x20ac;', $subject->utf8_decode($string, 'ascii', true));
        // test decoding to iso-8859-15
        $result = $subject->utf8_decode($string, 'iso-8859-15');
        self::assertSame('ISO-8859-15', mb_detect_encoding($result, ['ASCII', 'UTF-8', 'ISO-8859-15']));
        self::assertNotSame($string, $result);
    }

    #[Test]
    public function utf8DecodeAKanjiToBig5(): void
    {
        $subject = new CharsetConverter();
        $string = "\xE6\xBC\x80"; // 漀
        // test decoding to ascii
        self::assertSame('?', $subject->utf8_decode($string, 'ascii'));
        self::assertSame('&#x6f00;', $subject->utf8_decode($string, 'ascii', true));
        // test decoding to big5
        $result = $subject->utf8_decode($string, 'big5');
        self::assertSame('BIG-5', mb_detect_encoding($result, ['ASCII', 'UTF-8', 'BIG-5']));
        self::assertNotSame($string, $result);
    }

    #[Test]
    public function convertingAUtf8EmojiSignToNonExistingAsciiRepresentationResultsInAQuestionMarkSign(): void
    {
        $subject = new CharsetConverter();
        $string = "\xF0\x9F\x98\x82"; // 😂
        // test decoding to ascii
        self::assertSame('?', $subject->utf8_decode($string, 'ascii'));
        self::assertSame('&#x1f602;', $subject->utf8_decode($string, 'ascii', true));
    }

    #[Test]
    public function utf8DecodeToUtf8ReturnsTheSameSign(): void
    {
        self::assertSame("\xF0\x9F\x98\x82", (new CharsetConverter())->utf8_decode("\xF0\x9F\x98\x82", 'utf-8'));
    }

    #[Test]
    public function utf8EncodeIso885915ACharacter(): void
    {
        $string = "\x41"; // A
        $result = (new CharsetConverter())->utf8_encode($string, 'iso-8859-15');
        self::assertSame('A', $result);
    }

    #[Test]
    public function utf8EncodeIso885915EuroSign(): void
    {
        $string = "\xA4"; // € sign encoded as iso-8859-15
        $result = (new CharsetConverter())->utf8_encode($string, 'iso-8859-15');
        self::assertSame('€', $result);
    }

    #[Test]
    public function utf8EncodeABig5EncodedSign(): void
    {
        $string = "\xA2\xC5"; // 〣 sign encoded as big5
        $result =  (new CharsetConverter())->utf8_encode($string, 'big5');
        self::assertSame('〣', $result);
    }

    #[Test]
    public function utf8EncodeAlreadyUtf8EncodedSign(): void
    {
        self::assertSame("\xF0\x9F\x98\x82", (new CharsetConverter())->utf8_encode("\xF0\x9F\x98\x82", 'utf-8'));
    }

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
        self::assertSame($expectedArray, (new CharsetConverter())->utf8_to_numberarray($string));
    }

    public static function validInputForSpecCharsToAscii(): array
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

    #[DataProvider('validInputForSpecCharsToAscii')]
    #[Test]
    public function specCharsToAsciiConvertsUmlautsToAscii(string $input, string $expectedString): void
    {
        self::assertSame($expectedString, (new CharsetConverter())->specCharsToASCII('utf-8', $input));
    }

    public static function invalidInputForSpecCharsToAscii(): array
    {
        return [
            'integer input' => [
                1,
            ],
            'null input' => [
                null,
            ],
            'boolean input' => [
                true,
            ],
            'floating point input' => [
                3.14,
            ],
        ];
    }

    #[DataProvider('invalidInputForSpecCharsToAscii')]
    #[Test]
    public function specCharsToAsciiConvertsInvalidInputToEmptyString(int|null|bool|float $input): void
    {
        self::assertSame('', (new CharsetConverter())->specCharsToASCII('utf-8', $input));
    }
}
