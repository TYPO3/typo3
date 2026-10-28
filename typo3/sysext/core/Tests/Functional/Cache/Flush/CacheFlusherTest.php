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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent;
use TYPO3\CMS\Core\Cache\Event\BeforeCacheFlushEvent;
use TYPO3\CMS\Core\Cache\Event\BeforeRecordRelatedCachesAreFlushedEvent;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand;
use TYPO3\CMS\Core\Cache\Flush\CacheFlusher;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushRecord;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushSkipReason;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Tests\Functional\Cache\Flush\Fixtures\FlushCountingBackend;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CacheFlusherTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                    'cache_flusher_test_pages_group' => [
                        'backend' => FlushCountingBackend::class,
                        'groups' => ['pages'],
                    ],
                    'cache_flusher_test_default_group' => [
                        'backend' => FlushCountingBackend::class,
                    ],
                    'cache_flusher_test_without_group' => [
                        'backend' => FlushCountingBackend::class,
                        'groups' => [],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
    }

    public static function recordResolvesToTagsDataProvider(): array
    {
        return [
            'content element flushes its page' => [
                'tt_content',
                1,
                ['pageId_3', 'tt_content', 'tt_content_1'],
            ],
            'page flushes itself, its siblings and its parent' => [
                'pages',
                3,
                ['pageId_3', 'pageId_4', 'pageId_7', 'pageId_2', 'pages', 'pages_3'],
            ],
            'translated page flushes the pages of its default language page' => [
                'pages',
                7,
                ['pageId_3', 'pageId_4', 'pageId_7', 'pageId_2', 'pages', 'pages_7'],
            ],
            'content element with clearCache_pageGrandParent flushes the parent of its page' => [
                'tt_content',
                5,
                ['pageId_12', 'pageId_10', 'tt_content', 'tt_content_5'],
            ],
            'page with clearCache_pageSiblingChildren and clearCache_pageGrandParent' => [
                'pages',
                10,
                ['pageId_10', 'pageId_12', 'pageId_11', 'pageId_9', 'pageId_1', 'pages', 'pages_10'],
            ],
            'content element with clearCacheCmd flushes the configured pages and tags' => [
                'tt_content',
                4,
                ['pageId_8', 'tt_content', 'tt_content_4', 'pageId_6', 'news'],
            ],
            'record on root level flushes its tags' => [
                'sys_category',
                1,
                ['sys_category', 'sys_category_1'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('recordResolvesToTagsDataProvider')]
    public function recordResolvesToTags(string $table, int $uid, array $expectedTags): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord($table, $uid));

        self::assertEqualsCanonicalizing($expectedTags, $plan->getTags());
    }

    #[Test]
    public function cacheCommandsOfPageTsConfigArePartOfThePlan(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 4));

        self::assertSame(['6', 'cacheTag:news', 'pages'], $plan->getCommands());
    }

    #[Test]
    public function clearCacheCmdFlushesPagesGroupIfPermitted(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 4));

        self::assertSame(['pages'], $plan->getGroups());
        self::assertSame([], $plan->getSkippedGroups());
    }

    #[Test]
    public function clearCacheCmdSkipsPagesGroupIfNotPermitted(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(
            CacheFlushDemand::forRecord('tt_content', 4),
            new CacheFlushingContext(canFlushPagesGroup: false),
        );

        self::assertSame([], $plan->getGroups());
        self::assertSame(['pages' => CacheFlushSkipReason::NotPermitted], $plan->getSkippedGroups());
        self::assertContains('pageId_6', $plan->getTags());
    }

    #[Test]
    public function recordOnPageWithClearCacheDisableIsSkipped(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 3));

        self::assertSame([], $plan->getTags());
        self::assertEquals(
            [new CacheFlushRecord('tt_content', 3, 0, CacheFlushSkipReason::DisabledByPageTsConfig)],
            $plan->getRecords()
        );
    }

    #[Test]
    public function workspaceVersionIsSkippedInWorkspace(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(
            CacheFlushDemand::forRecord('tt_content', 2)->withRecord('tt_content', 1),
            new CacheFlushingContext(workspaceId: 1),
        );

        self::assertEquals(
            [
                new CacheFlushRecord('tt_content', 2, 0, CacheFlushSkipReason::WorkspaceVersion),
                new CacheFlushRecord('tt_content', 1, 3),
            ],
            $plan->getRecords()
        );
        self::assertEqualsCanonicalizing(['pageId_3', 'tt_content', 'tt_content_1'], $plan->getTags());
    }

    public static function invalidRecordDataProvider(): array
    {
        return [
            'table without TCA' => ['tx_table_without_tca', 1],
            'record uid zero' => ['tt_content', 0],
            'record that does not exist' => ['tt_content', 999],
        ];
    }

    #[Test]
    #[DataProvider('invalidRecordDataProvider')]
    public function invalidRecordIsSkippedWithoutAffectingOtherRecords(string $table, int $uid): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(
            CacheFlushDemand::forRecord($table, $uid)->withRecord('tt_content', 1)
        );

        self::assertEquals(
            [
                new CacheFlushRecord($table, $uid, 0, CacheFlushSkipReason::InvalidRecord),
                new CacheFlushRecord('tt_content', 1, 3),
            ],
            $plan->getRecords()
        );
        self::assertEqualsCanonicalizing(['pageId_3', 'tt_content', 'tt_content_1'], $plan->getTags());
    }

    #[Test]
    public function movedRecordUsesPageTsConfigOfItsOriginalPage(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 1, 6));

        self::assertEquals(
            [new CacheFlushRecord('tt_content', 1, 0, CacheFlushSkipReason::DisabledByPageTsConfig)],
            $plan->getRecords()
        );
    }

    public static function commandResolvesToTagsAndGroupsDataProvider(): array
    {
        return [
            'page id' => ['5', ['pageId_5'], []],
            'cache tag' => ['cacheTag:myTag', ['myTag'], []],
            'pages group' => ['pages', [], ['pages']],
            'pages group in upper case' => ['PAGES', [], ['pages']],
            'all caches' => ['all', [], ['all']],
            'unknown command' => ['myCommand', [], []],
        ];
    }

    #[Test]
    #[DataProvider('commandResolvesToTagsAndGroupsDataProvider')]
    public function commandResolvesToTagsAndGroups(string $command, array $expectedTags, array $expectedGroups): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forCommand($command));

        self::assertSame($expectedTags, $plan->getTags());
        self::assertSame($expectedGroups, $plan->getGroups());
        self::assertSame([$command], $plan->getCommands());
    }

    #[Test]
    public function allCachesAreSkippedIfNotPermitted(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(
            CacheFlushDemand::forCommand('all'),
            new CacheFlushingContext(canFlushAllCaches: false),
        );

        self::assertSame([], $plan->getGroups());
        self::assertSame(['all' => CacheFlushSkipReason::NotPermitted], $plan->getSkippedGroups());
    }

    #[Test]
    public function beforeRecordRelatedCachesAreFlushedEventChangesPagesTagsAndCommands(): void
    {
        $dispatchedEvents = [];
        $this->addListener(
            BeforeRecordRelatedCachesAreFlushedEvent::class,
            static function (BeforeRecordRelatedCachesAreFlushedEvent $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
                $event->setPageIds([]);
                $event->addPageId(42);
                $event->addPageId(42);
                if ($event->hasTag('tt_content_1')) {
                    $event->addTag('myTag');
                }
                $event->setCommands(['cacheTag:fromCommand']);
            }
        );

        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 1));

        self::assertCount(1, $dispatchedEvents);
        self::assertSame('tt_content', $dispatchedEvents[0]->table);
        self::assertSame(1, $dispatchedEvents[0]->uid);
        self::assertSame(3, $dispatchedEvents[0]->pageId);
        self::assertSame(3, $dispatchedEvents[0]->originalPageId);
        self::assertNull($dispatchedEvents[0]->skipReason);
        self::assertSame([42], $dispatchedEvents[0]->getPageIds());
        self::assertEqualsCanonicalizing(['pageId_42', 'tt_content', 'tt_content_1', 'myTag', 'fromCommand'], $plan->getTags());
    }

    #[Test]
    public function beforeRecordRelatedCachesAreFlushedEventIsDispatchedForSkippedRecords(): void
    {
        $dispatchedEvents = [];
        $this->addListener(
            BeforeRecordRelatedCachesAreFlushedEvent::class,
            static function (BeforeRecordRelatedCachesAreFlushedEvent $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            }
        );

        $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 3));

        self::assertCount(1, $dispatchedEvents);
        self::assertSame(CacheFlushSkipReason::DisabledByPageTsConfig, $dispatchedEvents[0]->skipReason);
        self::assertSame('1', $dispatchedEvents[0]->pageTsConfig['clearCache_disable'] ?? null);
        self::assertSame([], $dispatchedEvents[0]->getPageIds());
        self::assertSame([], $dispatchedEvents[0]->getTags());
    }

    #[Test]
    public function beforeCacheFlushEventChangesThePlanOnce(): void
    {
        $dispatchCount = 0;
        $this->addListener(
            BeforeCacheFlushEvent::class,
            static function (BeforeCacheFlushEvent $event) use (&$dispatchCount): void {
                $dispatchCount++;
                $event->plan->removeTag('tt_content');
                $event->plan->addTag('myTag');
            }
        );

        $plan = $this->get(CacheFlusher::class)->resolve(
            CacheFlushDemand::forRecord('tt_content', 1)->withRecord('tt_content', 5)
        );

        self::assertSame(1, $dispatchCount);
        self::assertEqualsCanonicalizing(['pageId_3', 'tt_content_1', 'pageId_12', 'pageId_10', 'tt_content_5', 'myTag'], $plan->getTags());
    }

    #[Test]
    public function flushFlushesResolvedTagsAndDispatchesAfterCacheFlushedEventOnce(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-a', ['dummy'], ['pageId_3']);
        $pageCache->set('page-b', ['dummy'], ['pageId_4']);
        $pageCache->set('child-of-a', ['dummy'], ['pageId_5']);
        $dispatchedEvents = [];
        $this->addListener(
            AfterCacheFlushedEvent::class,
            static function (AfterCacheFlushedEvent $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            }
        );

        $result = $this->get(CacheFlusher::class)->flush(CacheFlushDemand::forRecord('pages', 3));

        self::assertFalse($pageCache->has('page-a'));
        self::assertFalse($pageCache->has('page-b'));
        self::assertTrue($pageCache->has('child-of-a'));
        self::assertCount(1, $dispatchedEvents);
        self::assertSame($result, $dispatchedEvents[0]->result);
        self::assertEqualsCanonicalizing([3, 4, 7, 2], $result->getPageIds());
        self::assertEquals([new CacheFlushRecord('pages', 3, 3)], $result->getFlushedRecords());
        self::assertSame([], $result->getSkippedRecords());
    }

    #[Test]
    public function pagesCommandFlushesPagesGroupThroughCacheFlushEvent(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-a', ['dummy'], ['pageId_3']);
        $dispatchedEvents = [];
        $this->addListener(
            CacheFlushEvent::class,
            static function (CacheFlushEvent $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            }
        );

        $result = $this->get(CacheFlusher::class)->flush(CacheFlushDemand::forCommand('pages'));

        self::assertFalse($pageCache->has('page-a'));
        self::assertCount(1, $dispatchedEvents);
        self::assertSame(['pages'], $dispatchedEvents[0]->getGroups());
        self::assertTrue($result->includesGroup('pages'));
        self::assertFalse($result->includesGroup('system'));
    }

    #[Test]
    public function allCommandDispatchesCacheFlushEventWithAllGroups(): void
    {
        $dispatchedEvents = [];
        $this->addListener(
            CacheFlushEvent::class,
            static function (CacheFlushEvent $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            }
        );

        $result = $this->get(CacheFlusher::class)->flush(CacheFlushDemand::forCommand('all'));

        self::assertCount(1, $dispatchedEvents);
        self::assertEqualsCanonicalizing(['all', 'pages', 'system'], $dispatchedEvents[0]->getGroups());
        self::assertTrue($result->includesGroup('system'));
    }

    public static function allCommandFlushesEveryCacheOnceDataProvider(): array
    {
        return [
            'cache in the "pages" group' => ['cache_flusher_test_pages_group'],
            'cache in the default group' => ['cache_flusher_test_default_group'],
            'cache without a group, like the runtime cache' => ['cache_flusher_test_without_group'],
        ];
    }

    #[Test]
    #[DataProvider('allCommandFlushesEveryCacheOnceDataProvider')]
    public function allCommandFlushesEveryCacheOnce(string $cacheIdentifier): void
    {
        /** @var FlushCountingBackend $backend */
        $backend = $this->get(CacheManager::class)->getCache($cacheIdentifier)->getBackend();

        $this->get(CacheFlusher::class)->flush(CacheFlushDemand::forCommand('all'));

        self::assertSame(1, $backend->flushCount);
    }

    private function addListener(string $event, \Closure $listener): void
    {
        $identifier = 'cache-flusher-test-' . md5($event);
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set($identifier, $listener);
        $this->get(ListenerProvider::class)->addListener($event, $identifier);
    }
}
