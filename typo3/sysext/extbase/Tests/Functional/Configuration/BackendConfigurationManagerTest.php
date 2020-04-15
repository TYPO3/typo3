<?php

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
    public function getCurrentPageIdReturnsPidFromFirstRootTemplateIfIdIsNotSetAndNoRootPageWasFound()
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
    public function getCurrentPageIdReturnsUidFromFirstRootPageIfIdIsNotSet()
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
    public function getCurrentPageIdReturnsDefaultStoragePidIfIdIsNotSetNoRootTemplateAndRootPageWasFound()
    {
        $backendConfigurationManager = $this->getAccessibleMock(BackendConfigurationManager::class, ['getTypoScriptSetup'], [], '', false);
        $mockTypoScriptService = $this->getMockBuilder(TypoScriptService::class)->getMock();
        $backendConfigurationManager->_set('typoScriptService', $mockTypoScriptService);

        $expectedResult = AbstractConfigurationManager::DEFAULT_BACKEND_STORAGE_PID;
        $actualResult = $backendConfigurationManager->_call('getCurrentPageId');
        self::assertEquals($expectedResult, $actualResult);
    }
}
