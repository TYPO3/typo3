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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Hook;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * DataHandlerHook test - Contains scenarios that do not fit into DataHandler/ modify/publish/... scenarios
 */
class DataHandlerHookTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var ActionService
     */
    protected $actionService;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/Hook/DataSet/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/Hook/DataSet/';

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUser =  $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();
        $this->actionService = $this->getActionService();
        $this->setWorkspaceId(0);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        unset($this->actionService);
        parent::tearDown();
    }

    /**
     * @param int $workspaceId
     */
    protected function setWorkspaceId(int $workspaceId)
    {
        $this->backendUser->workspace = $workspaceId;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    /**
     * @return ActionService
     */
    protected function getActionService()
    {
        return GeneralUtility::makeInstance(ActionService::class);
    }

    /**
     * @param string $dataSetName
     */
    protected function importScenarioDataSet($dataSetName)
    {
        $fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->importCSVDataSet($fileName);
    }

    /**
     * @param string $dataSetName
     */
    protected function assertAssertionDataSet($dataSetName)
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->assertCSVDataSet($fileName);
    }

    /**
     * @test
     */
    public function deletingSysWorkspaceDeletesWorkspaceRecords()
    {
        $this->importScenarioDataSet('deletingSysWorkspaceDeletesWorkspaceRecords');

        $this->setWorkspaceId(1);
        // Create a pages move placeholder uid:93 and a versioned record uid:94 - both should be fully deleted after deleting ws1
        $this->actionService->moveRecord('pages', 92, -1);
        // Create a versioned record uid:311 - should be fully deleted after deleting ws1
        $this->actionService->createNewRecord('tt_content', 89, ['header' => 'Testing #1']);
        // Create a versioned record of a translated record uid:313 - should be fully deleted after deleting ws1
        $this->actionService->modifyRecord('tt_content', 301, ['header' => '[Translate to Dansk:] Regular Element #1 Changed']);
        // Create a delete placeholder uid:314 - should be fully deleted after deleting ws1
        $this->actionService->deleteRecord('tt_content', 310);

        $this->setWorkspaceId(2);
        // Create a versioned record uid:314 in ws2 - should be kept after deleting ws1
        $this->actionService->modifyRecord('tt_content', 301, ['header' => '[Translate to Dansk:] Regular Element #1 Changed in ws2']);

        // Switch to live and delete sys_workspace record 1
        $this->setWorkspaceId(0);
        $this->actionService->deleteRecord('sys_workspace', 1);

        $this->assertAssertionDataSet('deletingSysWorkspaceDeletesWorkspaceRecordsResult');
    }
}
