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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Country\Country;
use TYPO3\CMS\Core\Country\Event\BeforeCountriesEvaluatedEvent;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler type=datetime handling
 */
final class DataHandlerCountryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler_country',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        // Use event to modify country list
        $container = $this->get('service_container');
        $container->set(
            'modify-country-provider-list',
            static function (BeforeCountriesEvaluatedEvent $event) use (&$modifyCountryProviderEvent) {
                $modifyCountryProviderEvent = $event;
                $countries = $modifyCountryProviderEvent->getCountries();
                // Add country XX
                $countries['XX'] = new Country('XX', 'XXX', 'Magic Kingdom', '12345', '🔮', 'Kingdom of Magic');
                // Remove country BB
                unset($countries['BB']);
                $modifyCountryProviderEvent->setCountries($countries);
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeCountriesEvaluatedEvent::class, 'modify-country-provider-list');

    }

    #[Test]
    #[DataProvider('setValuesProvider')]
    public function setValues(string $field, mixed $input, mixed $expected): void
    {
        $datamap['tx_testdatahandler_country']['NEW1234'] = [
            $field => $input,
            'pid' => 0,
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        $dataHandler->process_datamap();
        $uid = $dataHandler->substNEWwithIDs['NEW1234'];
        $newRecord = BackendUtility::getRecord('tx_testdatahandler_country', $uid);
        self::assertSame($expected, $newRecord[$field]);
    }

    public static function setValuesProvider(): \Generator
    {
        // Basic tests
        yield 'field "country", empty value' => [
            'field' => 'country',
            'input' => '',
            'expected' => '',
        ];
        yield 'field "country", valid country 1' => [
            'field' => 'country',
            'input' => 'DE',
            'expected' => 'DE',
        ];
        yield 'field "country", valid country 2' => [
            'field' => 'country',
            'input' => 'FR',
            'expected' => 'FR',
        ];
        yield 'field "country", valid country 3' => [
            'field' => 'country',
            'input' => 'HU',
            'expected' => 'HU',
        ];
        yield 'field "country", invalid country' => [
            'field' => 'country',
            'input' => 'ABCXYZ',
            'expected' => '',
        ];
        // filter only countries
        yield 'field "country_filter_onlycountries", allowed country 1' => [
            'field' => 'country_filter_onlycountries',
            'input' => 'DE',
            'expected' => 'DE',
        ];
        yield 'field "country_filter_onlycountries", allowed country 2' => [
            'field' => 'country_filter_onlycountries',
            'input' => 'FR',
            'expected' => 'FR',
        ];
        yield 'field "country_filter_onlycountries", allowed country 3' => [
            'field' => 'country_filter_onlycountries',
            'input' => 'IT',
            'expected' => 'IT',
        ];
        yield 'field "country_filter_onlycountries", not allowed country' => [
            'field' => 'country_filter_onlycountries',
            'input' => 'HU',
            'expected' => '',
        ];
        // nullable
        yield 'field "country_nullable", with null' => [
            'field' => 'country_nullable',
            'input' => null,
            'expected' => null,
        ];
        yield 'field "country_nullable", with country' => [
            'field' => 'country_nullable',
            'input' => 'DE',
            'expected' => 'DE',
        ];
        // default value
        yield 'field "country_default", with country' => [
            'field' => 'country_default',
            'input' => 'FR',
            'expected' => 'FR',
        ];

        // Respect excluded countries
        yield 'field "country_filter_excludeCountries", not excluded' => [
            'field' => 'country_filter_excludeCountries',
            'input' => 'HU',
            'expected' => 'HU',
        ];
        yield 'field "country_filter_excludeCountries", excluded' => [
            'field' => 'country_filter_excludeCountries',
            'input' => 'DE',
            'expected' => '',
        ];
        // Test both filters
        yield 'field "country_filter_both", allowed 1' => [
            'field' => 'country_filter_both',
            'input' => 'DE',
            'expected' => 'DE',
        ];
        yield 'field "country_filter_both", allowed 2' => [
            'field' => 'country_filter_both',
            'input' => 'IT',
            'expected' => 'IT',
        ];
        yield 'field "country_filter_both", not allowed 1' => [
            'field' => 'country_filter_both',
            'input' => 'FR',
            'expected' => '',
        ];
        yield 'field "country_filter_both", not allowed 2' => [
            'field' => 'country_filter_both',
            'input' => 'HU',
            'expected' => '',
        ];
        // event modification
        yield 'field "country", removed value BB' => [
            'field' => 'country',
            'input' => 'BB',
            'expected' => '',
        ];
        yield 'field "country", added value BB' => [
            'field' => 'country',
            'input' => 'XX',
            'expected' => 'XX',
        ];
        // Try to fix invalid countries
        yield 'field "country", with empty spaces before' => [
            'field' => 'country',
            'input' => ' DE',
            'expected' => 'DE',
        ];
        yield 'field "country", with empty spaces after' => [
            'field' => 'country',
            'input' => 'DE ',
            'expected' => 'DE',
        ];
        yield 'field "country", with empty spaces both' => [
            'field' => 'country',
            'input' => ' DE ',
            'expected' => 'DE',
        ];
        yield 'field "country", lowercase' => [
            'field' => 'country',
            'input' => 'de',
            'expected' => 'DE',
        ];
        yield 'field "country", mixed' => [
            'field' => 'country',
            'input' => 'De',
            'expected' => 'DE',
        ];
        yield 'field "country", mixed and empty spaces' => [
            'field' => 'country',
            'input' => ' De ',
            'expected' => 'DE',
        ];
    }

    #[Test]
    public function defaultValue(): void
    {
        $datamap['tx_testdatahandler_country']['NEW1234'] = [
            'pid' => 0,
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, []);
        $dataHandler->process_datamap();
        $uid = $dataHandler->substNEWwithIDs['NEW1234'];
        $newRecord = BackendUtility::getRecord('tx_testdatahandler_country', $uid);
        self::assertSame('DE', $newRecord['country_default']);
    }

}
