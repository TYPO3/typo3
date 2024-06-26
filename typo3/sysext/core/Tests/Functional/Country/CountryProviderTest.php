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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\Country\Event\BeforeCountriesEvaluatedEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CountryProviderTest extends FunctionalTestCase
{
    #[Test]
    public function modifyRecordsAfterFetchingContentEventIsCalled(): void
    {
        $modifyCountryProviderEvent = null;
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-country-provider-list',
            static function (BeforeCountriesEvaluatedEvent $event) use (&$modifyCountryProviderEvent) {
                $modifyCountryProviderEvent = $event;
                $countries = $modifyCountryProviderEvent->getCountries();
                $countries['XX'] = new Country('XX', 'XXX', 'Magic Kingdom', '12345', '🔮', 'Kingdom of Magic');
                $modifyCountryProviderEvent->setCountries($countries);
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeCountriesEvaluatedEvent::class, 'modify-country-provider-list');
        $countryProvider = $this->get(CountryProvider::class);
        $country = $countryProvider->getByAlpha2IsoCode('XX');
        self::assertEquals($country->getName(), 'Magic Kingdom');
    }
}
