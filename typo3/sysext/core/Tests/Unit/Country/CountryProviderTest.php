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

namespace TYPO3\CMS\Core\Tests\Unit\Country;

use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CountryProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function findAllCountriesReturnsCountryObjects(): void
    {
        $subject = new CountryProvider();
        $countries = $subject->getAll();
        self::assertGreaterThan(150, count($countries));
    }

    /**
     * @test
     */
    public function findByIsoCodeReturnsValidObject(): void
    {
        $subject = new CountryProvider();
        $countryIsoCode2LowerCase = $subject->getByIsoCode('fr');
        $countryIsoCode2 = $subject->getByIsoCode('FR');
        self::assertInstanceOf(Country::class, $countryIsoCode2);
        self::assertSame($countryIsoCode2LowerCase, $countryIsoCode2);
        self::assertEquals('France', $countryIsoCode2->getName());
        self::assertEquals('French Republic', $countryIsoCode2->getOfficialName());
    }

    /**
     * @test
     */
    public function findByThreeLetterIsoCodeReturnsValidObject(): void
    {
        $subject = new CountryProvider();
        $countryIsoCode3LowerCase = $subject->getByIsoCode('fra');
        $countryIsoCode3 = $subject->getByIsoCode('FRA');
        $countryIsoCode2 = $subject->getByIsoCode('FR');
        self::assertInstanceOf(Country::class, $countryIsoCode2);
        self::assertSame($countryIsoCode3LowerCase, $countryIsoCode2);
        self::assertSame($countryIsoCode3, $countryIsoCode2);
        self::assertEquals('France', $countryIsoCode3->getName());
        self::assertEquals('🇫🇷', $countryIsoCode3->getFlag());
        self::assertEquals('French Republic', $countryIsoCode3->getOfficialName());
        self::assertEquals('250', $countryIsoCode3->getNumericRepresentation());
        self::assertEquals('FR', $countryIsoCode3->getAlpha2IsoCode());
        self::assertEquals('FRA', $countryIsoCode3->getAlpha3IsoCode());
    }
}
