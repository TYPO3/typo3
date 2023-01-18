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

namespace TYPO3\CMS\Core\Tests\Functional\Country;

use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CountryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function countryLabelCanBeLocalized(): void
    {
        $languageServiceFactory = $this->get(LanguageServiceFactory::class);
        $languageService = $languageServiceFactory->create('de');
        $provider = $this->get('country.provider');
        $subject = $provider->getByIsoCode('FR');
        self::assertInstanceOf(Country::class, $subject);
        self::assertEquals('France', $languageService->sL($subject->getName()));
        self::assertEquals('Frankreich', $languageService->sL($subject->getLocalizedNameLabel()));
        self::assertEquals('Französische Republik', $languageService->sL($subject->getLocalizedOfficialNameLabel()));

        $subject = $provider->getByIsoCode('BEL');
        $languageService = $languageServiceFactory->create('fr');
        self::assertEquals('Belgique', $languageService->sL($subject->getLocalizedNameLabel()));
        self::assertEquals('Royaume de Belgique', $languageService->sL($subject->getLocalizedOfficialNameLabel()));
        self::assertEquals('Kingdom of Belgium', $subject->getOfficialName());
        self::assertEquals('Kingdom of Belgium', $languageService->sL($subject->getOfficialName()));
    }
}
