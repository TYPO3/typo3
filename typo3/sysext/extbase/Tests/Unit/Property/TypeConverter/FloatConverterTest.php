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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FloatConverterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function convertFromShouldCastTheStringToFloat(): void
    {
        self::assertSame(1.5, (new FloatConverter())->convertFrom('1.5', 'float'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfEmptyStringSpecified(): void
    {
        self::assertNull((new FloatConverter())->convertFrom('', 'float'));
    }

    /**
     * @test
     */
    public function convertFromShouldAcceptIntegers(): void
    {
        self::assertSame((float)123, (new FloatConverter())->convertFrom(123, 'float'));
    }

    /**
     * @test
     */
    public function convertFromShouldRespectConfiguration(): void
    {
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $series = [
            [FloatConverter::class, FloatConverter::CONFIGURATION_THOUSANDS_SEPARATOR, '.'],
            [FloatConverter::class, FloatConverter::CONFIGURATION_DECIMAL_POINT, ','],
        ];
        $mockMappingConfiguration
            ->expects(self::exactly(2))
            ->method('getConfigurationValue')
            ->willReturnCallback(function (string $class, string $key) use (&$series): string {
                $arguments = array_shift($series);
                self::assertSame($class, $arguments[0]);
                self::assertSame($key, $arguments[1]);
                return $arguments[2];
            });
        self::assertSame(1024.42, (new FloatConverter())->convertFrom('1.024,42', 'float', [], $mockMappingConfiguration));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric(): void
    {
        self::assertInstanceOf(Error::class, (new FloatConverter())->convertFrom('not numeric', 'float'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray(): void
    {
        self::assertEquals([], (new FloatConverter())->getSourceChildPropertiesToBeConverted('myString'));
    }
}
