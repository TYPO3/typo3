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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ArgumentTest extends UnitTestCase
{
    protected Argument $simpleValueArgument;
    protected Argument $objectArgument;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleValueArgument = new Argument('someName', 'string');
        $this->objectArgument = new Argument('someName', 'DateTime');
    }

    /**
     * @test
     */
    public function constructingArgumentWithoutNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1232551853);
        new Argument('', 'Text');
    }

    /**
     * @test
     */
    public function constructingArgumentWithInvalidNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1187951688);
        new Argument(new \ArrayObject(), 'Text');
    }

    /**
     * @test
     */
    public function passingDataTypeToConstructorReallySetsTheDataType(): void
    {
        self::assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
        self::assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
    }

    /**
     * @test
     */
    public function setShortNameProvidesFluentInterface(): void
    {
        $returnedArgument = $this->simpleValueArgument->setShortName('x');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    /**
     * @return array
     */
    public function invalidShortNames(): array
    {
        return [
            [''],
            ['as'],
            [5],
        ];
    }

    /**
     * @test
     * @dataProvider invalidShortNames
     * @param string|int $invalidShortName
     */
    public function shortNameShouldThrowExceptionIfInvalid($invalidShortName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1195824959);
        $this->simpleValueArgument->setShortName($invalidShortName);
    }

    /**
     * @test
     */
    public function shortNameCanBeRetrievedAgain(): void
    {
        $this->simpleValueArgument->setShortName('x');
        self::assertEquals('x', $this->simpleValueArgument->getShortName());
    }

    /**
     * @test
     */
    public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState(): void
    {
        $returnedArgument = $this->simpleValueArgument->setRequired(true);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertTrue($this->simpleValueArgument->isRequired());
    }

    /**
     * @test
     */
    public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue(): void
    {
        $returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame('default', $this->simpleValueArgument->getDefaultValue());
    }

    /**
     * @test
     */
    public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator(): void
    {
        $mockValidator = $this->createMock(ValidatorInterface::class);
        $returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame($mockValidator, $this->simpleValueArgument->getValidator());
    }

    /**
     * @test
     */
    public function setValueProvidesFluentInterface(): void
    {
        $returnedArgument = $this->simpleValueArgument->setValue(null);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    /**
     * @test
     */
    public function setValueUsesNullAsIs(): void
    {
        $this->simpleValueArgument = new Argument('dummy', 'string');
        $this->simpleValueArgument->setValue(null);
        self::assertNull($this->simpleValueArgument->getValue());
    }

    /**
     * @test
     */
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects(): void
    {
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
