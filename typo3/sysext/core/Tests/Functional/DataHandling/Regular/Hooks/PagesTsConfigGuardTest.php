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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PagesTsConfigGuardTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    private $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

    /**
     * @var string
     */
    private $assertionDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/Hooks/DataSet/';

    /**
     * The fixture which is used when initializing a backend user
     *
     * @var string
     */
    protected $backendUserFixture = 'typo3/sysext/core/Tests/Functional/Fixtures/be_users.xml';

    protected function setUp(): void
    {
        parent::setUp();
        Bootstrap::initializeLanguageObject();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importDataSet(dirname($this->backendUserFixture) . '/be_groups.xml');
        // define page create permissions for backend user group 9 on page 1
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->update(
                'pages',
                ['perms_groupid' => 9, 'perms_group' => Permission::ALL],
                ['uid' => 1]
            );
    }

    /**
     * @test
     */
    public function pagesTsConfigIsConsideredForAdminUser()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        $identifier = StringUtility::getUniqueId('NEW');

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
        $this->assertAssertionDataSet('pagesTsConfigIsConsideredForAdminUser');
    }

    /**
     * @test
     */
    public function pagesTsConfigIsIgnoredForNonAdminUser()
    {
        $backendUser = $this->setUpBackendUserFromFixture(9);
        $identifier = StringUtility::getUniqueId('NEW');

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
        $this->assertAssertionDataSet('pagesTsConfigIsIgnoredForNonAdminUser');
    }

    /**
     * @param string $dataSetName
     */
    private function importScenarioDataSet($dataSetName): void
    {
        $fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->importCSVDataSet($fileName);
    }

    private function assertAssertionDataSet($dataSetName): void
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->assertCSVDataSet($fileName);
    }

    private function assertProcessedDataMap(array $dataMap, BackendUserAuthentication $backendUser)
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, [], $backendUser);
        $dataHandler->process_datamap();
        self::assertEmpty($dataHandler->errorLog);
    }
}
