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

namespace TYPO3\CMS\Backend\Tests\Functional\Utility;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\PageTsConfig;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendUtilityTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected BackendUserAuthentication $backendUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ]
        );
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
    }

    #[Test]
    public function givenPageIdCanBeExpanded(): void
    {
        $this->backendUser->groupData['webmounts'] = '1';

        BackendUtility::openPageTree(5, false);

        $expectedSiteHash = [
            '1_5' => '1',
            '1_1' => '1',
            '1_0' => '1',
        ];
        $actualSiteHash = $this->backendUser->uc['BackendComponents']['States']['Pagetree']['stateHash'];
        self::assertSame($expectedSiteHash, $actualSiteHash);
    }

    #[Test]
    public function otherBranchesCanBeClosedWhenOpeningPage(): void
    {
        $this->backendUser->groupData['webmounts'] = '1';

        BackendUtility::openPageTree(5, false);
        BackendUtility::openPageTree(4, true);

        //the complete branch of uid => 5 should be closed here
        $expectedSiteHash = [
            '1_4' => '1',
            '1_3' => '1',
            '1_2' => '1',
            '1_1' => '1',
            '1_0' => '1',
        ];
        $actualSiteHash = $this->backendUser->uc['BackendComponents']['States']['Pagetree']['stateHash'];
        self::assertSame($expectedSiteHash, $actualSiteHash);
    }

    #[Test]
    public function getProcessedValueForLanguage(): void
    {
        self::assertEquals(
            'Dansk',
            BackendUtility::getProcessedValue(
                'pages',
                'sys_language_uid',
                '1',
                0,
                false,
                false,
                1
            )
        );

        self::assertEquals(
            'German',
            BackendUtility::getProcessedValue(
                'tt_content',
                'sys_language_uid',
                '2',
                0,
                false,
                false,
                1
            )
        );
    }

    #[Test]
    public function getRecordTitleForUidLabel(): void
    {
        $GLOBALS['TCA']['tt_content']['ctrl']['label'] = 'uid';
        unset($GLOBALS['TCA']['tt_content']['ctrl']['label_alt']);
        $this->get(TcaSchemaFactory::class)->load($GLOBALS['TCA'], true);

        self::assertEquals(
            '1',
            BackendUtility::getRecordTitle('tt_content', BackendUtility::getRecord('tt_content', 1))
        );
    }

    public static function enableFieldsStatementIsCorrectDataProvider(): array
    {
        // Expected sql should contain identifier escaped in mysql/mariadb identifier quotings "`", which are
        // replaced by corresponding quoting values for other database systems.
        return [
            'disabled' => [
                [
                    'disabled' => 'disabled',
                ],
                false,
                ' AND `${tableName}`.`disabled` = 0',
            ],
            'starttime' => [
                [
                    'starttime' => 'starttime',
                ],
                false,
                ' AND `${tableName}`.`starttime` <= 1234567890',
            ],
            'endtime' => [
                [
                    'endtime' => 'endtime',
                ],
                false,
                ' AND ((`${tableName}`.`endtime` = 0) OR (`${tableName}`.`endtime` > 1234567890))',
            ],
            'disabled, starttime, endtime' => [
                [
                    'disabled' => 'disabled',
                    'starttime' => 'starttime',
                    'endtime' => 'endtime',
                ],
                false,
                ' AND ((`${tableName}`.`disabled` = 0) AND (`${tableName}`.`starttime` <= 1234567890) AND (((`${tableName}`.`endtime` = 0) OR (`${tableName}`.`endtime` > 1234567890))))',
            ],
            'disabled inverted' => [
                [
                    'disabled' => 'disabled',
                ],
                true,
                ' AND `${tableName}`.`disabled` <> 0',
            ],
            'starttime inverted' => [
                [
                    'starttime' => 'starttime',
                ],
                true,
                ' AND ((`${tableName}`.`starttime` <> 0) AND (`${tableName}`.`starttime` > 1234567890))',
            ],
            'endtime inverted' => [
                [
                    'endtime' => 'endtime',
                ],
                true,
                ' AND ((`${tableName}`.`endtime` <> 0) AND (`${tableName}`.`endtime` <= 1234567890))',
            ],
            'disabled, starttime, endtime inverted' => [
                [
                    'disabled' => 'disabled',
                    'starttime' => 'starttime',
                    'endtime' => 'endtime',
                ],
                true,
                ' AND ((`${tableName}`.`disabled` <> 0) OR (((`${tableName}`.`starttime` <> 0) AND (`${tableName}`.`starttime` > 1234567890))) OR (((`${tableName}`.`endtime` <> 0) AND (`${tableName}`.`endtime` <= 1234567890))))',
            ],
        ];
    }

    #[DataProvider('enableFieldsStatementIsCorrectDataProvider')]
    #[Test]
    public function enableFieldsStatementIsCorrect(array $enableColumns, bool $inverted, string $expectation): void
    {
        $platform = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->getDatabasePlatform();
        $tableName = uniqid('table');
        $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'] = $enableColumns;
        foreach ($enableColumns as $column) {
            $GLOBALS['TCA'][$tableName]['columns'][$column]['config']['type'] = 'check';
        }
        $this->get(TcaSchemaFactory::class)->load($GLOBALS['TCA'], true);
        $GLOBALS['SIM_ACCESS_TIME'] = 1234567890;
        $statement = BackendUtility::BEenableFields($tableName, $inverted);
        $replaces = [
            '${tableName}' => $tableName,
        ];
        // replace mysql identifier quoting with sqlite identifier quoting in expected sql string
        if ($platform instanceof DoctrineSQLitePlatform || $platform instanceof DoctrinePostgreSQLPlatform) {
            $replaces['`'] = '"';
        }
        $expectation = str_replace(array_keys($replaces), array_values($replaces), $expectation);
        self::assertSame($expectation, $statement);
    }

    #[Test]
    public function getRecordWithLargeUidDoesNotFail(): void
    {
        self::assertNull(BackendUtility::getRecord('tt_content', 9234567890111));
    }

    #[Test]
    public function getRecordWithNegativeUidDoesNotFail(): void
    {
        self::assertNull(BackendUtility::getRecord('tt_content', -42));
    }

    #[Test]
    public function getRecordWithNonExistentUidReturnsNull(): void
    {
        self::assertNull(BackendUtility::getRecord('tt_content', 99));
    }

    #[Test]
    public function getRecordWithExistingUidDoesNotReturnNull(): void
    {
        self::assertNotNull(BackendUtility::getRecord('tt_content', 1));
    }

    #[Test]
    public function pageTSconfigWorksCorrectly(): void
    {
        // root page: some_property set in TSconfig
        $ts = BackendUtility::getPagesTSconfig(1);
        self::assertSame('0', $ts['some_property']);

        // sub page: inherited from root page
        $ts = BackendUtility::getPagesTSconfig(2);
        self::assertSame('0', $ts['some_property']);

        // sub page with overridden TSconfig
        $ts = BackendUtility::getPagesTSconfig(5);
        self::assertSame('5', $ts['some_property']);

        // sub page with inherited conditional property
        $ts = BackendUtility::getPagesTSconfig(6);
        self::assertSame('6', $ts['some_property']);
    }

    #[Test]
    public function pageTSconfigCacheWorks(): void
    {
        /** @var FrontendInterface $cache */
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');

        BackendUtility::getPagesTSconfig(1);
        $cacheKey1 = $cache->get('pageTsConfig-pid-to-hash-1');
        self::assertIsString($cacheKey1);
        self::assertNotSame('', $cacheKey1);
        $cacheObject1 = $cache->get('pageTsConfig-hash-to-object-' . $cacheKey1);
        self::assertInstanceOf(PageTsConfig::class, $cacheObject1);

        BackendUtility::getPagesTSconfig(2);
        $cacheKey2 = $cache->get('pageTsConfig-pid-to-hash-2');
        self::assertIsString($cacheKey2);
        self::assertNotSame('', $cacheKey2);
        $cacheObject2 = $cache->get('pageTsConfig-hash-to-object-' . $cacheKey2);
        self::assertInstanceOf(PageTsConfig::class, $cacheObject2);

        self::assertSame($cacheKey1, $cacheKey2, 'Cache keys should be the same for page 1 and 2');
        self::assertSame(
            $cacheObject1->getPageTsConfigArray(),
            $cacheObject2->getPageTsConfigArray(),
            'TSconfig should be the same for page 1 and 2'
        );
        self::assertSame(
            $cacheObject1->getConditionListWithVerdicts(),
            $cacheObject2->getConditionListWithVerdicts(),
            'TSconfig conditions should be the same for page 1 and 2'
        );

        BackendUtility::getPagesTSconfig(6);
        $cacheKey6 = $cache->get('pageTsConfig-pid-to-hash-6');
        self::assertIsString($cacheKey6);
        self::assertNotSame('', $cacheKey6);
        $cacheObject6 = $cache->get('pageTsConfig-hash-to-object-' . $cacheKey6);
        self::assertInstanceOf(PageTsConfig::class, $cacheObject6);

        self::assertNotSame($cacheKey2, $cacheKey6);
        self::assertNotSame(
            $cacheObject2->getConditionListWithVerdicts(),
            $cacheObject6->getConditionListWithVerdicts(),
            'the tree.rootLineIds condition should lead to a different hash for page 6'
        );
    }

    #[Test]
    public function getProcessedValueForGroupWithOneAllowedTable(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->insert('pages', [
            'uid' => 10001,
            'pid' => 0,
            'title' => 'Page 1',
            'deleted' => 0,
            'hidden' => 0,
        ]);
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->insert('pages', [
            'uid' => 10002,
            'pid' => 0,
            'title' => 'Page 2',
            'deleted' => 0,
            'hidden' => 0,
        ]);

        $GLOBALS['TCA']['tt_content']['columns']['pages'] = [
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'maxitems' => 22,
                'size' => 3,
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $result = BackendUtility::getProcessedValue('tt_content', 'pages', '10001,10002');
        self::assertSame('Page 1, Page 2', $result);
    }

    #[Test]
    public function getProcessedValueForGroupWithMultipleAllowedTables(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->insert('pages', [
            'uid' => 10003,
            'pid' => 0,
            'title' => 'Page 1',
            'deleted' => 0,
            'hidden' => 0,
        ]);

        $GLOBALS['TCA']['index_config'] = [
            'ctrl' => [
                'label' => 'title',
            ],
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
                'indexcfgs' => [
                    'config' => [
                        'type' => 'group',
                        'allowed' => 'index_config,pages',
                        'size' => 5,
                    ],
                ],
            ],
        ];
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('pages');
        $connection->executeStatement('CREATE TABLE IF NOT EXISTS index_config (uid int PRIMARY KEY, pid int, title varchar(255))');
        $connection->executeStatement("INSERT INTO index_config (uid, pid, title) VALUES (10004, 0, 'Configuration 2')");
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $result = BackendUtility::getProcessedValue('index_config', 'indexcfgs', 'pages_10003,index_config_10004');
        self::assertSame('Page 1, Configuration 2', $result);

        $connection->executeStatement('DROP TABLE IF EXISTS index_config');
    }

    #[Test]
    public function getProcessedValueForSelectWithMMRelation(): void
    {
        $GLOBALS['TCA']['pages']['columns']['categories'] = [
            'config' => [
                'type' => 'category',
            ],
        ];
        $GLOBALS['TCA']['sys_category']['ctrl']['label'] = 'title';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $this->get(ConnectionPool::class)->getConnectionForTable('pages')->insert('pages', [
            'uid' => 10007,
            'pid' => 0,
            'title' => 'Test Page',
            'deleted' => 0,
            'hidden' => 0,
        ]);

        $this->get(ConnectionPool::class)->getConnectionForTable('sys_category')->insert('sys_category', [
            'uid' => 10005,
            'pid' => 0,
            'title' => 'Category 1',
            'deleted' => 0,
            'hidden' => 0,
        ]);
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_category')->insert('sys_category', [
            'uid' => 10006,
            'pid' => 0,
            'title' => 'Category 2',
            'deleted' => 0,
            'hidden' => 0,
        ]);

        $this->get(ConnectionPool::class)->getConnectionForTable('sys_category_record_mm')->insert('sys_category_record_mm', [
            'uid_local' => 10005,
            'uid_foreign' => 10007,
            'tablenames' => 'pages',
            'fieldname' => 'categories',
            'sorting' => 1,
        ]);
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_category_record_mm')->insert('sys_category_record_mm', [
            'uid_local' => 10006,
            'uid_foreign' => 10007,
            'tablenames' => 'pages',
            'fieldname' => 'categories',
            'sorting' => 2,
        ]);

        $result = BackendUtility::getProcessedValue('pages', 'categories', '10005,10006', 10007);
        self::assertNotEmpty($result);
    }

    #[Test]
    public function workspaceOLDoesNotChangeValuesForNoBeUserAvailable(): void
    {
        $tableName = 'table_a';
        $row = [
            'uid' => 1,
            'pid' => 17,
        ];
        $expected = $row;

        BackendUtility::workspaceOL($tableName, $row);
        self::assertSame($expected, $row);
    }

    #[Test]
    public function getAllowedFieldsForTableReturnsUniqueList(): void
    {
        $GLOBALS['TCA']['myTable'] = [
            'ctrl' => [
                'tstamp' => 'updatedon',
                // Won't be added due to defined in "columns"
                'crdate' => 'createdon',
                'sortby' => 'sorting',
                'versioningWS' => true,
            ],
            'columns' => [
                // Regular field
                'title' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
                // Overwrite automatically set management field from "ctrl"
                'createdon' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
                // Won't be added due to type "none"
                'reference' => [
                    'config' => [
                        'type' => 'none',
                    ],
                ],
            ],
        ];

        // Rebuild TCA schema
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertEquals(
            ['title', 'createdon', 'uid', 'pid', 'updatedon', 'sorting', 't3ver_state', 't3ver_stage', 't3ver_wsid', 't3ver_oid'],
            BackendUtility::getAllowedFieldsForTable('myTable', false)
        );
    }

    #[Test]
    public function getProcessedValueForZeroStringIsZero(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'header' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertEquals('0', BackendUtility::getProcessedValue('test_table', 'header', '0'));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDateNull(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'header' => [
                    'config' => [
                        'type' => 'datetime',
                        'dbType' => 'date',
                        'format' => 'date',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame('', BackendUtility::getProcessedValue('test_table', 'header', null));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDatetime(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'header' => [
                    'config' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $value = '2022-09-23 00:03:00';
        $expected = BackendUtility::datetime((int)strtotime($value));
        self::assertSame($expected, BackendUtility::getProcessedValue('test_table', 'header', $value));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDatetimeNull(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'header' => [
                    'config' => [
                        'type' => 'datetime',
                        'dbType' => 'datetime',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame('', BackendUtility::getProcessedValue('test_table', 'header', null));
    }

    #[Test]
    public function getProcessedValueForDatetimeDbTypeDate(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'header' => [
                    'config' => [
                        'type' => 'datetime',
                        'format' => 'date',
                        'dbType' => 'date',
                        'disableAgeDisplay' => true,
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $value = '2022-09-23';
        $expected = BackendUtility::date((int)strtotime($value));
        self::assertSame($expected, BackendUtility::getProcessedValue('test_table', 'header', $value));
    }

    /**
     * @todo This is so wrong ...
     */
    #[Test]
    public function getProcessedValueForFlex(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'pi_flexform' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => [
                            'default' => '
                                <T3FlexForms>
                                    <sheets type="array">
                                        <sDEF type="array">
                                            <ROOT type="array">
                                                <type>array</type>
                                                <el type="array">
                                                    <field index="foo" type="array">
                                                        <label>foo</label>
                                                        <config>
                                                            <type>input</type>
                                                        </config>
                                                    </field>
                                                </el>
                                            </ROOT>
                                        </sDEF>
                                    </sheets>
                                </T3FlexForms>',
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $expectation = "\n"
            . "\n    "
            . "\n        "
            . "\n            "
            . "\n                "
            . "\n                    bar"
            . "\n                "
            . "\n            "
            . "\n        "
            . "\n    "
            . "\n";

        self::assertSame($expectation, BackendUtility::getProcessedValue('test_table', 'pi_flexform', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="foo">
                    <value index="vDEF">bar</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>'));
    }

    #[Test]
    public function getProcessedValueDisplaysAgeForDateInputFieldsIfSettingAbsent(): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);

        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'date' => [
                    'config' => [
                        'type' => 'datetime',
                        'format' => 'date',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame('2015-08-28 (-2 days)', BackendUtility::getProcessedValue('test_table', 'date', mktime(0, 0, 0, 8, 28, 2015)));
    }

    public static function inputTypeDateDisplayOptions(): array
    {
        return [
            'typeSafe Setting' => [
                true,
                '2015-08-28',
            ],
            'non typesafe setting' => [
                1,
                '2015-08-28',
            ],
            'setting disabled typesafe' => [
                false,
                '2015-08-28 (-2 days)',
            ],
            'setting disabled not typesafe' => [
                0,
                '2015-08-28 (-2 days)',
            ],
        ];
    }

    #[DataProvider('inputTypeDateDisplayOptions')]
    #[Test]
    public function getProcessedValueHandlesAgeDisplayCorrectly(bool|int $input, string $expected): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);

        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'date' => [
                    'config' => [
                        'type' => 'datetime',
                        'format' => 'date',
                        'disableAgeDisplay' => $input,
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame($expected, BackendUtility::getProcessedValue('test_table', 'date', mktime(0, 0, 0, 8, 28, 2015)));
    }

    #[Test]
    public function getProcessedValueForCheckWithSingleItem(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'hide' => [
                    'config' => [
                        'type' => 'check',
                        'items' => [
                            [
                                0 => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame('Yes', BackendUtility::getProcessedValue('test_table', 'hide', 1));
    }

    #[Test]
    public function getProcessedValueForCheckWithSingleItemInvertStateDisplay(): void
    {
        $GLOBALS['TCA']['test_table'] = [
            'columns' => [
                'hide' => [
                    'config' => [
                        'type' => 'check',
                        'items' => [
                            [
                                0 => '',
                                'invertStateDisplay' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertSame('No', BackendUtility::getProcessedValue('test_table', 'hide', 1));
    }

    #[Test]
    public function getProcessedValueReturnsLabelsForExistingValuesSolely(): void
    {
        $table = 'test_table';
        $col = 'someColumn';
        $GLOBALS['TCA'][$table] = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['label' => 'aFooLabel', 'value' => 'foo'],
                            ['label' => 'aBarLabel', 'value' => 'bar'],
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $label = BackendUtility::getProcessedValue($table, $col, 'foo,invalidKey,bar');
        self::assertEquals('aFooLabel, aBarLabel', $label);
    }

    #[Test]
    public function getProcessedValueReturnsLabelsFormItemsProcFuncUsingRow(): void
    {
        $table = 'test_table';
        $col = 'someColumn';
        $uid = 123;
        $GLOBALS['TCA'][$table] = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'itemsProcFunc' => static function (array $parameters, $pObj) {
                            $parameters['items'] = [
                                ['label' => $parameters['row']['title'], 'value' => 'foo'],
                                ['label' => $parameters['row']['title2'], 'value' => 'bar'],
                                ['label' => (string)$parameters['row']['uid'], 'value' => 'uidIsApplied'],
                            ];
                        },
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $row = [
            'title' => 'itemTitle',
            'title2' => 'itemTitle2',
        ];

        $label = BackendUtility::getProcessedValue(
            $table,
            $col,
            'foo,invalidKey,bar,uidIsApplied',
            0,
            false,
            false,
            $uid,
            true,
            0,
            $row
        );
        self::assertEquals($row['title'] . ', ' . $row['title2'] . ', ' . $uid, $label);
    }

    #[Test]
    public function getProcessedValueReturnsPlainValueIfItemIsNotFound(): void
    {
        $table = 'test_table';
        $col = 'someColumn';
        $GLOBALS['TCA'][$table] = [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            '0' => ['label' => 'aFooLabel', 'value' => 'foo'],
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $label = BackendUtility::getProcessedValue($table, $col, 'invalidKey');
        self::assertEquals('invalidKey', $label);
    }

    public static function getLabelFromItemlistReturnsCorrectFieldsDataProvider(): array
    {
        return [
            'item set' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'type' => 'select',
                                'items' => [
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '3'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2',
            ],
            'item set twice' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'type' => 'select',
                                'items' => [
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2a', 'value' => '1'],
                                    ['label' => 'Item 2b', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '3'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2a',
            ],
            'item not found' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '5',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'type' => 'select',
                                'items' => [
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '2'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => null,
            ],
            'item from itemsProcFunc' => [
                'table' => 'tt_content',
                'col' => 'menu_type',
                'key' => '1',
                'tca' => [
                    'columns' => [
                        'menu_type' => [
                            'config' => [
                                'type' => 'radio',
                                'items' => [],
                                'itemsProcFunc' => static function (array $parameters, $pObj) {
                                    $parameters['items'] = [
                                        ['label' => 'Item 1', 'value' => '0'],
                                        ['label' => 'Item 2', 'value' => '1'],
                                        ['label' => 'Item 3', 'value' => '2'],
                                    ];
                                },
                            ],
                        ],
                    ],
                ],
                'expectedLabel' => 'Item 2',
            ],
        ];
    }

    #[DataProvider('getLabelFromItemlistReturnsCorrectFieldsDataProvider')]
    #[Test]
    public function getLabelFromItemlistReturnsCorrectFields(
        string $table,
        string $col,
        string $key,
        array $tca,
        ?string $expectedLabel = ''
    ): void {
        $GLOBALS['TCA'][$table] = $tca;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $label = BackendUtility::getLabelFromItemlist($table, $col, $key);
        self::assertEquals($expectedLabel, $label);
    }

    public static function getLabelsFromItemsListDataProvider(): array
    {
        return [
            'return value if found' => [
                'foobar', // table
                'someColumn', // col
                'foo, bar', // keyList
                [ // TCA
                    'columns' => [
                        'someColumn' => [
                            'config' => [
                                'type' => 'select',
                                'items' => [
                                    ['label' => 'aFooLabel', 'value' => 'foo'],
                                    ['label' => 'aBarLabel', 'value' => 'bar'],
                                ],
                            ],
                        ],
                    ],
                ],
                [], // page TSconfig
                'aFooLabel, aBarLabel', // expected
            ],
            'page TSconfig overrules TCA' => [
                'foobar', // table
                'someColumn', // col
                'foo,bar,add', // keyList
                [ // TCA
                    'columns' => [
                        'someColumn' => [
                            'config' => [
                                'type' => 'select',
                                'items' => [
                                    ['label' => 'aFooLabel', 'value' => 'foo'],
                                    ['label' => 'aBarLabel', 'value' => 'bar'],
                                ],
                            ],
                        ],
                    ],
                ],
                [ // page TSconfig
                    'addItems.' => ['add' => 'aNewLabel'],
                    'altLabels.' => ['bar' => 'aBarDiffLabel'],
                ],
                'aFooLabel, aBarDiffLabel, aNewLabel', // expected
            ],
            'itemsProcFunc is evaluated' => [
                'foobar', // table
                'someColumn', // col
                'foo,bar', // keyList
                [ // TCA
                    'columns' => [
                        'someColumn' => [
                            'config' => [
                                'type' => 'select',
                                'itemsProcFunc' => static function (array $parameters, $pObj) {
                                    $parameters['items'] = [
                                        ['label' => 'aFooLabel', 'value' => 'foo'],
                                        ['label' => 'aBarLabel', 'value' => 'bar'],
                                    ];
                                },
                            ],
                        ],
                    ],
                ],
                [],
                'aFooLabel, aBarLabel', // expected
            ],
        ];
    }

    #[DataProvider('getLabelsFromItemsListDataProvider')]
    #[Test]
    public function getLabelsFromItemsListReturnsCorrectValue(
        string $table,
        string $col,
        string $keyList,
        array $tca,
        array $pageTsConfig,
        string $expectedLabel
    ): void {
        $GLOBALS['TCA'][$table] = $tca;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $label = BackendUtility::getLabelsFromItemsList($table, $col, $keyList, $pageTsConfig);
        self::assertEquals($expectedLabel, $label);
    }

    public static function getCommonSelectFieldsReturnsCorrectFieldsDataProvider(): array
    {
        return [
            'minimum fields' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [],
                'expectedFields' => 'uid,pid',
            ],
            'label set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label',
                    ],
                ],
                'expectedFields' => 'uid,pid,label',
            ],
            'label_alt set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label', // @todo This is a bug, see #107143
                        'label_alt' => 'label2,label3',
                    ],
                ],
                'expectedFields' => 'uid,pid,label,label2,label3',
            ],
            'versioningWS set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'versioningWS' => true,
                    ],
                ],
                'expectedFields' => 'uid,pid,t3ver_state,t3ver_wsid',
            ],
            'selicon_field set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'selicon_field' => 'field',
                    ],
                ],
                'expectedFields' => 'uid,pid,field',
            ],
            'typeicon_column set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'typeicon_column' => 'field',
                    ],
                ],
                'expectedFields' => 'uid,pid,field',
            ],
            'enablecolumns set' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'enablecolumns' => [
                            'disabled' => 'hidden',
                            'starttime' => 'start',
                            'endtime' => 'stop',
                            'fe_group' => 'groups',
                        ],
                    ],
                    'columns' => [
                        'hidden' => ['config' => ['type' => 'check']],
                        'start' => ['config' => ['type' => 'check']],
                        'stop' => ['config' => ['type' => 'check']],
                        'groups' => ['config' => ['type' => 'check']],
                    ],
                ],
                'expectedFields' => 'uid,pid,hidden,start,stop,groups',
            ],
            'label set to uid' => [
                'table' => 'test_table',
                'prefix' => '',
                'presetFields' => [],
                'tca' => [
                    'ctrl' => [
                        'label' => 'uid',
                    ],
                ],
                'expectedFields' => 'uid,pid',
            ],
            'prefix used' => [
                'table' => 'test_table',
                'prefix' => 'prefix.',
                'presetFields' => [
                    'preset',
                ],
                'tca' => [
                    'ctrl' => [
                        'label' => 'label',
                        'label_alt' => 'label2,label3', ],
                ],
                'expectedFields' => 'prefix.preset,prefix.uid,prefix.pid,prefix.label,prefix.label2,prefix.label3',
            ],
        ];
    }

    #[DataProvider('getCommonSelectFieldsReturnsCorrectFieldsDataProvider')]
    #[IgnoreDeprecations]
    #[Test]
    public function getCommonSelectFieldsReturnsCorrectFields(
        string $table,
        string $prefix,
        array $presetFields,
        array $tca,
        string $expectedFields = ''
    ): void {
        $GLOBALS['TCA'][$table] = $tca;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $selectFields = BackendUtility::getCommonSelectFields($table, $prefix, $presetFields);
        self::assertEquals($expectedFields, $selectFields);
    }

    #[Test]
    public function getAllowedFieldsForTableReturnsEmptyArrayOnBrokenTca(): void
    {
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
        self::assertEmpty(BackendUtility::getAllowedFieldsForTable('nonExistentTable', false));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function returnNullForMissingTcaConfigInResolveFileReferences(): void
    {
        $tableName = 'test_table';
        $fieldName = 'field_a';
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = [];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertNull(BackendUtility::resolveFileReferences($tableName, $fieldName, []));
    }

    public static function unfitResolveFileReferencesTableConfig(): array
    {
        return [
            'invalid table' => [
                [
                    'type' => 'inline',
                    'foreign_table' => 'table_b',
                ],
            ],
            'empty table' => [
                [
                    'type' => 'inline',
                    'foreign_table' => '',
                ],
            ],
            'invalid type' => [
                [
                    'type' => 'select',
                    'foreign_table' => 'sys_file_reference',
                ],
            ],
            'empty type' => [
                [
                    'type' => '',
                    'foreign_table' => 'sys_file_reference',
                ],
            ],
            'empty' => [
                [
                    'type' => '',
                    'foreign_table' => '',
                ],
            ],
        ];
    }

    #[DataProvider('unfitResolveFileReferencesTableConfig')]
    #[IgnoreDeprecations]
    #[Test]
    public function returnNullForUnfitTableConfigInResolveFileReferences(array $config): void
    {
        $tableName = 'test_table';
        $fieldName = 'field_a';
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = $config;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertNull(BackendUtility::resolveFileReferences($tableName, $fieldName, []));
    }

    #[IgnoreDeprecations]
    #[Test]
    public function resolveFileReferencesReturnsEmptyResultForNoReferencesAvailable(): void
    {
        $tableName = 'test_table';
        $fieldName = 'field_a';
        $elementData = [
            $fieldName => '',
            'uid' => 42,
        ];
        $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] = [
            'type' => 'file',
            'foreign_table' => 'sys_file_reference',
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        self::assertEmpty(BackendUtility::resolveFileReferences($tableName, $fieldName, $elementData));
    }

    public static function calcAgeDataProvider(): array
    {
        return [
            '366 days' => [
                'seconds' => 60 * 60 * 24 * (365 + 1),
                'expectedLabel' => '1 year',
            ],
            '365 days' => [
                'seconds' => 60 * 60 * 24 * 365,
                'expectedLabel' => '1 year',
            ],
            'Plural years' => [
                'seconds' => 60 * 60 * 24 * (365 * 2 + 1),
                'expectedLabel' => '2 yrs',
            ],
            'Negative 366 year' => [
                'seconds' => 60 * 60 * 24 * (365 + 1) * -1,
                'expectedLabel' => '-1 year',
            ],
            'Negative 365 days (not a year, because of leap year)' => [
                'seconds' => 60 * 60 * 24 * 365 * -1,
                'expectedLabel' => '-365 days',
            ],
            'Negative 2*365 days' => [
                'seconds' => 60 * 60 * 24 * (365 * 2) * -1,
                'expectedLabel' => '-1 year',
            ],
            'Negative 366 + 365 days' => [
                'seconds' => 60 * 60 * 24 * (366 + 365) * -1,
                'expectedLabel' => '-2 yrs',
            ],
            'Single day' => [
                'seconds' => 60 * 60 * 24,
                'expectedLabel' => '1 day',
            ],
            'Plural days' => [
                'seconds' => 60 * 60 * 24 * 2,
                'expectedLabel' => '2 days',
            ],
            'Single negative day' => [
                'seconds' => 60 * 60 * 24 * -1,
                'expectedLabel' => '-1 day',
            ],
            'Plural negative days' => [
                'seconds' => 60 * 60 * 24 * 2 * -1,
                'expectedLabel' => '-2 days',
            ],
            'Single hour' => [
                'seconds' => 60 * 60,
                'expectedLabel' => '1 hour',
            ],
            'Plural hours' => [
                'seconds' => 60 * 60 * 2,
                'expectedLabel' => '2 hrs',
            ],
            'Single negative hour' => [
                'seconds' => 60 * 60 * -1,
                'expectedLabel' => '-1 hour',
            ],
            'Plural negative hours' => [
                'seconds' => 60 * 60 * 2 * -1,
                'expectedLabel' => '-2 hrs',
            ],
            'Single minute' => [
                'seconds' => 60,
                'expectedLabel' => '1 min',
            ],
            'Plural minutes' => [
                'seconds' => 60 * 2,
                'expectedLabel' => '2 min',
            ],
            'Single negative minute' => [
                'seconds' => 60 * -1,
                'expectedLabel' => '-1 min',
            ],
            'Plural negative minutes' => [
                'seconds' => 60 * 2 * -1,
                'expectedLabel' => '-2 min',
            ],
            'Zero seconds' => [
                'seconds' => 0,
                'expectedLabel' => '0 min',
            ],
        ];
    }

    #[DataProvider('calcAgeDataProvider')]
    #[Test]
    public function calcAgeReturnsExpectedValues(int $seconds, string $expectedLabel): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 8, 30, 2015);
        self::assertSame($expectedLabel, BackendUtility::calcAge($seconds));
    }

    #[Test]
    public function dateTimeAgeReturnsCorrectValues(): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(0, 0, 0, 3, 23, 2016);

        self::assertSame('2016-03-24 00:00 (-1 day)', BackendUtility::dateTimeAge($GLOBALS['EXEC_TIME'] + 86400));
        self::assertSame('2016-03-24 (-1 day)', BackendUtility::dateTimeAge($GLOBALS['EXEC_TIME'] + 86400, 1, 'date'));
    }

    #[Test]
    public function purgeComputedPropertyNamesRemovesPropertiesStartingWithUnderscore(): void
    {
        $propertyNames = [
            'uid',
            'pid',
            '_ORIG_PID',
        ];
        $computedPropertyNames = BackendUtility::purgeComputedPropertyNames($propertyNames);
        self::assertSame(['uid', 'pid'], $computedPropertyNames);
    }

    #[Test]
    public function purgeComputedPropertiesFromRecordRemovesPropertiesStartingWithUnderscore(): void
    {
        $record = [
            'uid'       => 1,
            'pid'       => 2,
            '_ORIG_PID' => 1,
        ];
        $expected = [
            'uid' => 1,
            'pid' => 2,
        ];
        $computedProperties = BackendUtility::purgeComputedPropertiesFromRecord($record);
        self::assertSame($expected, $computedProperties);
    }

    public static function splitTableUidDataProvider(): array
    {
        return [
            'simple' => [
                'pages_23',
                ['pages', '23'],
            ],
            'complex' => [
                'tt_content_13',
                ['tt_content', '13'],
            ],
            'multiple underscores' => [
                'tx_runaway_domain_model_crime_scene_1234',
                ['tx_runaway_domain_model_crime_scene', '1234'],
            ],
            'no underscore' => [
                'foo',
                ['', 'foo'],
            ],
        ];
    }

    #[DataProvider('splitTableUidDataProvider')]
    #[Test]
    public function splitTableUid($input, $expected): void
    {
        $result = BackendUtility::splitTable_Uid($input);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function wsMapIdReturnsLiveIdIfNoBeUserIsAvailable(): void
    {
        $GLOBALS['BE_USER'] = null;
        $tableName = 'table_a';
        $uid = 42;
        self::assertSame(42, BackendUtility::wsMapId($tableName, $uid));
    }
}
