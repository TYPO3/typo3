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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=country fields
 *
 * @internal
 */
final class TypeCountry extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'country',
            ],
        ],
    ];

    public function __construct(
        private readonly CountryProvider $countryProvider,
    ) {}

    public function generate(array $data): string
    {
        $availableCountries = $this->countryProvider->getAll();
        // Pick a country card, any country card.
        return array_rand($availableCountries, 1);
    }
}
