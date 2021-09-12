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

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\MathUtilityTestClassWithStringRepresentationFixture;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\MathUtility
 */
class MathUtilityTest extends UnitTestCase
{
    //////////////////////////////////
    // Tests concerning forceIntegerInRange
    //////////////////////////////////
    /**
     * Data provider for forceIntegerInRangeForcesIntegerIntoBoundaries
     *
     * @return array expected values, arithmetic expression
     */
    public function forceIntegerInRangeForcesIntegerIntoDefaultBoundariesDataProvider(): array
    {
        return [
            'negativeValue' => [0, -10],
            'normalValue' => [30, 30],
            'veryHighValue' => [2000000000, PHP_INT_MAX],
            'zeroValue' => [0, 0],
            'anotherNormalValue' => [12309, 12309]
        ];
    }

    /**
     * @test
     * @dataProvider forceIntegerInRangeForcesIntegerIntoDefaultBoundariesDataProvider
     */
    public function forceIntegerInRangeForcesIntegerIntoDefaultBoundaries($expected, $value): void
    {
        self::assertEquals($expected, MathUtility::forceIntegerInRange($value, 0));
    }

    /**
     * @test
     */
    public function forceIntegerInRangeSetsDefaultValueIfZeroValueIsGiven(): void
    {
        self::assertEquals(42, MathUtility::forceIntegerInRange('', 0, 2000000000, 42));
    }

    //////////////////////////////////
    // Tests concerning convertToPositiveInteger
    //////////////////////////////////
    /**
     * @test
     */
    public function convertToPositiveIntegerReturnsZeroForNegativeValues(): void
    {
        self::assertEquals(0, MathUtility::convertToPositiveInteger(-123));
    }

    /**
     * @test
     */
    public function convertToPositiveIntegerReturnsTheInputValueForPositiveValues(): void
    {
        self::assertEquals(123, MathUtility::convertToPositiveInteger(123));
    }

    ///////////////////////////////
    // Tests concerning canBeInterpretedAsInteger
    ///////////////////////////////
    /**
     * Data provider for canBeInterpretedAsIntegerReturnsTrue
     *
     * @return array Data sets
     */
    public function functionCanBeInterpretedAsIntegerValidDataProvider(): array
    {
        return [
            'int' => [32425],
            'negative int' => [-32425],
            'largest int' => [PHP_INT_MAX],
            'int as string' => ['32425'],
            'negative int as string' => ['-32425'],
            'zero' => [0],
            'zero as string' => ['0']
        ];
    }

    /**
     * @test
     * @dataProvider functionCanBeInterpretedAsIntegerValidDataProvider
     */
    public function canBeInterpretedAsIntegerReturnsTrue($int): void
    {
        self::assertTrue(MathUtility::canBeInterpretedAsInteger($int));
    }

