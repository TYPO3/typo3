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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Event\AfterRecordPublishedEvent;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * DataHandlerHook test - Contains scenarios that do not fit into DataHandler/ modify/publish/... scenarios
 */
final class DataHandlerHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    #[Test]
    public function deletingSysWorkspaceDeletesWorkspaceRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->importCSVDataSet(__DIR__ . '/DataSet/deletingSysWorkspaceDeletesWorkspaceRecords.csv');

        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $actionService = new ActionService();
        // Create a pages move placeholder uid:93 and a versioned record uid:94 - both should be fully deleted after deleting ws1
        $actionService->moveRecord('pages', 92, -1);
        // Create a versioned record uid:311 - should be fully deleted after deleting ws1
        $actionService->createNewRecord('tt_content', 89, ['header' => 'Testing #1']);
        // Create a versioned record of a translated record uid:313 - should be fully deleted after deleting ws1
        $actionService->modifyRecord('tt_content', 301, ['header' => '[Translate to Dansk:] Regular Element #1 Changed']);
        // Create a delete placeholder uid:314 - should be fully deleted after deleting ws1
        $actionService->deleteRecord('tt_content', 310);

        $backendUser->workspace = 2;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(2));
        // Create a versioned record uid:314 in ws2 - should be kept after deleting ws1
        $actionService->modifyRecord('tt_content', 301, ['header' => '[Translate to Dansk:] Regular Element #1 Changed in ws2']);

        // Switch to live and delete sys_workspace record 1
        $backendUser->workspace = 0;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(0));
        $actionService->deleteRecord('sys_workspace', 1);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/deletingSysWorkspaceDeletesWorkspaceRecordsResult.csv');
    }

    #[Test]
    public function flushByTagEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $afterRecordPublishedEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-record-published-event',
            static function (AfterRecordPublishedEvent $event) use (&$afterRecordPublishedEvent) {
                $afterRecordPublishedEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterRecordPublishedEvent::class, 'after-record-published-event');

        $this->importCSVDataSet(__DIR__ . '/DataSet/deletingSysWorkspaceDeletesWorkspaceRecords.csv');

        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $actionService = new ActionService();
        $actionService->modifyRecord('tt_content', 301, ['header' => '[Translate to Dansk:] Regular Element #1 Changed']);
        $actionService->publishRecord('tt_content', 301);

        self::assertInstanceOf(AfterRecordPublishedEvent::class, $afterRecordPublishedEvent);
        self::assertEquals('tt_content', $afterRecordPublishedEvent->getTable());
        self::assertEquals(301, $afterRecordPublishedEvent->getRecordId());
        self::assertEquals(1, $afterRecordPublishedEvent->getWorkspaceId());
    }
}
