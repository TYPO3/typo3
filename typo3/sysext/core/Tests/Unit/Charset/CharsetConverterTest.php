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

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CharsetConverterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function utf8DecodeACharacterToAscii()
    {
        $charsetConverter = new CharsetConverter();

        $string = "\x41"; // A
        self::assertSame(1, mb_strlen($string));
        self::assertSame(1, strlen($string));
        self::assertSame('UTF-8', mb_detect_encoding($string, ['UTF-8', 'ASCII']));

        // test decoding to ascii
        self::assertSame('A', $charsetConverter->utf8_decode($string, 'ascii'));
        self::assertSame('A', $charsetConverter->utf8_decode($string, 'ascii', true));

        $targetString = $charsetConverter->utf8_decode($string, 'ascii');
        self::assertSame('ASCII', mb_detect_encoding($targetString, ['ASCII', 'UTF-8']));
    }

    /**
     * @test
     */
    public function utf8DecodeACharacterToIso885915()
    {
        $charsetConverter = new CharsetConverter();

        $string = "\xE2\x82\xAC"; // €
        self::assertSame(1, mb_strlen($string));
        self::assertSame(3, strlen($string));
        self::assertSame('UTF-8', mb_detect_encoding($string, ['ASCII', 'UTF-8']));

        // test decoding to ascii
        self::assertSame('?', $charsetConverter->utf8_decode($string, 'ascii'));
        self::assertSame('&#x20ac;', $charsetConverter->utf8_decode($string, 'ascii', true));

        // test decoding to iso-8859-15
        $targetString = $charsetConverter->utf8_decode($string, 'iso-8859-15');
        self::assertSame('ISO-8859-15', mb_detect_encoding($targetString, ['ASCII', 'UTF-8', 'ISO-8859-15']));
        self::assertNotSame($string, $targetString);
    }

    /**
     * @test
     */
    public function utf8DecodeEuroSignCharacterToIso885915()
    {
        $charsetConverter = new CharsetConverter();

        $string = "\xE2\x82\xAC"; // €
        self::assertSame(1, mb_strlen($string));
        self::assertSame(3, strlen($string));
        self::assertSame('UTF-8', mb_detect_encoding($string, ['ASCII', 'UTF-8']));

        // test decoding to ascii
        self::assertSame('?', $charsetConverter->utf8_decode($string, 'ascii'));
        self::assertSame('&#x20ac;', $charsetConverter->utf8_decode($string, 'ascii', true));

        // test decoding to iso-8859-15
        $targetString = $charsetConverter->utf8_decode($string, 'iso-8859-15');
        self::assertSame('ISO-8859-15', mb_detect_encoding($targetString, ['ASCII', 'UTF-8', 'ISO-8859-15']));
        self::assertNotSame($string, $targetString);
    }

    /**
     * @test
     */
    public function utf8DecodeAKanjiToBig5()
    {
        $charsetConverter = new CharsetConverter();

        $string = "\xE6\xBC\x80"; // 漀
        self::assertSame(1, mb_strlen($string));
        self::assertSame(3, strlen($string));
        self::assertSame('UTF-8', mb_detect_encoding($string, ['ASCII', 'UTF-8']));

        // test decoding to ascii
        self::assertSame('?', $charsetConverter->utf8_decode($string, 'ascii'));
        self::assertSame('&#x6f00;', $charsetConverter->utf8_decode($string, 'ascii', true));

        // test decoding to big5
        $targetString = $charsetConverter->utf8_decode($string, 'big5');
        self::assertSame('BIG-5', mb_detect_encoding($targetString, ['ASCII', 'UTF-8', 'BIG-5']));
        self::assertNotSame($string, $targetString);
    }

    /**
     * @test
     */
    public function convertingAUtf8EmojiSignToNonExistingAsciiRepresentationResultsInAQuestionMarkSign()
    {
        $charsetConverter = new CharsetConverter();

        $string = "\xF0\x9F\x98\x82"; // 😂
        self::assertSame(1, mb_strlen($string));
        self::assertSame(4, strlen($string));
        self::assertSame('UTF-8', mb_detect_encoding($string, ['ASCII', 'UTF-8']));

        // test decoding to ascii
        self::assertSame('?', $charsetConverter->utf8_decode($string, 'ascii'));
        self::assertSame('&#x1f602;', $charsetConverter->utf8_decode($string, 'ascii', true));
    }

    /**
     * @test
     */
    public function utf8DecodeToUtf8ReturnsTheSameSign()
    {
        self::assertSame(
            "\xF0\x9F\x98\x82",
            (new CharsetConverter())->utf8_decode("\xF0\x9F\x98\x82", 'utf-8')
        );
    }

    /**
     * @test
     */
    public function utf8EncodeIso885915ACharacter()
    {
        $string = "\x41"; // A
        $targetString = (new CharsetConverter())->utf8_encode($string, 'iso-8859-15');

        self::assertSame(1, strlen($string));
        self::assertSame('A', $targetString);
        self::assertSame(1, mb_strlen($targetString));
        self::assertSame(1, strlen($targetString));
        self::assertSame($string, $targetString);
    }

    /**
     * @test
     */
    public function utf8EncodeIso885915EuroSign()
    {
        $string = "\xA4"; // € sign encoded as iso-8859-15
        $targetString = (new CharsetConverter())->utf8_encode($string, 'iso-8859-15');

        self::assertSame('€', $targetString);
        self::assertSame(1, mb_strlen($targetString));
        self::assertSame(3, strlen($targetString));
        self::assertNotSame($string, $targetString);
    }

    /**
     * @test
     */
    public function utf8EncodeABig5EncodedSign()
    {
        $string = "\xA2\xC5"; // 〣 sign encoded as big5
        $targetString =  (new CharsetConverter())->utf8_encode($string, 'big5');

        self::assertSame(2, strlen($string));
        self::assertSame('〣', $targetString);
        self::assertSame(1, mb_strlen($targetString));
        self::assertSame(3, strlen($targetString));
        self::assertNotSame($string, $targetString);
    }

    /**
     * @test
     */
    public function utf8EncodeAlreadyUtf8EncodedSign()
    {
        self::assertSame(
            "\xF0\x9F\x98\x82",
            (new CharsetConverter())->utf8_encode("\xF0\x9F\x98\x82", 'utf-8')
        );
    }

    /**
     * @test
     */
    public function utf8ToNumberArray()
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

    /**
     * Data provider for specialCharactersToAsciiConvertsUmlautsToAscii()
     *
     * @return string[][]
     */
    public function validInputForSpecCharsToAscii(): array
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

    /**
     * @test
     * @dataProvider validInputForSpecCharsToAscii
     * @param string $input
     * @param string $expectedString
     */
    public function specCharsToAsciiConvertsUmlautsToAscii(
        string $input,
        string $expectedString
    ) {
        $subject = new CharsetConverter();
        self::assertSame($expectedString, $subject->specCharsToASCII('utf-8', $input));
    }

    /**
     * Data provider for specialCharactersToAsciiConvertsInvalidInputToEmptyString()
     *
     * @return array[]
     */
    public function invalidInputForSpecCharsToAscii(): array
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

    /**
     * @test
     * @dataProvider invalidInputForSpecCharsToAscii
     * @param mixed $input
     */
    public function specCharsToAsciiConvertsInvalidInputToEmptyString($input)
    {
        $subject = new CharsetConverter();
        self::assertSame('', $subject->specCharsToASCII('utf-8', $input));
    }
}
