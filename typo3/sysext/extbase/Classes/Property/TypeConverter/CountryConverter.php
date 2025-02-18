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

namespace TYPO3\CMS\Extbase\Property\TypeConverter;

use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;

/**
 * Converter which transforms simple types to a country object.
 */
class CountryConverter extends AbstractTypeConverter
{
    public const CONFIGURATION_FROM = 'alpha2IsoCode';

    protected CountryProvider $countryProvider;

    public function injectCountryProvider(CountryProvider $countryProvider): void
    {
        $this->countryProvider = $countryProvider;
    }

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param string $source
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ): ?Country {

        $by = self::CONFIGURATION_FROM;
        if ($configuration !== null) {
            $by = $configuration->getConfigurationValue(CountryConverter::class, self::CONFIGURATION_FROM);
        }
        return match ($by) {
            'alpha3IsoCode' => $this->countryProvider->getByAlpha3IsoCode((string)$source),
            default => $this->countryProvider->getByAlpha2IsoCode((string)$source),
        };
    }
}
