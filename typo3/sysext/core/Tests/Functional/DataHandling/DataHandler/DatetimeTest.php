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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler type=datetime handling
 */
final class DatetimeTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler_datetime',
    ];

    protected function setUp(): void
    {
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

    public static function getDatetimeSets(): array
    {
        return [
            'UTC0 on UTC Server' => [
                'resultSet' => 'Result',
                'timezone' => 'UTC',
                'datetime' => '2014-10-22T23:58:00Z',
                'date' => '2014-10-22T00:00:00Z',
                'timesec' => '1970-01-01T23:58:20Z',
                'time' => '1970-01-01T23:58:00Z',
            ],
            'UTC0 on Europe/Berlin Server' => [
                'resultSet' => 'ResultUTC0Berlin',
                'timezone' => 'Europe/Berlin',
                'datetime' => '2014-10-22T23:58:00Z',
                'date' => '2014-10-22T00:00:00Z',
                'timesec' => '1970-01-01T23:58:20Z',
                'time' => '1970-01-01T23:58:00Z',
            ],

            'ISO8601 localtime (Formengine server-client format) on UTC Server' => [
                'resultSet' => 'Result',
                'timezone' => 'UTC',
                'datetime' => '2014-10-22T23:58:00',
                'date' => '2014-10-22T00:00:00',
                'timesec' => '1970-01-01T23:58:20',
                'time' => '1970-01-01T23:58:00',
            ],
            'ISO8601 localtime (Formengine server-client format) on Europe/Berlin Server' => [
                'resultSet' => 'ResultBerlinLocaltime',
                'timezone' => 'Europe/Berlin',
                'datetime' => '2014-10-22T23:58:00',
                'date' => '2014-10-22T00:00:00',
                'timesec' => '1970-01-01T23:58:20',
                'time' => '1970-01-01T23:58:00',
            ],

            'empty string on UTC' => [
                'resultSet' => 'ResultEmpty',
                'timezone' => 'UTC',
                'datetime' => '',
                'date' => '',
                'timesec' => '',
                'time' => '',
            ],
            'empty string to null on Europe/Berlin' => [
                'resultSet' => 'ResultEmpty',
                'timezone' => 'Europe/Berlin',
                'datetime' => '',
                'date' => '',
                'timesec' => '',
                'time' => '',
            ],
            'null to null on UTC' => [
                'resultSet' => 'ResultNull',
                'timezone' => 'UTC',
                'datetime' => null,
                'date' => null,
                'timesec' => null,
                'time' => null,
            ],
            'null to null on Europe/Berlin' => [
                'resultSet' => 'ResultNull',
                'timezone' => 'Europe/Berlin',
                'datetime' => null,
                'date' => null,
                'timesec' => null,
                'time' => null,
            ],
            'timezone offsets on UTC' => [
                'resultSet' => 'ResultOffsetUTC',
                'timezone' => 'UTC',
                'datetime' => '2014-10-22T23:58:00+02:00',
                'date' => '2014-10-22T00:00:00+02:00',
                'timesec' => '1970-01-01T23:58:20+02:00',
                'time' => '1970-01-01T23:58:00+02:00',
            ],
            'timezone offsets on Europe/Berlin' => [
                'resultSet' => 'ResultOffsetBerlin',
                'timezone' => 'Europe/Berlin',
                'datetime' => '2014-10-22T23:58:00+02:00',
                'date' => '2014-10-22T00:00:00+02:00',
                'timesec' => '1970-01-01T23:58:20+02:00',
                'time' => '1970-01-01T23:58:00+02:00',
            ],
        ];
    }

    #[DataProvider('getDatetimeSets')]
    #[Test]
    public function createDatetimeRecords(
        string $resultSet,
        string $timezone,
        ?string $datetime,
        ?string $date,
        ?string $timesec,
        ?string $time,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/DataSet/Datetime/Base.csv');

        $fields = [
            'datetime_int' => $datetime,
            'datetime_int_nullable' => $datetime,
            'datetime_native' => $datetime,

            'date_int' => $date,
            'date_int_nullable' => $date,
            'date_native' => $date,

            'timesec_int' => $timesec,
            'timesec_int_nullable' => $timesec,
            'timesec_native' => $timesec,

            'time_int' => $time,
            'time_int_nullable' => $time,
            'time_native' => $time,
        ];

        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'tx_testdatahandler_datetime' => [
                    'NEW-1' => [
                        'pid' => 2,
                        ...$fields,
                    ],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();
        date_default_timezone_set($oldTimezone);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/Datetime/' . $resultSet . '.csv');
    }

    public static function getDatetimeObjectSets(): array
    {
        return [
            'UTC0 on UTC Server' => [
                'resultSet' => 'Result',
                'timezone' => 'UTC',
                'datetime' => new \DateTimeImmutable('2014-10-22T23:58:00Z'),
                'date' => new \DateTimeImmutable('2014-10-22T00:00:00Z'),
                'timesec' => new \DateTimeImmutable('1970-01-01T23:58:20Z'),
                'time' => new \DateTimeImmutable('1970-01-01T23:58:00Z'),
            ],
            'UTC0 on Europe/Berlin Server' => [
                'resultSet' => 'ResultUTC0Berlin',
                'timezone' => 'Europe/Berlin',
                'datetime' => new \DateTimeImmutable('2014-10-22T23:58:00Z'),
                'date' => new \DateTimeImmutable('2014-10-22T00:00:00Z'),
                'timesec' => new \DateTimeImmutable('1970-01-01T23:58:20Z'),
                'time' => new \DateTimeImmutable('1970-01-01T23:58:00Z'),
            ],

            'ISO8601 localtime (Formengine server-client format) on UTC Server' => [
                'resultSet' => 'Result',
                'timezone' => 'UTC',
                'datetime' => new \DateTimeImmutable('2014-10-22T23:58:00'),
                'date' => new \DateTimeImmutable('2014-10-22T00:00:00'),
                'timesec' => new \DateTimeImmutable('1970-01-01T23:58:20'),
                'time' => new \DateTimeImmutable('1970-01-01T23:58:00'),
            ],
            'ISO8601 localtime (Formengine server-client format) on Europe/Berlin Server' => [
                'resultSet' => 'ResultBerlinLocaltime',
                'timezone' => 'Europe/Berlin',
                'datetime' => new \DateTimeImmutable('2014-10-22T23:58:00', new \DateTimeZone('Europe/Berlin')),
                'date' => new \DateTimeImmutable('2014-10-22T00:00:00', new \DateTimeZone('Europe/Berlin')),
                'timesec' => new \DateTimeImmutable('1970-01-01T23:58:20', new \DateTimeZone('Europe/Berlin')),
                'time' => new \DateTimeImmutable('1970-01-01T23:58:00', new \DateTimeZone('Europe/Berlin')),
            ],

            'null to null on UTC' => [
                'resultSet' => 'ResultNull',
                'timezone' => 'UTC',
                'datetime' => null,
                'date' => null,
                'timesec' => null,
                'time' => null,
            ],
            'null to null on Europe/Berlin' => [
                'resultSet' => 'ResultNull',
                'timezone' => 'Europe/Berlin',
                'datetime' => null,
                'date' => null,
                'timesec' => null,
                'time' => null,
            ],
            'timezone offsets on UTC' => [
                'resultSet' => 'ResultOffsetUTC',
                'timezone' => 'UTC',
                'datetime' => new \DateTimeImmutable('2014-10-22T23:58:00+02:00'),
                'date' => new \DateTimeImmutable('2014-10-22T00:00:00+02:00'),
                'timesec' => new \DateTimeImmutable('1970-01-01T23:58:20+02:00'),
                'time' => new \DateTimeImmutable('1970-01-01T23:58:00+02:00'),
            ],
            'timezone offsets on Europe/Berlin' => [
                'resultSet' => 'ResultOffsetBerlin',
                'timezone' => 'Europe/Berlin',
                'datetime' => new \DateTimeImmutable('2014-10-22T23:58:00+02:00'),
                'date' => new \DateTimeImmutable('2014-10-22T00:00:00+02:00'),
                'timesec' => new \DateTimeImmutable('1970-01-01T23:58:20+02:00'),
                'time' => new \DateTimeImmutable('1970-01-01T23:58:00+02:00'),
            ],
        ];
    }

    #[DataProvider('getDatetimeObjectSets')]
    #[Test]
    public function createDatetimeObjectRecords(
        string $resultSet,
        string $timezone,
        ?\DateTimeInterface $datetime,
        ?\DateTimeInterface $date,
        ?\DateTimeInterface $timesec,
        ?\DateTimeInterface $time,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/DataSet/Datetime/Base.csv');

        $fields = [
            'datetime_int' => $datetime,
            'datetime_int_nullable' => $datetime,
            'datetime_native' => $datetime,

            'date_int' => $date,
            'date_int_nullable' => $date,
            'date_native' => $date,

            'timesec_int' => $timesec,
            'timesec_int_nullable' => $timesec,
            'timesec_native' => $timesec,

            'time_int' => $time,
            'time_int_nullable' => $time,
            'time_native' => $time,
        ];

        $oldTimezone = date_default_timezone_get();
        date_default_timezone_set($timezone);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'tx_testdatahandler_datetime' => [
                    'NEW-1' => [
                        'pid' => 2,
                        ...$fields,
                    ],
                ],
            ],
            [],
        );
        $dataHandler->process_datamap();
        date_default_timezone_set($oldTimezone);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/Datetime/' . $resultSet . '.csv');
    }
}
