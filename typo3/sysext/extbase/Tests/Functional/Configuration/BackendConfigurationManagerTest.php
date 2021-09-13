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

namespace TYPO3\CMS\Extbase\Tests\Functional\Configuration;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class BackendConfigurationManagerTest extends FunctionalTestCase
{
    /**
     * Warning: white box test
     *
     * @test
     */
    public function getCurrentPageIdReturnsPidFromFirstRootTemplateIfIdIsNotSetAndNoRootPageWasFound(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(BackendConfigurationManager::class, ['getTypoScriptSetup'], [], '', false);
        $mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $backendConfigurationManager->_set('typoScriptService', $mockTypoScriptService);

        (new ConnectionPool())->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 123,
                'deleted' => 0,
                'hidden' => 0,
                'root' => 1
            ]
        );

        $actualResult = $backendConfigurationManager->_call('getCurrentPageId');
        self::assertEquals(123, $actualResult);
    }

    /**
     * Warning: white box test
     *
     * @test
     */
    public function getCurrentPageIdReturnsUidFromFirstRootPageIfIdIsNotSet(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(BackendConfigurationManager::class, ['getTypoScriptSetup'], [], '', false);
        $mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $backendConfigurationManager->_set('typoScriptService', $mockTypoScriptService);

        (new ConnectionPool())->getConnectionForTable('pages')->insert(
            'pages',
            [
                'deleted' => 0,
                'hidden' => 0,
                'is_siteroot' => 1
            ]
        );

        $actualResult = $backendConfigurationManager->_call('getCurrentPageId');
        self::assertEquals(1, $actualResult);
    }

    /**
     * Warning: white box test
     *
     * @test
     */
    public function getCurrentPageIdReturnsDefaultStoragePidIfIdIsNotSetNoRootTemplateAndRootPageWasFound(): void
    {
        $backendConfigurationManager = $this->getAccessibleMock(BackendConfigurationManager::class, ['getTypoScriptSetup'], [], '', false);
        $mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $backendConfigurationManager->_set('typoScriptService', $mockTypoScriptService);

        $expectedResult = AbstractConfigurationManager::DEFAULT_BACKEND_STORAGE_PID;
        $actualResult = $backendConfigurationManager->_call('getCurrentPageId');
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getRecursiveStoragePidsReturnsListOfPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendConfigurationManagerRecursivePids.csv');
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['admin' => true];
        $backendConfigurationManager = $this->getAccessibleMock(BackendConfigurationManager::class, null, [], '', false);
        $expectedResult = [1, 2, 4, 5, 3, 6, 7];
        $actualResult = $backendConfigurationManager->_call('getRecursiveStoragePids', [1, -6], 4);
        self::assertEquals($expectedResult, $actualResult);
    }
}