    /**
     * Data provider for canBeInterpretedAsIntegerReturnsFalse
     *
     * @return array Data sets
     */
    public function functionCanBeInterpretedAsIntegerInvalidDataProvider(): array
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
            'largest int plus one' => [PHP_INT_MAX + 1],
            'string' => ['testInt'],
            'empty string' => [''],
            'int in string' => ['5 times of testInt'],
            'int as string with space after' => ['5 '],
            'int as string with space before' => [' 5'],
            'int as string with many spaces before' => ['     5'],
            'float' => [3.14159],
            'float as string' => ['3.14159'],
            'float as string only a dot' => ['10.'],
            'float as string trailing zero would evaluate to int 10' => ['10.0'],
            'float as string trailing zeros	 would evaluate to int 10' => ['10.00'],
            'null' => [null],
            'empty array' => [[]],
            'int in array' => [[32425]],
            'int as string in array' => [['32425']],
            'object without string representation' => [new \stdClass()],
            'object with numerical string representation' => [$objectWithNumericalStringRepresentation],
            'object without numerical string representation' => [$objectWithNonNumericalStringRepresentation],
            'object with empty string representation' => [$objectWithEmptyStringRepresentation]
        ];
    }

    /**
     * @test
     * @dataProvider functionCanBeInterpretedAsIntegerInvalidDataProvider
     */
    public function canBeInterpretedAsIntegerReturnsFalse($int): void
    {
        self::assertFalse(MathUtility::canBeInterpretedAsInteger($int));
    }

    ///////////////////////////////
    // Tests concerning canBeInterpretedAsFloat
    ///////////////////////////////
    /**
     * Data provider for canBeInterpretedAsFloatReturnsTrue
     *
     * @return array Data sets
     */
    public function functionCanBeInterpretedAsFloatValidDataProvider(): array
    {
        // testcases for Integer apply for float as well
        $intTestcases = $this->functionCanBeInterpretedAsIntegerValidDataProvider();
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

    /**
     * @test
     * @dataProvider functionCanBeInterpretedAsFloatValidDataProvider
     */
    public function canBeInterpretedAsFloatReturnsTrue($val): void
    {
        self::assertTrue(MathUtility::canBeInterpretedAsFloat($val));
    }

    /**
     * Data provider for canBeInterpretedAsFloatReturnsFalse
     *
     * @return array Data sets
     */
    public function functionCanBeInterpretedAsFloatInvalidDataProvider(): array
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
            'object with empty string representation' => [$objectWithEmptyStringRepresentation]
        ];
    }

    /**
     * @test
     * @dataProvider functionCanBeInterpretedAsFloatInvalidDataProvider
     */
    public function canBeInterpretedAsFloatReturnsFalse($int): void
    {
        self::assertFalse(MathUtility::canBeInterpretedAsFloat($int));
    }

    //////////////////////////////////
    // Tests concerning calculateWithPriorityToAdditionAndSubtraction
    //////////////////////////////////
    /**
     * Data provider for calculateWithPriorityToAdditionAndSubtraction
     *
     * @return array expected values, arithmetic expression
     */
    public function calculateWithPriorityToAdditionAndSubtractionDataProvider(): array
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
            'division by zero with modulus' => ['ERROR: dividing by zero', '2 % 0']
        ];
    }

    /**
     * @test
     * @dataProvider calculateWithPriorityToAdditionAndSubtractionDataProvider
     */
    public function calculateWithPriorityToAdditionAndSubtractionCorrectlyCalculatesExpression($expected, $expression): void
    {
        self::assertEquals($expected, MathUtility::calculateWithPriorityToAdditionAndSubtraction($expression));
    }

    //////////////////////////////////
    // Tests concerning calcParenthesis
    //////////////////////////////////
    /**
     * Data provider for calcParenthesis
     *
     * @return array expected values, arithmetic expression
     */
    public function calculateWithParenthesesDataProvider(): array
    {
        return [
            'starts with parenthesis' => [18, '(6 + 3) * 2'],
            'ends with parenthesis' => [6, '2 * (6 - 3)'],
            'multiple parentheses' => [-6, '(3 - 6) * (4 - 2)'],
            'nested parentheses' => [22, '2 * (3 + 2 + (3 * 2))'],
            'parenthesis with division' => [15, '5 / 2 * (3 * 2)']
        ];
    }

    /**
     * @test
     * @dataProvider calculateWithParenthesesDataProvider
     */
    public function calculateWithParenthesesCorrectlyCalculatesExpression($expected, $expression): void
    {
        self::assertEquals($expected, MathUtility::calculateWithParentheses($expression));
    }

    //////////////////////////////////
    // Tests concerning isIntegerInRange
    //////////////////////////////////
    /**
     * @test
     */
    public function isIntegerInRangeIncludesLowerBoundary(): void
    {
        self::assertTrue(MathUtility::isIntegerInRange(1, 1, 2));
    }

    /**
     * @test
     */
    public function isIntegerInRangeIncludesUpperBoundary(): void
    {
        self::assertTrue(MathUtility::isIntegerInRange(2, 1, 2));
    }

    /**
     * @test
     */
    public function isIntegerInRangeAcceptsValueInRange(): void
    {
        self::assertTrue(MathUtility::isIntegerInRange(10, 1, 100));
    }

    /**
     * @test
     */
    public function isIntegerInRangeRejectsValueOutsideOfRange(): void
    {
        self::assertFalse(MathUtility::isIntegerInRange(10, 1, 2));
    }

    /**
     * Data provider or isIntegerInRangeRejectsOtherDataTypes
     */
    public function isIntegerInRangeRejectsOtherDataTypesDataProvider(): array
    {
        return [
            'negative integer' => [-1],
            'float' => [1.5],
            'string' => ['string'],
            'array' => [[]],
            'object' => [new \stdClass()],
            'boolean FALSE' => [false],
            'NULL' => [null]
        ];
    }

    /**
     * @test
     * @dataProvider isIntegerInRangeRejectsOtherDataTypesDataProvider
     */
    public function isIntegerInRangeRejectsOtherDataTypes($inputValue): void
    {
        self::assertFalse(MathUtility::isIntegerInRange($inputValue, 0, 10));
    }
}
