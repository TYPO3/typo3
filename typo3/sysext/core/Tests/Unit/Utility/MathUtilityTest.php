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
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\MathUtilityTestClassWithStringRepresentationFixture;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MathUtilityTest extends UnitTestCase
{
    public static function forceIntegerInRangeForcesIntegerIntoDefaultBoundariesDataProvider(): array
    {
        return [
            'negativeValue' => [0, -10],
            'normalValue' => [30, 30],
            'veryHighValue' => [2000000000, PHP_INT_MAX],
            'zeroValue' => [0, 0],
            'anotherNormalValue' => [12309, 12309],
        ];
    }

    #[DataProvider('forceIntegerInRangeForcesIntegerIntoDefaultBoundariesDataProvider')]
    #[Test]
    public function forceIntegerInRangeForcesIntegerIntoDefaultBoundaries($expected, $value): void
    {
        self::assertEquals($expected, MathUtility::forceIntegerInRange($value, 0));
    }

    #[Test]
    public function forceIntegerInRangeSetsDefaultValueIfZeroValueIsGiven(): void
    {
        self::assertEquals(42, MathUtility::forceIntegerInRange('', 0, 2000000000, 42));
    }

    public static function functionCanBeInterpretedAsIntegerValidDataProvider(): array
    {
        return [
            'int' => [32425],
            'negative int' => [-32425],
            'largest int' => [PHP_INT_MAX],
            'smallest int' => [PHP_INT_MIN],
            'int as string' => ['32425'],
            'negative int as string' => ['-32425'],
            'zero' => [0],
            'zero as string' => ['0'],
            'one' => [1],
            'one as string' => ['1'],
            'one as float' => [1.0],
            'true' => [true],
        ];
    }

    #[DataProvider('functionCanBeInterpretedAsIntegerValidDataProvider')]
    #[Test]
    public function canBeInterpretedAsIntegerReturnsTrue($int): void
    {
        self::assertTrue(MathUtility::canBeInterpretedAsInteger($int));
    }

    public static function functionCanBeInterpretedAsIntegerInvalidDataProvider(): array
    {
        $objectWithNumericalStringRepresentation = new MathUtilityTestClassWithStringRepresentationFixture();
        $objectWithNumericalStringRepresentation->setString('1234');
        $objectWithNonNumericalStringRepresentation = new MathUtilityTestClassWithStringRepresentationFixture();
        $objectWithNonNumericalStringRepresentation->setString('foo');
        $objectWithEmptyStringRepresentation = new MathUtilityTestClassWithStringRepresentationFixture();
        $objectWithEmptyStringRepresentation->setString('');
        return [
            'int as string with leading zero' => ['01234'],
            'positive int as string with plus modifier' => ['+1234'],
            'negative int as string with leading zero' => ['-01234'],
            'multiple zeroes' => ['00'],
            'largest int plus one' => [PHP_INT_MAX + 1],
            'string' => ['testInt'],
            'empty string' => [''],
            'int in string' => ['5 times of testInt'],
            'int as string with space after' => ['5 '],
            'int as string with space before' => [' 5'],
            'int as string with many spaces before' => ['     5'],
            'NaN' => [INF / INF],
            'float' => [3.14159],
            'float as string' => ['3.14159'],
            'float as string only a dot' => ['10.'],
            'float as string trailing zero would evaluate to int 10' => ['10.0'],
            'float as string trailing zeros	 would evaluate to int 10' => ['10.00'],
            'null' => [null],
            'false' => [false],
            'empty array' => [[]],
            'int in array' => [[32425]],
            'int as string in array' => [['32425']],
            'object without string representation' => [new \stdClass()],
            'object with numerical string representation' => [$objectWithNumericalStringRepresentation],
            'object without numerical string representation' => [$objectWithNonNumericalStringRepresentation],
            'object with empty string representation' => [$objectWithEmptyStringRepresentation],
        ];
    }

    #[DataProvider('functionCanBeInterpretedAsIntegerInvalidDataProvider')]
    #[Test]
    public function canBeInterpretedAsIntegerReturnsFalse($int): void
    {
        self::assertFalse(MathUtility::canBeInterpretedAsInteger($int));
    }

    public static function functionCanBeInterpretedAsFloatValidDataProvider(): array
    {
        // testcases for Integer apply for float as well
        $intTestcases = self::functionCanBeInterpretedAsIntegerValidDataProvider();
        $floatTestcases = [
            'zero as float' => [(float)0],
            'negative float' => [(float)-7.5],
            'negative float as string with exp #1' => ['-7.5e3'],
            'negative float as string with exp #2' => ['-7.5e03'],
            'negative float as string with exp #3' => ['-7.5e-3'],
            'float' => [3.14159],
            'float as string' => ['3.14159'],
            'float as string only a dot' => ['10.'],
            'float as string trailing zero' => ['10.0'],
            'float as string trailing zeros' => ['10.00'],
        ];
        return array_merge($intTestcases, $floatTestcases);
    }

    #[DataProvider('functionCanBeInterpretedAsFloatValidDataProvider')]
    #[Test]
    public function canBeInterpretedAsFloatReturnsTrue($val): void
    {
        self::assertTrue(MathUtility::canBeInterpretedAsFloat($val));
    }

    public static function functionCanBeInterpretedAsFloatInvalidDataProvider(): array
    {
        $objectWithNumericalStringRepresentation = new MathUtilityTestClassWithStringRepresentationFixture();
        $objectWithNumericalStringRepresentation->setString('1234');
        $objectWithNonNumericalStringRepresentation = new MathUtilityTestClassWithStringRepresentationFixture();
        $objectWithNonNumericalStringRepresentation->setString('foo');
        $objectWithEmptyStringRepresentation = new MathUtilityTestClassWithStringRepresentationFixture();
        $objectWithEmptyStringRepresentation->setString('');
        return [
            // 'int as string with leading zero' => array('01234'),
            // 'positive int as string with plus modifier' => array('+1234'),
            // 'negative int as string with leading zero' => array('-01234'),
            // 'largest int plus one' => array(PHP_INT_MAX + 1),
            'string' => ['testInt'],
            'empty string' => [''],
            'int in string' => ['5 times of testInt'],
            'int as string with space after' => ['5 '],
            'int as string with space before' => [' 5'],
            'int as string with many spaces before' => ['     5'],
            'null' => [null],
            'empty array' => [[]],
            'int in array' => [[32425]],
            'int as string in array' => [['32425']],
            'negative float as string with invalid chars in exponent' => ['-7.5eX3'],
            'object without string representation' => [new \stdClass()],
            'object with numerical string representation' => [$objectWithNumericalStringRepresentation],
            'object without numerical string representation' => [$objectWithNonNumericalStringRepresentation],
            'object with empty string representation' => [$objectWithEmptyStringRepresentation],
        ];
    }

    #[DataProvider('functionCanBeInterpretedAsFloatInvalidDataProvider')]
    #[Test]
    public function canBeInterpretedAsFloatReturnsFalse($int): void
    {
        self::assertFalse(MathUtility::canBeInterpretedAsFloat($int));
    }

    public static function calculateWithPriorityToAdditionAndSubtractionDataProvider(): array
    {
        return [
            'add' => [9, '6 + 3'],
            'substract with positive result' => [3, '6 - 3'],
            'substract with negative result' => [-3, '3 - 6'],
            'multiply' => [6, '2 * 3'],
            'divide' => [2.5, '5 / 2'],
            'modulus' => [1, '5 % 2'],
            'power' => [8, '2 ^ 3'],
            'three operands with non integer result' => [6.5, '5 + 3 / 2'],
            'three operands with power' => [14, '5 + 3 ^ 2'],
            'three operands with modulus' => [4, '5 % 2 + 3'],
            'four operands' => [3, '2 + 6 / 2 - 2'],
            'division by zero when dividing' => ['ERROR: dividing by zero', '2 / 0'],
            'division by zero with modulus' => ['ERROR: dividing by zero', '2 % 0'],
        ];
    }

    #[DataProvider('calculateWithPriorityToAdditionAndSubtractionDataProvider')]
    #[Test]
    public function calculateWithPriorityToAdditionAndSubtractionCorrectlyCalculatesExpression($expected, $expression): void
    {
        self::assertEquals($expected, MathUtility::calculateWithPriorityToAdditionAndSubtraction($expression));
    }

    public static function calculateWithParenthesesDataProvider(): array
    {
        return [
            'starts with parenthesis' => [18, '(6 + 3) * 2'],
            'ends with parenthesis' => [6, '2 * (6 - 3)'],
            'multiple parentheses' => [-6, '(3 - 6) * (4 - 2)'],
            'nested parentheses' => [22, '2 * (3 + 2 + (3 * 2))'],
            'parenthesis with division' => [15, '5 / 2 * (3 * 2)'],
        ];
    }

    #[DataProvider('calculateWithParenthesesDataProvider')]
    #[Test]
    public function calculateWithParenthesesCorrectlyCalculatesExpression($expected, $expression): void
    {
        self::assertEquals($expected, MathUtility::calculateWithParentheses($expression));
    }

    #[Test]
    public function isIntegerInRangeIncludesLowerBoundary(): void
    {
        self::assertTrue(MathUtility::isIntegerInRange(1, 1, 2));
    }

    #[Test]
    public function isIntegerInRangeIncludesUpperBoundary(): void
    {
        self::assertTrue(MathUtility::isIntegerInRange(2, 1, 2));
    }

    #[Test]
    public function isIntegerInRangeAcceptsValueInRange(): void
    {
        self::assertTrue(MathUtility::isIntegerInRange(10, 1, 100));
    }

    #[Test]
    public function isIntegerInRangeRejectsValueOutsideOfRange(): void
    {
        self::assertFalse(MathUtility::isIntegerInRange(10, 1, 2));
    }

    public static function isIntegerInRangeRejectsOtherDataTypesDataProvider(): array
    {
        return [
            'negative integer' => [-1],
            'float' => [1.5],
            'string' => ['string'],
            'array' => [[]],
            'object' => [new \stdClass()],
            'boolean FALSE' => [false],
            'NULL' => [null],
        ];
    }

    #[DataProvider('isIntegerInRangeRejectsOtherDataTypesDataProvider')]
    #[Test]
    public function isIntegerInRangeRejectsOtherDataTypes($inputValue): void
    {
        self::assertFalse(MathUtility::isIntegerInRange($inputValue, 0, 10));
    }

    public static function roundDecimalStringDataProvider(): iterable
    {
        yield 'value with less decimal digits is padded' => ['1.5', 2, '1.50'];
        yield 'value without decimal digits is padded' => ['1', 2, '1.00'];
        yield 'empty value becomes zero' => ['', 2, '0.00'];
        yield 'leading zeros are stripped' => ['007.5', 2, '7.50'];
        yield 'value is rounded down' => ['1.234', 2, '1.23'];
        yield 'value is rounded up' => ['1.235', 2, '1.24'];
        yield 'rounding up carries over to the integer digits' => ['1.999', 2, '2.00'];
        yield 'rounding up creates an integer digit' => ['0.999', 2, '1.00'];
        yield 'rounding up carries over multiple digits' => ['99.999', 2, '100.00'];
        yield 'negative value keeps its sign' => ['-1.5', 2, '-1.50'];
        yield 'negative value is rounded away from zero' => ['-1.005', 2, '-1.01'];
        yield 'negative value rounded to zero is not negative' => ['-0.001', 2, '0.00'];
        yield 'scale zero rounds to an integer' => ['1.9', 0, '2'];
        yield 'negative scale is treated as zero' => ['1.9', -5, '2'];
        yield 'digits beyond float precision are kept' => [
            '2.000000001000000082740370999091',
            30,
            '2.000000001000000082740370999091',
        ];
        yield 'value is padded beyond float precision' => [
            '2.000000001',
            30,
            '2.' . str_pad('000000001', 30, '0'),
        ];
        yield 'large value is not distorted by float precision' => [
            '12345678.1234567891',
            10,
            '12345678.1234567891',
        ];
        yield 'leading plus sign is accepted' => ['+1.5', 2, '1.50'];
        yield 'value without integer digits is accepted' => ['.5', 2, '0.50'];
        yield 'value without decimal digits after the point is accepted' => ['5.', 2, '5.00'];
        yield 'negative exponent moves the decimal point to the right' => ['1.0e-7', 7, '0.0000001'];
        yield 'negative exponent is not distorted by float precision' => [
            '1.234567890123456789e-10',
            28,
            '0.0000000001234567890123456789',
        ];
        yield 'positive exponent moves the decimal point to the left' => ['1.5e3', 2, '1500.00'];
        yield 'positive exponent beyond the given digits is padded' => ['1.5E5', 0, '150000'];
        yield 'exponent of a negative value keeps the sign' => ['-2.5e-2', 3, '-0.025'];
        yield 'exponent is rounded to the scale' => ['1.29e-1', 2, '0.13'];
    }

    public static function roundDecimalStringRejectsDataProvider(): iterable
    {
        yield 'decimal comma' => ['1,5'];
        yield 'thousands separator' => ['1,234.56'];
        yield 'letters' => ['abc'];
        yield 'unit appended to a number' => ['1.5px'];
        yield 'two decimal points' => ['1.2.3'];
        yield 'sign only' => ['-'];
        yield 'decimal point only' => ['.'];
        yield 'surrounding whitespace' => [' 1.5 '];
        yield 'exponent without digits' => ['1.5e'];
        yield 'exponent with decimal digits' => ['1.5e1.5'];
    }

    /**
     * Reinterpreting a value that is not a number silently turns "1,5" into 15, so it is rejected.
     */
    #[DataProvider('roundDecimalStringRejectsDataProvider')]
    #[Test]
    public function roundDecimalStringThrowsExceptionOnInvalidValue(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1788293269);

        MathUtility::roundDecimalString($value, 2);
    }

    #[DataProvider('roundDecimalStringDataProvider')]
    #[Test]
    public function roundDecimalStringReturnsExpectedValue(string $value, int $scale, string $expected): void
    {
        self::assertSame($expected, MathUtility::roundDecimalString($value, $scale));
    }
}
