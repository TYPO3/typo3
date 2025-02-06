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

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler type=datetime handling
 */
final class DatetimeLegacyTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler_datetime_legacy',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = [
            'DB' => [
                'Connections' => [
                    'Default' => [
                        // Overwrite sql_mode to disable NO_ZERO_DATE
                        'initCommands' => 'SET SESSION sql_mode = \'' . implode(',', [
                            'STRICT_ALL_TABLES',
                            'ERROR_FOR_DIVISION_BY_ZERO',
                            'NO_AUTO_VALUE_ON_ZERO',
                            'NO_ENGINE_SUBSTITUTION',
                            // Disabled to allow "0000-00-00" values for DATE and DATETIME fields with legacy non-nullable config
                            //'NO_ZERO_DATE',
                            'NO_ZERO_IN_DATE',
                            'ONLY_FULL_GROUP_BY',
                        ]) . '\';',
                    ],
                ],
            ],
        ];

        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Group('not-postgres')]
    #[Group('not-sqlite')]
    #[DataProviderExternal(DatetimeTest::class, 'getDatetimeSets')]
    #[Test]
    public function createLegacyDatetimeRecords(
        string $resultSet,
        string $timezone,
        ?string $datetime,
        ?string $date,
        ?string $timesec,
        ?string $time,
        string $comment = ''
    ): void {
        $this->importCSVDataSet(__DIR__ . '/DataSet/Datetime/Legacy/Base.csv');

        $fields = [
            'datetime_native_notnull' => $datetime,
            'date_native_notnull' => $date,
            'timesec_native_notnull' => $timesec,
            'time_native_notnull' => $time,
        ];

        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'tx_testdatahandler_datetime_legacy' => [
                    'NEW-1' => [
                        'pid' => 2,
                        ...$fields,
                        'comment' => $comment,
                    ],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();
        date_default_timezone_set($oldTimezone);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/Datetime/Legacy/' . $resultSet . '.csv');
    }
}
