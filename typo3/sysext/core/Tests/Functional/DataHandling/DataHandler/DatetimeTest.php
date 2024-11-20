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
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
            'Default FormEngine server-client format: Fake UTC-0 (localtime!) on UTC Server' => [
                'resultSet' => 'Result',
                'timezone' => 'UTC',
                'datetime' => '2014-10-22T23:58:00Z',
                'date' => '2014-10-22T00:00:00Z',
                'timesec' => '1970-01-01T23:58:20Z',
                'time' => '1970-01-01T23:58:00Z',
            ],
            'Default FormEngine server-client format:fake UTC-0 (localtime!) on Europe/Berlin Server' => [
                'resultSet' => 'ResultBerlinFakeUTC0',
                'timezone' => 'Europe/Berlin',
                'datetime' => '2014-10-22T23:58:00Z',
                'date' => '2014-10-22T00:00:00Z',
                'timesec' => '1970-01-01T23:58:20Z',
                'time' => '1970-01-01T23:58:00Z',
            ],

            // HEADS UP: Localtime offsets are currently not supported official by DataHandler, this test shows *why* (it's broken)
            'ISO8601 localtime (currently unused!) on UTC Server' => [
                'resultSet' => 'Result',
                'timezone' => 'UTC',
                'datetime' => '2014-10-22T23:58:00',
                'date' => '2014-10-22T00:00:00',
                'timesec' => '1970-01-01T23:58:20',
                'time' => '1970-01-01T23:58:00',
            ],
            'ISO8601 localtime (currently unused!) on Europe/Berlin Server' => [
                'resultSet' => 'ResultBerlinLocaltime',
                'timezone' => 'Europe/Berlin',
                // @todo should be 1414015080, but is 1414007880 as localtime offset is currently cropped because it is differentiated from fake UTC0
                'datetime' => '2014-10-22T23:58:00',
                // @todo should be 1413928800, but is 1413921600
                'date' => '2014-10-22T00:00:00',
                // @todo should be 86300 for int fields, but is 82700, see `23*3600 + 58*60 + 20`
                'timesec' => '1970-01-01T23:58:20',
                // @todo should be 86280 for int fields, but is 82680, see `23*3600 + 58*60`
                'time' => '1970-01-01T23:58:00',
            ],

            'empty string on UTC' => [
                'resultSet' => 'ResultEmpty',
                'timezone' => 'UTC',
                'datetime' => '',
                'date' => '',
                'timesec' => '',
                'time' => '',
                'comment' => '@todo: Fix *_int_nullable and *_native fields to be NULL instead of 0',
            ],
            'empty string to null on Europe/Berlin' => [
                'resultSet' => 'ResultEmpty',
                'timezone' => 'Europe/Berlin',
                'datetime' => '',
                'date' => '',
                'timesec' => '',
                'time' => '',
                'comment' => '@todo: Fix *_int_nullable and *_native fields to be NULL instead of 0',
            ],
            'null to null on UTC' => [
                'resultSet' => 'ResultNull',
                'timezone' => 'UTC',
                'datetime' => null,
                'date' => null,
                'timesec' => null,
                'time' => null,
                'comment' => '@todo: Fix *_int_nullable and *_native fields to be NULL instead of 0',
            ],
            'null to null on Europe/Berlin' => [
                'resultSet' => 'ResultNull',
                'timezone' => 'Europe/Berlin',
                'datetime' => null,
                'date' => null,
                'timesec' => null,
                'time' => null,
                'comment' => '@todo: Fix *_int_nullable and *_native fields to be NULL instead of 0',
            ],
            'timezone offsets on UTC' => [
                'resultSet' => 'ResultOffsetUTC',
                'timezone' => 'UTC',
                'datetime' => '2014-10-22T23:58:00+02:00',
                'date' => '2014-10-22T00:00:00+02:00',
                'timesec' => '1970-01-01T23:58:20+02:00',
                'time' => '1970-01-01T23:58:00+02:00',
                'comment' => '@todo: Fix offsets to *not* be cropped',
            ],
            // HEADS UP: Timezone offsets are currently not supported official by DataHandler, this test shows *why* (it's broken)
            'timezone offsets are cropped on Europe/Berlin' => [
                'resultSet' => 'ResultOffsetBerlin',
                'timezone' => 'Europe/Berlin',
                // @todo: should be 1414015080 for int fields, but is 1414007880, see `date --date=2014-10-22T23:58:00+02:00 +%s`
                // @todo: should be "2014-10-22 23:58:00" for native fields, but is "2014-10-22 21:58:00"
                'datetime' => '2014-10-22T23:58:00+02:00',
                // @todo: should be 1413928800 for int fields, but is 1413921600, see `date --date=2014-10-22T00:00:00+02:00 +%s`
                // @todo: should be "2014-10-21" for native fields, but is "2014-10-21"
                'date' => '2014-10-22T00:00:00+02:00',
                // @todo should be 86300 for int fields, but is 79100, see `23*3600 + 58*60 + 20`
                // @todo should be "23:58:20", but is "21:58:20"
                'timesec' => '1970-01-01T23:58:20+02:00',
                // @todo should be 86280 for int fields, but is 79080, see `23*3600 + 58*60`
                // @todo should be "23:58:00", but is "21:58:00"
                'time' => '1970-01-01T23:58:00+02:00',
                'comment' => '@todo: Fix offsets to *not* be cropped',
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
        string $comment = ''
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

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'tx_testdatahandler_datetime' => [
                    'NEW-1' => [
                        'pid' => 2,
                        ...$fields,
                        'comment' => $comment,
                    ],
                ],
            ],
            [],
        );
        $res = $dataHandler->process_datamap();
        date_default_timezone_set($oldTimezone);

        self::assertNotFalse($res);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/Datetime/' . $resultSet . '.csv');
    }
}
