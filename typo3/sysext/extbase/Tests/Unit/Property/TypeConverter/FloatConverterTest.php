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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FloatConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
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
    public function convertFromShouldCastTheStringToFloat(): void
    {
        self::assertSame(1.5, $this->converter->convertFrom('1.5', 'float'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfEmptyStringSpecified(): void
    {
        self::assertNull($this->converter->convertFrom('', 'float'));
    }

    /**
     * @test
     */
    public function convertFromShouldAcceptIntegers(): void
    {
        self::assertSame((float)123, $this->converter->convertFrom(123, 'float'));
    }

    /**
     * @test
     */
    public function convertFromShouldRespectConfiguration(): void
    {
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $mockMappingConfiguration
            ->expects(self::exactly(2))
            ->method('getConfigurationValue')
            ->withConsecutive(
                [FloatConverter::class, FloatConverter::CONFIGURATION_THOUSANDS_SEPARATOR],
                [FloatConverter::class, FloatConverter::CONFIGURATION_DECIMAL_POINT]
            )
            ->willReturnOnConsecutiveCalls('.', ',');
        self::assertSame(1024.42, $this->converter->convertFrom('1.024,42', 'float', [], $mockMappingConfiguration));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric(): void
    {
        self::assertInstanceOf(Error::class, $this->converter->convertFrom('not numeric', 'float'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray(): void
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
