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

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\CountryConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CountryConverterTest extends UnitTestCase
{
    protected CountryConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new CountryConverter();
        $this->converter->injectCountryProvider(new CountryProvider($this->createMock(EventDispatcherInterface::class)));
    }

    #[Test]
    public function convertValidIso2ShouldReturnValidOutput(): void
    {
        $country = $this->converter->convertFrom('AT', Country::class);
        self::assertEquals('AUT', $country->getAlpha3IsoCode());
    }

    #[Test]
    public function convertValidIso3ShouldReturnValidOutput(): void
    {
        $mockMappingConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
        $mockMappingConfiguration
            ->expects(self::atLeastOnce())
            ->method('getConfigurationValue')
            ->with(CountryConverter::class, CountryConverter::CONFIGURATION_FROM)
            ->willReturn('alpha3IsoCode');
        $country = $this->converter->convertFrom('TJK', Country::class, [], $mockMappingConfiguration);
        self::assertEquals('Tajikistan', $country->getName());
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray(): void
    {
        $country = $this->converter->convertFrom('XYZ', Country::class);
        self::assertNull($country);
    }
}
