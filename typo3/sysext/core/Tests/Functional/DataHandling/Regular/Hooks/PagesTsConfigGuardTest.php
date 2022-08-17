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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Hooks;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PagesTsConfigGuardTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../DataSet/ImportDefault.csv');
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_users.csv');

        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        // define page create permissions for backend user group 9 on page 1
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->update(
                'pages',
                ['perms_groupid' => 9, 'perms_group' => Permission::ALL],
                ['uid' => 1]
            );
    }

    #[Test]
    public function pagesTsConfigIsConsideredForAdminUser(): void
    {
        $identifier = StringUtility::getUniqueId('NEW');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataMap = [
            'pages' => [
                $identifier => [
                    'pid' => 1,
                    'title' => 'New page',
                    'TSconfig' => 'custom.setting = 1',
                    'tsconfig_includes' => 'EXT:package/file.tsconfig',
                ],
            ],
        ];

        $this->assertProcessedDataMap($dataMap, $backendUser);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/pagesTsConfigIsConsideredForAdminUser.csv');
    }

    #[Test]
    public function pagesTsConfigIsIgnoredForNonAdminUser(): void
    {
        $identifier = StringUtility::getUniqueId('NEW');
        $backendUser = $this->setUpBackendUser(9);
        $backendUser->groupData['pagetypes_select'] = '1';
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataMap = [
            'pages' => [
                $identifier => [
                    'pid' => 1,
                    'title' => 'New page',
                    'TSconfig' => 'custom.setting = 1',
                    'tsconfig_includes' => 'EXT:package/file.tsconfig',
                ],
            ],
        ];

        $this->assertProcessedDataMap($dataMap, $backendUser);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/pagesTsConfigIsIgnoredForNonAdminUser.csv');
    }

    private function assertProcessedDataMap(array $dataMap, BackendUserAuthentication $backendUser): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, [], $backendUser);
        $dataHandler->process_datamap();
        self::assertEmpty($dataHandler->errorLog);
    }
}
