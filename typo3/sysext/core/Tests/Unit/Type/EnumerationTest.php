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

namespace TYPO3\CMS\Core\Tests\Unit\Type;

use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration;
use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\DuplicateConstantValueEnumeration;
use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\InvalidConstantEnumeration;
use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\MissingConstantsEnumeration;
use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\MissingDefaultEnumeration;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationDefinitionException;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EnumerationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfNoConstantsAreDefined(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512753);

        new MissingConstantsEnumeration();
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfInvalidValueIsRequested(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512761);

        new CompleteEnumeration('bar');
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfGivenValueIsNotAvailableInEnumeration(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512807);

        new MissingConstantsEnumeration(2);
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfDisallowedTypeIsDefinedAsConstant(): void
    {
        $this->expectException(InvalidEnumerationDefinitionException::class);
        $this->expectExceptionCode(1381512797);

        new InvalidConstantEnumeration(1);
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfNoDefaultConstantIsDefinedAndNoValueIsGiven(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512753);

        new MissingDefaultEnumeration();
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfValueIsDefinedMultipleTimes(): void
    {
        $this->expectException(InvalidEnumerationDefinitionException::class);
        $this->expectExceptionCode(1381512859);

        new DuplicateConstantValueEnumeration(1);
    }

    /**
     * Array of value pairs and expected comparison result
     * @return array
     */
    public function looseEnumerationValues(): array
    {
        return [
            [
                1,
                CompleteEnumeration::INTEGER_VALUE,
            ],
            [
                '1',
                CompleteEnumeration::INTEGER_VALUE,
            ],
            [
                2,
                CompleteEnumeration::STRING_INTEGER_VALUE,
            ],
            [
                '2',
                CompleteEnumeration::STRING_INTEGER_VALUE,
            ],
            [
                'foo',
                CompleteEnumeration::STRING_VALUE,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider looseEnumerationValues
     * @param $testValue
     * @param $expectedValue
     */
    public function doesTypeLooseComparison($testValue, $expectedValue): void
    {
        $value = new CompleteEnumeration($testValue);

        self::assertEquals((string)$expectedValue, (string)$value);
    }

    /**
     * @test
     */
    public function getConstantsReturnsArrayOfPossibleValuesWithoutDefault(): void
    {
        $expected = [
            'INTEGER_VALUE' => 1,
            'STRING_INTEGER_VALUE' => '2',
            'STRING_VALUE' => 'foo',
        ];

        self::assertEquals($expected, CompleteEnumeration::getConstants());
    }

    /**
     * @test
     */
    public function getConstantsReturnsArrayOfPossibleValuesWithDefaultIfRequested(): void
    {
        $expected = [
            'INTEGER_VALUE' => 1,
            'STRING_INTEGER_VALUE' => '2',
            'STRING_VALUE' => 'foo',
            '__default' => 1,
        ];

        self::assertEquals($expected, CompleteEnumeration::getConstants(true));
    }

    /**
     * @test
     */
    public function getConstantsCanBeCalledOnInstances(): void
    {
        $enumeration = new CompleteEnumeration();
        $expected = [
            'INTEGER_VALUE' => 1,
            'STRING_INTEGER_VALUE' => '2',
            'STRING_VALUE' => 'foo',
        ];

        self::assertEquals($expected, $enumeration::getConstants());
    }

    /**
     * @test
     */
    public function toStringReturnsValueAsString(): void
    {
        $enumeration = new CompleteEnumeration();
        self::assertSame('1', $enumeration->__toString());
    }

    /**
     * @test
     */
    public function castReturnsObjectOfEnumerationTypeIfSimpleValueIsGiven(): void
    {
        $enumeration = CompleteEnumeration::cast(1);
        self::assertInstanceOf(CompleteEnumeration::class, $enumeration);
    }

    /**
     * @test
     */
    public function castReturnsObjectOfCalledEnumerationTypeIfCalledWithValueOfDifferentType(): void
    {
        $initialEnumeration = new MissingDefaultEnumeration(1);
        $enumeration = CompleteEnumeration::cast($initialEnumeration);
        self::assertInstanceOf(CompleteEnumeration::class, $enumeration);
    }

    /**
     * @test
     */
    public function castReturnsGivenObjectIfCalledWithValueOfSameType(): void
    {
        $initialEnumeration = new CompleteEnumeration(1);
        $enumeration = CompleteEnumeration::cast($initialEnumeration);
        self::assertSame($initialEnumeration, $enumeration);
    }

    /**
     * @test
     */
    public function castCastsStringToEnumerationWithCorrespondingValue(): void
    {
        $value = new CompleteEnumeration(CompleteEnumeration::STRING_VALUE);

        self::assertSame(CompleteEnumeration::STRING_VALUE, (string)$value);
    }

    /**
     * @test
     */
    public function castCastsIntegerToEnumerationWithCorrespondingValue(): void
    {
        $value = new CompleteEnumeration(CompleteEnumeration::INTEGER_VALUE);

        self::assertSame((int)(string)CompleteEnumeration::INTEGER_VALUE, (int)(string)$value);
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfIntegerIsGivenThatEqualsEnumerationsIntegerValue(): void
    {
        $enumeration = new CompleteEnumeration(1);
        self::assertTrue($enumeration->equals(1));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfStringIsGivenThatEqualsEnumerationsIntegerValue(): void
    {
        $enumeration = new CompleteEnumeration(1);
        self::assertTrue($enumeration->equals('1'));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfEqualEnumerationIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration(1);
        $enumerationBar = new CompleteEnumeration(1);
        self::assertTrue($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfDifferentEnumerationWithSameValueIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration(1);
        $enumerationBar = new MissingDefaultEnumeration(1);
        self::assertTrue($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfDifferentEnumerationWithDifferentValueIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration('foo');
        $enumerationBar = new MissingDefaultEnumeration(1);
        self::assertFalse($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfEnumerationOfSameTypeWithDifferentValueIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration(1);
        $enumerationBar = new CompleteEnumeration('foo');
        self::assertFalse($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function getNameProvidesNameForAvailableConstant(): void
    {
        $result = CompleteEnumeration::getName(CompleteEnumeration::INTEGER_VALUE);
        self::assertSame('INTEGER_VALUE', $result);
    }

    /**
     * @test
     */
    public function getNameReturnsEmptyStringForNotAvailableConstant(): void
    {
        $result = CompleteEnumeration::getName(42);
        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function getHumanReadableNameProvidesNameForAvailableConstant(): void
    {
        $result = CompleteEnumeration::getHumanReadableName(CompleteEnumeration::INTEGER_VALUE);
        self::assertSame('Integer Value', $result);
    }

    /**
     * @test
     */
    public function getHumanReadableNameReturnsEmptyStringForNotAvailableConstant(): void
    {
        $result = CompleteEnumeration::getName(42);
        self::assertSame('', $result);
    }
}
