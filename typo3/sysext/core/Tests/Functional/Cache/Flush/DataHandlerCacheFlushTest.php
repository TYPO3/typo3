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

namespace TYPO3\CMS\Core\Tests\Functional\Cache\Flush;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushRecord;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerCacheFlushTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    /** @var list<AfterCacheFlushedEvent> */
    private array $afterCacheFlushedEvents = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('data-handler-cache-flush-test', function (AfterCacheFlushedEvent $event): void {
            $this->afterCacheFlushedEvents[] = $event;
        });
        $this->get(ListenerProvider::class)->addListener(AfterCacheFlushedEvent::class, 'data-handler-cache-flush-test');
    }

    #[Test]
    public function updatedRecordsAreFlushedOnceAtTheEndOfTheDataHandlerRun(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-a', ['dummy'], ['pageId_3']);
        $pageCache->set('page-with-cache-commands', ['dummy'], ['pageId_8']);
        $pageCache->set('news', ['dummy'], ['news']);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([
            'tt_content' => [
                1 => ['header' => 'Changed content'],
                4 => ['header' => 'Changed content'],
            ],
        ], []);
        $dataHandler->process_datamap();

        self::assertFalse($pageCache->has('page-a'));
        self::assertFalse($pageCache->has('page-with-cache-commands'));
        self::assertFalse($pageCache->has('news'));
        self::assertCount(1, $this->afterCacheFlushedEvents);
        $result = $this->afterCacheFlushedEvents[0]->result;
        self::assertEquals(
            [new CacheFlushRecord('tt_content', 1, 3), new CacheFlushRecord('tt_content', 4, 8)],
            $result->records
        );
        self::assertSame(['6', 'cacheTag:news', 'pages'], $result->commands);
        self::assertSame(['pages'], $result->groups);
    }

    #[Test]
    public function updatedRecordOnRootLevelIsFlushed(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('category', ['dummy'], ['sys_category_1']);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['sys_category' => [1 => ['title' => 'Changed category']]], []);
        $dataHandler->process_datamap();

        self::assertFalse($pageCache->has('category'));
        self::assertCount(1, $this->afterCacheFlushedEvents);
        self::assertEquals(
            [new CacheFlushRecord('sys_category', 1, 0)],
            $this->afterCacheFlushedEvents[0]->result->records
        );
    }

    #[Test]
    public function cacheCommandsOfPageTsConfigAreLogged(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [4 => ['header' => 'Changed content']]], []);
        $dataHandler->process_datamap();

        $loggedCommands = $this->getConnectionPool()->getConnectionForTable('sys_log')
            ->select(['log_data'], 'sys_log', ['type' => 3])
            ->fetchFirstColumn();
        self::assertCount(3, $loggedCommands);
    }

    #[Test]
    public function dataHandlerRunWithoutChangesDoesNotFlushCaches(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        self::assertSame([], $this->afterCacheFlushedEvents);
    }

    #[Test]
    public function clearCacheCmdFlushesPageCacheAndReportsTheCommand(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-a', ['dummy'], ['pageId_3']);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], []);
        $dataHandler->clear_cacheCmd(3);

        self::assertFalse($pageCache->has('page-a'));
        self::assertCount(1, $this->afterCacheFlushedEvents);
        self::assertSame(['3'], $this->afterCacheFlushedEvents[0]->result->commands);
        self::assertSame([3], $this->afterCacheFlushedEvents[0]->result->getPageIds());
    }
}
