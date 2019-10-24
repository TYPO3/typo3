<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ArgumentTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument
     */
    protected $simpleValueArgument;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument
     */
    protected $objectArgument;

    protected $mockPropertyMapper;

    protected $mockConfigurationBuilder;

    protected $mockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleValueArgument = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['dummy'], ['someName', 'string']);
        $this->objectArgument = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['dummy'], ['someName', 'DateTime']);
        $this->mockPropertyMapper = $this->createMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class);
        $this->simpleValueArgument->_set('propertyMapper', $this->mockPropertyMapper);
        $this->objectArgument->_set('propertyMapper', $this->mockPropertyMapper);
        $this->mockConfiguration = new \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration();
        $propertyMappingConfiguranion = new \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration();
        $this->simpleValueArgument->_set('propertyMappingConfiguration', $propertyMappingConfiguranion);
        $this->objectArgument->_set('propertyMappingConfiguration', $propertyMappingConfiguranion);
    }

    /**
     * @test
     */
    public function constructingArgumentWithoutNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1232551853);
        new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('', 'Text');
    }

    /**
     * @test
     */
    public function constructingArgumentWithInvalidNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1187951688);
        new \TYPO3\CMS\Extbase\Mvc\Controller\Argument(new \ArrayObject(), 'Text');
    }

    /**
     * @test
     */
    public function passingDataTypeToConstructorReallySetsTheDataType()
    {
        self::assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
        self::assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
    }

    /**
     * @test
     */
    public function setShortNameProvidesFluentInterface()
    {
        $returnedArgument = $this->simpleValueArgument->setShortName('x');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    /**
     * @return array
     */
    public function invalidShortNames()
    {
        return [
            [''],
            ['as'],
            [5]
        ];
    }

    /**
     * @test
     * @dataProvider invalidShortNames
     * @param string $invalidShortName
     */
    public function shortNameShouldThrowExceptionIfInvalid($invalidShortName)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1195824959);
        $this->simpleValueArgument->setShortName($invalidShortName);
    }

    /**
     * @test
     */
    public function shortNameCanBeRetrievedAgain()
    {
        $this->simpleValueArgument->setShortName('x');
        self::assertEquals('x', $this->simpleValueArgument->getShortName());
    }

    /**
     * @test
     */
    public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState()
    {
        $returnedArgument = $this->simpleValueArgument->setRequired(true);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertTrue($this->simpleValueArgument->isRequired());
    }

    /**
     * @test
     */
    public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue()
    {
        $returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame('default', $this->simpleValueArgument->getDefaultValue());
    }

    /**
     * @test
     */
    public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator()
    {
        $mockValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame($mockValidator, $this->simpleValueArgument->getValidator());
    }

    /**
     * @test
     */
    public function setValueProvidesFluentInterface()
    {
        $returnedArgument = $this->simpleValueArgument->setValue(null);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    /**
     * @test
     */
    public function setValueUsesNullAsIs()
    {
        $this->simpleValueArgument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'string');
        $this->simpleValueArgument = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['dummy'], ['dummy', 'string']);
        $this->simpleValueArgument->setValue(null);
        self::assertNull($this->simpleValueArgument->getValue());
    }

    /**
     * @test
     */
    public function setValueUsesMatchingInstanceAsIs()
    {
        $this->mockPropertyMapper->expects(self::never())->method('convert');
        $this->objectArgument->setValue(new \DateTime());
    }

    /**
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument $this
     */
    protected function setupPropertyMapperAndSetValue()
    {
        $this->mockPropertyMapper->expects(self::once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->willReturn('convertedValue');
        $this->mockPropertyMapper->expects(self::once())->method('getMessages')->willReturn(new \TYPO3\CMS\Extbase\Error\Result());
        return $this->simpleValueArgument->setValue('someRawValue');
    }

    /**
     * @test
     */
    public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue()
    {
        $this->setupPropertyMapperAndSetValue();
        self::assertSame('convertedValue', $this->simpleValueArgument->getValue());
        self::assertTrue($this->simpleValueArgument->isValid());
    }

    /**
     * @test
     */
    public function setValueShouldBeFluentInterface()
    {
        self::assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
    }

    /**
     * @test
     */
    public function setValueShouldSetValidationErrorsIfValidatorIsSetAndValidationFailed()
    {
        $error = new \TYPO3\CMS\Extbase\Error\Error('Some Error', 1234);
        $mockValidator = $this->getMockBuilder(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class)
            ->setMethods(['validate', 'getOptions'])
            ->getMock();
        $validationMessages = new \TYPO3\CMS\Extbase\Error\Result();
        $validationMessages->addError($error);
        $mockValidator->expects(self::once())->method('validate')->with('convertedValue')->willReturn($validationMessages);
        $this->simpleValueArgument->setValidator($mockValidator);
        $this->setupPropertyMapperAndSetValue();
        self::assertFalse($this->simpleValueArgument->isValid());
        self::assertEquals([$error], $this->simpleValueArgument->validate()->getErrors());
    }

    /**
     * @test
     */
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects()
    {
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
