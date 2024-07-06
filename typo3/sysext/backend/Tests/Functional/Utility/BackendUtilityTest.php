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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
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
}
