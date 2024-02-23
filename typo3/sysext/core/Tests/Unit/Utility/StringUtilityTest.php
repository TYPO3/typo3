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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StringUtilityTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: mixed}>
     */
    public static function stringCastableValuesDataProvider(): \Generator
    {
        yield 'empty string' => [''];
        yield 'string' => ['value'];
        yield 'int' => [1];
        yield 'float' => [1.2345];
        yield 'bool' => [true];
    }

    #[DataProvider('stringCastableValuesDataProvider')]
    #[Test]
    public function castWithStringCastableReturnsValueCastToString(mixed $value): void
    {
        $expected = (string)$value;

        self::assertSame($expected, StringUtility::cast($value, 'default'));
    }

    /**
     * @return \Generator<string, array{0: mixed}>
     */
    public static function nonStringCastableValuesDataProvider(): \Generator
    {
        yield 'array' => [['1']];
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
        yield 'string backed enum' => [ApplicationType::BACKEND];
        yield 'closure' => [static fn(): string => 'fn'];
        // PHP interprets it as `lim(x→0) log(x) = -∞`
        yield 'infinite' => [log(0)];
        // acos only supports values in range [-1; +1]
        yield 'NaN' => [acos(2)];
    }

    #[DataProvider('nonStringCastableValuesDataProvider')]
    #[Test]
    public function castWithWithNonStringCastableReturnsDefault(mixed $value): void
    {
        $default = 'default';

        self::assertSame($default, StringUtility::cast($value, $default));
    }

    #[DataProvider('nonStringCastableValuesDataProvider')]
    #[Test]
    public function castWithWithNonStringCastableAndNoDefaultProvidedReturnsNull(mixed $value): void
    {
        self::assertNull(StringUtility::cast($value));
    }

    /**
     * @return \Generator<string, array{0: mixed}>
     */
    public static function nonStringValueToFilterDataProvider(): \Generator
    {
        yield 'int' => [1];
        yield 'float' => [1.2345];
        yield 'bool' => [true];
        yield 'array' => [['1']];
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
        yield 'string backed enum' => [ApplicationType::BACKEND];
        yield 'closure' => [static fn(): string => 'fn'];
        // PHP interprets it as `lim(x→0) log(x) = -∞`
        yield 'infinite' => [log(0)];
        // acos only supports values in range [-1; +1]
        yield 'NaN' => [acos(2)];
    }

    #[DataProvider('nonStringValueToFilterDataProvider')]
    #[Test]
    public function filterForNonStringValueAndDefaultProvidedReturnsDefault(mixed $value): void
    {
        $default = 'default';

        self::assertSame($default, StringUtility::filter($value, $default));
    }

    #[DataProvider('nonStringValueToFilterDataProvider')]
    #[Test]
    public function filterForNonStringValueAndNoDefaultProvidedReturnsNull(mixed $value): void
    {
        self::assertNull(StringUtility::filter($value));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function stringValueToFilterDataProvider(): \Generator
    {
        yield 'empty string' => [''];
        yield 'non-empty string' => ['value'];
    }
    #[DataProvider('stringValueToFilterDataProvider')]
    #[Test]
    public function filterForStringValuesReturnsProvidedValue(string $value): void
    {
        self::assertSame($value, StringUtility::filter($value, 'some default'));
    }

    #[Test]
    public function getUniqueIdReturnsIdWithPrefix(): void
    {
        $id = StringUtility::getUniqueId('NEW');
        self::assertEquals('NEW', substr($id, 0, 3));
    }

    #[Test]
    public function getUniqueIdReturnsIdWithoutDot(): void
    {
        self::assertStringNotContainsString('.', StringUtility::getUniqueId());
    }

    #[DataProvider('escapeCssSelectorDataProvider')]
    #[Test]
    public function escapeCssSelector(string $selector, string $expectedValue): void
    {
        self::assertEquals($expectedValue, StringUtility::escapeCssSelector($selector));
    }

    public static function escapeCssSelectorDataProvider(): array
    {
        return [
            ['data.field', 'data\\.field'],
            ['#theId', '\\#theId'],
            ['.theId:hover', '\\.theId\\:hover'],
            ['.theId:hover', '\\.theId\\:hover'],
            ['input[name=foo]', 'input\\[name\\=foo\\]'],
        ];
    }

    #[DataProvider('removeByteOrderMarkDataProvider')]
    #[Test]
    public function removeByteOrderMark(string $input, string $expectedValue): void
    {
        // assertContains is necessary as one test contains non-string characters
        self::assertSame($expectedValue, StringUtility::removeByteOrderMark(hex2bin($input)));
    }

    public static function removeByteOrderMarkDataProvider(): array
    {
        return [
            'BOM gets removed' => [
                'efbbbf424f4d2061742074686520626567696e6e696e6720676574732072656d6f766564',
                'BOM at the beginning gets removed',
            ],
            'No BOM available' => [
                '4e6f20424f4d20617661696c61626c65',
                'No BOM available',
            ],
        ];
    }

    #[DataProvider('searchStringWildcardDataProvider')]
    #[Test]
    public function searchStringWildcard(string $haystack, string $needle, bool $result): void
    {
        self::assertSame($result, StringUtility::searchStringWildcard($haystack, $needle));
    }

    public static function searchStringWildcardDataProvider(): array
    {
        return [
            'Simple wildcard single character with *' => [
                'TYPO3',
                'TY*O3',
                true,
            ],
            'Simple wildcard multiple character with *' => [
                'TYPO3',
                'T*P*3',
                true,
            ],
            'Simple wildcard multiple character for one placeholder with *' => [
                'TYPO3',
                'T*3',
                true,
            ],
            'Simple wildcard single character with ?' => [
                'TYPO3',
                'TY?O3',
                true,
            ],
            'Simple wildcard multiple character with ?' => [
                'TYPO3',
                'T?P?3',
                true,
            ],
            'Simple wildcard multiple character for one placeholder with ?' => [
                'TYPO3',
                'T?3',
                false,
            ],
            'RegExp' => [
                'TYPO3',
                '/^TYPO(\d)$/',
                true,
            ],
        ];
    }

    /**
     * Data provider for uniqueListUnifiesCommaSeparatedList
     */
    public static function uniqueListUnifiesCommaSeparatedListDataProvider(): \Generator
    {
        yield 'List without duplicates' => ['one,two,three', 'one,two,three'];
        yield 'List with two consecutive duplicates' => ['one,two,two,three,three', 'one,two,three'];
        yield 'List with non-consecutive duplicates' => ['one,two,three,two,three', 'one,two,three'];
        yield 'One item list' => ['one', 'one'];
        yield 'Empty list' => ['', ''];
        yield 'No list, just a comma' => [',', ''];
        yield 'List with leading comma' => [',one,two', 'one,two'];
        yield 'List with trailing comma' => ['one,two,', 'one,two'];
        yield 'List with multiple consecutive commas' => ['one,,two', 'one,two'];
    }

    #[DataProvider('uniqueListUnifiesCommaSeparatedListDataProvider')]
    #[Test]
    public function uniqueListUnifiesCommaSeparatedList(string $initialList, string $unifiedList): void
    {
        self::assertSame($unifiedList, StringUtility::uniqueList($initialList));
    }

    /**
     * Data provider for multibyteStringPadReturnsSameValueAsStrPadForAsciiStrings
     */
    public static function multibyteStringPadReturnsSameValueAsStrPadForAsciiStringsDataProvider(): \Generator
    {
        yield 'Pad right to 10 with string with uneven length' => ['ABC', 10, ' ', STR_PAD_RIGHT];
        yield 'Pad left to 10  with string with uneven length' => ['ABC', 10, ' ', STR_PAD_LEFT];
        yield 'Pad both to 10  with string with uneven length' => ['ABC', 10, ' ', STR_PAD_BOTH];
        yield 'Pad right to 10 with string with uneven length and 2 character padding' => ['ABC', 10, '12', STR_PAD_RIGHT];
        yield 'Pad left to 10 with string with uneven length and 2 character padding' => ['ABC', 10, '12', STR_PAD_LEFT];
        yield 'Pad both to 10 with string with uneven length and 2 character padding' => ['ABC', 10, '12', STR_PAD_BOTH];

        yield 'Pad right to 10 with string with even length' => ['AB', 10, ' ', STR_PAD_RIGHT];
        yield 'Pad left to 10  with string with even length' => ['AB', 10, ' ', STR_PAD_LEFT];
        yield 'Pad both to 10  with string with even length' => ['AB', 10, ' ', STR_PAD_BOTH];
        yield 'Pad right to 10 with string with even length and 2 character padding' => ['AB', 10, '12', STR_PAD_RIGHT];
        yield 'Pad left to 10 with string with even length and 2 character padding' => ['AB', 10, '12', STR_PAD_LEFT];
        yield 'Pad both to 10 with string with even length and 2 character padding' => ['AB', 10, '12', STR_PAD_BOTH];
    }

    /**
     * Tests that StringUtility::multibyteStringPad() returns the same value as \str_pad()
     * for ASCII strings.
     */
    #[DataProvider('multibyteStringPadReturnsSameValueAsStrPadForAsciiStringsDataProvider')]
    #[Test]
    public function multibyteStringPadReturnsSameValueAsStrPadForAsciiStrings(string $string, int $length, string $pad_string, int $pad_type): void
    {
        self::assertEquals(
            str_pad($string, $length, $pad_string, $pad_type),
            StringUtility::multibyteStringPad($string, $length, $pad_string, $pad_type)
        );
    }

    public static function multibyteStringPadReturnsCorrectResultsMultibyteDataProvider(): \Generator
    {
        yield 'Pad right to 8 with string with uneven length' => ['häh     ', 'häh', 8, ' ', STR_PAD_RIGHT];
        yield 'Pad left to 8  with string with uneven length' => ['     häh', 'häh', 8, ' ', STR_PAD_LEFT];
        yield 'Pad both to 8  with string with uneven length' => ['  häh   ', 'häh', 8, ' ', STR_PAD_BOTH];
        yield 'Pad right to 8 with string with uneven length and 2 character padding' => ['hühäöäöä', 'hüh', 8, 'äö', STR_PAD_RIGHT];
        yield 'Pad left to 8 with string with uneven length and 2 character padding'  => ['äöäöähüh', 'hüh', 8, 'äö', STR_PAD_LEFT];
        yield 'Pad both to 8 with string with uneven length and 2 character padding'  => ['äöhühäöä', 'hüh', 8, 'äö', STR_PAD_BOTH];

        yield 'Pad right to 8 with string with even length' => ['hä      ', 'hä', 8, ' ', STR_PAD_RIGHT];
        yield 'Pad left to 8  with string with even length' => ['      hä', 'hä', 8, ' ', STR_PAD_LEFT];
        yield 'Pad both to 8  with string with even length' => ['   hä   ', 'hä', 8, ' ', STR_PAD_BOTH];
        yield 'Pad right to 8 with string with even length and 2 character padding with MB char' => ['hüäöäöäö', 'hü', 8, 'äö', STR_PAD_RIGHT];
        yield 'Pad left to 8 with string with even length and 2 character padding with MB char'  => ['äöäöäöhü', 'hü', 8, 'äö', STR_PAD_LEFT];
        yield 'Pad both to 8 with string with even length and 2 character padding with MB char'  => ['äöähüäöä', 'hü', 8, 'äö', STR_PAD_BOTH];
    }

    #[DataProvider('multibyteStringPadReturnsCorrectResultsMultibyteDataProvider')]
    #[Test]
    public function multibyteStringPadReturnsCorrectResultsMultibyte(string $expectedResult, string $string, int $length, string $pad_string, int $pad_type): void
    {
        self::assertEquals(
            $expectedResult,
            StringUtility::multibyteStringPad($string, $length, $pad_string, $pad_type)
        );
    }

    public static function base64urlRoundTripWorksDataProvider(): \Generator
    {
        yield ['a'];
        yield ['aa'];
        yield ['aaa'];
        yield ['aaaa'];
        yield [random_bytes(31)];
        yield [random_bytes(32)];
        yield [random_bytes(33)];
    }

    #[DataProvider('base64urlRoundTripWorksDataProvider')]
    #[Test]
    public function base64urlRoundTripWorks(string $rawValue): void
    {
        $encoded = StringUtility::base64urlEncode($rawValue);
        $decoded = StringUtility::base64urlDecode($encoded);
        self::assertSame($rawValue, $decoded);
    }

    public static function base64urlDataProvider(): \Generator
    {
        yield ['', ''];
        yield ['a', 'YQ'];
        yield ['aa', 'YWE'];
        yield ['aa>', 'YWE-'];
        yield ['aa?', 'YWE_'];
        yield ['aaa', 'YWFh'];
        yield ['aaaa', 'YWFhYQ'];
    }

    #[DataProvider('base64urlDataProvider')]
    #[Test]
    public function base64urlEncodeWorks(string $rawValue, string $encodedValue): void
    {
        self::assertSame($encodedValue, StringUtility::base64urlEncode($rawValue));
    }

    #[DataProvider('base64urlDataProvider')]
    #[Test]
    public function base64urlDecodeWorks(string $rawValue, string $encodedValue): void
    {
        self::assertSame($rawValue, StringUtility::base64urlDecode($encodedValue));
    }

    public static function base64urlStrictDataProvider(): \Generator
    {
        yield ['', ''];
        yield ['YQ', 'a'];
        yield ['YWE', 'aa'];
        yield ['YWE-', 'aa>'];
        yield ['YWE_', 'aa?'];
        yield ['YWFh', 'aaa'];
        yield ['YWFhYQ', 'aaaa'];
        yield ['YWFhYQ!', false];
        yield ['Y!W!E', false];
        // `Y W E` is interesting - plain `base64_decode` strips inner spaces
        yield ['Y W E', 'aa'];
        yield ["Y\nW\nE", 'aa'];
        yield ["Y\tW\tE", 'aa'];
    }

    #[DataProvider('base64urlStrictDataProvider')]
    #[Test]
    public function base64urlStrictDecodeWorks(string $encodedValue, string|bool $expectation): void
    {
        self::assertSame($expectation, StringUtility::base64urlDecode($encodedValue, true));
    }

    public static function explodeEscapedDataProvider(): array
    {
        return [
            'no escape' => [
                'test.test',
                [
                    'test',
                    'test',
                ],
            ],
            'escaped once' => [
                'test\.test.abc',
                [
                    'test.test',
                    'abc',
                ],
            ],
            'escaped twice' => [
                'test\.test.abc\.another',
                [
                    'test.test',
                    'abc.another',
                ],
            ],
            'escaped three times at the begining of the key' => [
                'test\.test.\.abc\.another',
                [
                    'test.test',
                    '.abc.another',
                ],
            ],
            'escape the escape char' => [
                'test\\\.test.abc',
                [
                    'test\.test',
                    'abc',
                ],
            ],
        ];
    }

    #[DataProvider('explodeEscapedDataProvider')]
    #[Test]
    public function explodeEscapedWorks(string $escaped, array $unescapedExploded): void
    {
        self::assertSame($unescapedExploded, StringUtility::explodeEscaped('.', $escaped));
    }
}
