<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FloatConverterTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new FloatConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        self::assertEquals(['float', 'integer', 'string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('float', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(10, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromShouldCastTheStringToFloat()
    {
        self::assertSame(1.5, $this->converter->convertFrom('1.5', 'float'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfEmptyStringSpecified()
    {
        self::assertNull($this->converter->convertFrom('', 'float'));
    }

    /**
     * @test
     */
    public function convertFromShouldAcceptIntegers()
    {
        self::assertSame((float)123, $this->converter->convertFrom(123, 'float'));
    }

    /**
     * @test
     */
    public function convertFromShouldRespectConfiguration()
    {
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $mockMappingConfiguration
            ->expects(self::at(0))
            ->method('getConfigurationValue')
            ->with(FloatConverter::class, FloatConverter::CONFIGURATION_THOUSANDS_SEPARATOR)
            ->willReturn('.');
        $mockMappingConfiguration
            ->expects(self::at(1))
            ->method('getConfigurationValue')
            ->with(FloatConverter::class, FloatConverter::CONFIGURATION_DECIMAL_POINT)
            ->willReturn(',');
        self::assertSame(1024.42, $this->converter->convertFrom('1.024,42', 'float', [], $mockMappingConfiguration));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric()
    {
        self::assertInstanceOf(Error::class, $this->converter->convertFrom('not numeric', 'float'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrue()
    {
        self::assertTrue($this->converter->canConvertFrom('1.5', 'float'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForAnEmptyValue()
    {
        self::assertTrue($this->converter->canConvertFrom('', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForANullValue()
    {
        self::assertTrue($this->converter->canConvertFrom(null, 'integer'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
