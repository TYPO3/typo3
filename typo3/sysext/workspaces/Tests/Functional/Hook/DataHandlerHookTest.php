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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Hook;

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Event\AfterRecordPublishedEvent;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * DataHandlerHook test - Contains scenarios that do not fit into DataHandler/ modify/publish/... scenarios
 */
class DataHandlerHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected BackendUserAuthentication $backendUser;
    protected ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();
        $this->actionService = new ActionService();
        $this->setWorkspaceId(0);
    }

    protected function tearDown(): void
    {
        unset($this->actionService, $this->backendUser);
        parent::tearDown();
    }

    protected function setWorkspaceId(int $workspaceId): void
    {
        $this->backendUser->workspace = $workspaceId;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    /**
     * @test
     */
    public function deletingSysWorkspaceDeletesWorkspaceRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/deletingSysWorkspaceDeletesWorkspaceRecords.csv');

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

        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletingSysWorkspaceDeletesWorkspaceRecordsResult.csv');
    }

    /**
     * @test
     */
    public function flushByTagEventIsTriggered(): void
    {
        $afterRecordPublishedEvent = null;

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'after-record-published-event',
            static function (AfterRecordPublishedEvent $event) use (&$afterRecordPublishedEvent) {
                $afterRecordPublishedEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterRecordPublishedEvent::class, 'after-record-published-event');

        $this->importCSVDataSet(__DIR__ . '/DataSet/deletingSysWorkspaceDeletesWorkspaceRecords.csv');

        $workspaceId = 1;
        $tableName = 'tt_content';
        $recordId = 301;

        $this->setWorkspaceId($workspaceId);
        $this->actionService->modifyRecord($tableName, $recordId, ['header' => '[Translate to Dansk:] Regular Element #1 Changed']);
        $this->actionService->publishRecord($tableName, $recordId);

        self::assertInstanceOf(AfterRecordPublishedEvent::class, $afterRecordPublishedEvent);
        self::assertEquals($tableName, $afterRecordPublishedEvent->getTable());
        self::assertEquals($recordId, $afterRecordPublishedEvent->getRecordId());
        self::assertEquals($workspaceId, $afterRecordPublishedEvent->getWorkspaceId());
    }
}
