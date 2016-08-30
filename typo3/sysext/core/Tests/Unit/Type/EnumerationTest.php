<?php
namespace TYPO3\CMS\Core\Tests\Unit\Type;

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

use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration;

/**
 * Test case
 */
class EnumerationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function constructorThrowsExceptionIfNoConstantsAreDefined()
    {
        new Enumeration\MissingConstantsEnumeration();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function constructorThrowsExceptionIfInvalidValueIsRequested()
    {
        new Enumeration\CompleteEnumeration('bar');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function loadValuesThrowsExceptionIfGivenValueIsNotAvailableInEnumeration()
    {
        new Enumeration\MissingConstantsEnumeration(2);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationDefinitionException
     */
    public function loadValuesThrowsExceptionIfDisallowedTypeIsDefinedAsConstant()
    {
        new Enumeration\InvalidConstantEnumeration(1);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function loadValuesThrowsExceptionIfNoDefaultConstantIsDefinedAndNoValueIsGiven()
    {
        new Enumeration\MissingDefaultEnumeration();
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationDefinitionException
     */
    public function loadValuesThrowsExceptionIfValueIsDefinedMultipleTimes()
    {
        new Enumeration\DuplicateConstantValueEnumeration(1);
    }

    /**
     * @test
     */
    public function loadValuesSetsStaticEnumConstants()
    {
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy']
        );

        $enumClassName = get_class($enumeration);

        $expectedValue = [
            'INTEGER_VALUE' => 1,
            'STRING_VALUE' => 'foo',
             '__default' => 1
        ];

        $result = $enumeration->_getStatic('enumConstants');
        $this->assertArrayHasKey($enumClassName, $result);
        $this->assertSame($expectedValue, $result[$enumClassName]);
    }

    /**
     * @test
     */
    public function constructorSetsValue()
    {
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy'],
            [1]
        );
        $this->assertEquals(1, $enumeration->_get('value'));
    }

    /**
     * @test
     */
    public function setValueSetsValue()
    {
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy'],
            [1]
        );
        $enumeration->_call('setValue', 'foo');
        $this->assertEquals('foo', $enumeration->_get('value'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     */
    public function setValueToAnInvalidValueThrowsException()
    {
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy'],
            [1]
        );
        $enumeration->_call('setValue', 2);
        $this->assertEquals(2, $enumeration->_get('value'));
    }

    /**
     * Array of value pairs and expected comparison result
     */
    public function isValidComparisonExpectations()
    {
        return [
            [
                1,
                1,
                true
            ],
            [
                1,
                '1',
                true
            ],
            [
                '1',
                1,
                true
            ],
            [
                'a1',
                1,
                false
            ],
            [
                1,
                'a1',
                false
            ],
            [
                '1a',
                1,
                false
            ],
            [
                1,
                '1a',
                false
            ],
            [
                'foo',
                'foo',
                true
            ],
            [
                'foo',
                'bar',
                false
            ],
            [
                'foo',
                'foobar',
                false
            ]
        ];
    }

    /**
     * @test
     * @dataProvider isValidComparisonExpectations
     */
    public function isValidDoesTypeLooseComparison($enumerationValue, $testValue, $expectation)
    {
        $mockName = $this->getUniqueId('CompleteEnumerationMock');
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy'],
            [],
            $mockName,
            false
        );
        $enumeration->_setStatic('enumConstants', [$mockName => ['CONSTANT_NAME' => $enumerationValue]]);
        $enumeration->_set('value', $enumerationValue);
        $this->assertSame($expectation, $enumeration->_call('isValid', $testValue));
    }

    /**
     * @test
     */
    public function getConstantsReturnsArrayOfPossibleValuesWithoutDefault()
    {
        $this->assertEquals(['INTEGER_VALUE' => 1, 'STRING_VALUE' => 'foo'], Enumeration\CompleteEnumeration::getConstants());
    }

    /**
     * @test
     */
    public function getConstantsReturnsArrayOfPossibleValuesWithDefaultIfRequested()
    {
        $this->assertEquals(['INTEGER_VALUE' => 1, 'STRING_VALUE' => 'foo', '__default' => 1], Enumeration\CompleteEnumeration::getConstants(true));
    }

    /**
     * @test
     */
    public function getConstantsCanBeCalledOnInstances()
    {
        $enumeration = new Enumeration\CompleteEnumeration();
        $this->assertEquals(['INTEGER_VALUE' => 1, 'STRING_VALUE' => 'foo'], $enumeration->getConstants());
    }

    /**
     * @test
     */
    public function toStringReturnsValueAsString()
    {
        $enumeration = new Enumeration\CompleteEnumeration();
        $this->assertSame('1', $enumeration->__toString());
    }

    /**
     * @test
     */
    public function castReturnsObjectOfEnumerationTypeIfSimpleValueIsGiven()
    {
        $enumeration = Enumeration\CompleteEnumeration::cast(1);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class, $enumeration);
    }

    /**
     * @test
     */
    public function castReturnsObjectOfCalledEnumerationTypeIfCalledWithValueOfDifferentType()
    {
        $initialEnumeration = new Enumeration\MissingDefaultEnumeration(1);
        $enumeration = Enumeration\CompleteEnumeration::cast($initialEnumeration);
        $this->assertInstanceOf(\TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class, $enumeration);
    }

    /**
     * @test
     */
    public function castReturnsGivenObjectIfCalledWithValueOfSameType()
    {
        $initialEnumeration = new Enumeration\CompleteEnumeration(1);
        $enumeration = Enumeration\CompleteEnumeration::cast($initialEnumeration);
        $this->assertSame($initialEnumeration, $enumeration);
    }

    /**
     * @test
     */
    public function castCastsStringToEnumerationWithCorrespondingValue()
    {
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy'],
            ['1']
        );
        $this->assertSame(1, $enumeration->_get('value'));
    }

    /**
     * @test
     */
    public function castCastsIntegerToEnumerationWithCorrespondingValue()
    {
        $enumeration = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration::class,
            ['dummy'],
            [1]
        );
        $this->assertSame(1, $enumeration->_get('value'));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfIntegerIsGivenThatEqualsEnumerationsIntegerValue()
    {
        $enumeration = new Enumeration\CompleteEnumeration(1);
        $this->assertTrue($enumeration->equals(1));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfStringIsGivenThatEqualsEnumerationsIntegerValue()
    {
        $enumeration = new Enumeration\CompleteEnumeration(1);
        $this->assertTrue($enumeration->equals('1'));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfEqualEnumerationIsGiven()
    {
        $enumerationFoo = new Enumeration\CompleteEnumeration(1);
        $enumerationBar = new Enumeration\CompleteEnumeration(1);
        $this->assertTrue($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfDifferentEnumerationWithSameValueIsGiven()
    {
        $enumerationFoo = new Enumeration\CompleteEnumeration(1);
        $enumerationBar = new Enumeration\MissingDefaultEnumeration(1);
        $this->assertTrue($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfDifferentEnumerationWithDifferentValueIsGiven()
    {
        $enumerationFoo = new Enumeration\CompleteEnumeration('foo');
        $enumerationBar = new Enumeration\MissingDefaultEnumeration(1);
        $this->assertFalse($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfEnumerationOfSameTypeWithDifferentValueIsGiven()
    {
        $enumerationFoo = new Enumeration\CompleteEnumeration(1);
        $enumerationBar = new Enumeration\CompleteEnumeration('foo');
        $this->assertFalse($enumerationFoo->equals($enumerationBar));
    }
}
