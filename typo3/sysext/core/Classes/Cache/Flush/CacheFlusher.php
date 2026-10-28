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

namespace TYPO3\CMS\Core\Cache\Flush;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent;
use TYPO3\CMS\Core\Cache\Event\BeforeCacheFlushEvent;
use TYPO3\CMS\Core\Cache\Event\BeforeRecordRelatedCachesAreFlushedEvent;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DependencyInjection\Cache\ContainerBackend;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Flushes the caches affected by changed records, pages, cache tags and
 * cache commands, without the need of a DataHandler.
 *
 * Cache tags are flushed in the cache group "pages". Cache groups are flushed
 * by dispatching CacheFlushEvent, like the "cache:flush" command does.
 * All events are dispatched here, callers only describe what to flush.
 * flush() resolves and executes a demand in one go, resolve() alone tells
 * what would be flushed.
 */
#[Autoconfigure(public: true)]
readonly class CacheFlusher
{
    public function __construct(
        private CacheManager $cacheManager,
        private EventDispatcherInterface $eventDispatcher,
        private TcaSchemaFactory $tcaSchemaFactory,
        private CacheFlushPageResolver $pageResolver,
        private OpcodeCacheService $opcodeCacheService,
        private ContainerInterface $container,
    ) {}

    public function flush(CacheFlushDemand $demand, CacheFlushingContext $context = new CacheFlushingContext()): CacheFlushResult
    {
        return $this->execute($this->resolve($demand, $context));
    }

    /**
     * Resolves a demand into the cache tags and groups to flush, without
     * flushing anything. Dispatches BeforeRecordRelatedCachesAreFlushedEvent
     * for every record and BeforeCacheFlushEvent for the complete plan, so
     * their listeners also run here and must only change what is flushed.
     */
    public function resolve(CacheFlushDemand $demand, CacheFlushingContext $context = new CacheFlushingContext()): CacheFlushPlan
    {
        $plan = new CacheFlushPlan($context);
        foreach ($demand->records as $table => $records) {
            foreach ($records as $uid => $originalPageIds) {
                $this->resolveRecord((string)$table, (int)$uid, $originalPageIds, $plan);
            }
        }
        foreach ($demand->pageIds as $pageId) {
            $plan->addTag('pageId_' . $pageId);
        }
        foreach ($demand->tags as $tag) {
            $plan->addTag($tag);
        }
        foreach ($demand->commands as $command) {
            $this->resolveCommand($command, $plan);
        }
        $this->eventDispatcher->dispatch(new BeforeCacheFlushEvent($plan));
        return $plan;
    }

    /**
     * Flushes the caches of a plan and dispatches AfterCacheFlushedEvent.
     */
    public function execute(CacheFlushPlan $plan): CacheFlushResult
    {
        if ($plan->hasGroup('all')) {
            $this->eventDispatcher->dispatch(new CacheFlushEvent($this->cacheManager->getCacheGroups()));
            $this->cacheManager->flushCachesWithoutGroup();
            $this->opcodeCacheService->clearAllActive();
            $this->flushDependencyInjectionCacheInDevelopment();
        } else {
            if ($plan->getGroups() !== []) {
                $this->eventDispatcher->dispatch(new CacheFlushEvent($plan->getGroups()));
            }
            if ($plan->getTags() !== []) {
                $this->cacheManager->flushCachesInGroupByTags('pages', $plan->getTags());
            }
        }
        $result = CacheFlushResult::fromPlan($plan);
        $this->eventDispatcher->dispatch(new AfterCacheFlushedEvent($result));
        return $result;
    }

    /**
     * @param list<int> $originalPageIds
     */
    private function resolveRecord(string $table, int $uid, array $originalPageIds, CacheFlushPlan $plan): void
    {
        if ($uid <= 0 || !$this->tcaSchemaFactory->has($table)) {
            $plan->addRecord(new CacheFlushRecord($table, $uid, 0, CacheFlushSkipReason::InvalidRecord));
            return;
        }
        if ($originalPageIds === []) {
            $originalPageId = $this->pageResolver->getPageTsConfigPageId($table, $uid);
            if ($originalPageId === null) {
                $plan->addRecord(new CacheFlushRecord($table, $uid, 0, CacheFlushSkipReason::InvalidRecord));
                return;
            }
            $originalPageIds = [$originalPageId];
        }
        foreach ($originalPageIds as $originalPageId) {
            $pageTsConfig = BackendUtility::getPagesTSconfig($originalPageId)['TCEMAIN.'] ?? [];
            $skipReason = null;
            if (!empty($pageTsConfig['clearCache_disable'])) {
                $skipReason = CacheFlushSkipReason::DisabledByPageTsConfig;
            } elseif ($plan->context->workspaceId !== 0 && $this->pageResolver->isWorkspaceVersion($table, $uid)) {
                $skipReason = CacheFlushSkipReason::WorkspaceVersion;
            }
            $pageId = 0;
            $pageIds = [];
            $tags = [];
            if ($skipReason === null) {
                $pageId = $this->pageResolver->getPageIdOfRecord($table, $uid);
                $pageIds = $this->pageResolver->getAffectedPageIds($table, $pageId, $pageTsConfig);
                $tags = [$table, $table . '_' . $uid];
            }
            $commands = array_values(array_unique(GeneralUtility::trimExplode(',', (string)($pageTsConfig['clearCacheCmd'] ?? ''), true)));

            $event = $this->eventDispatcher->dispatch(new BeforeRecordRelatedCachesAreFlushedEvent(
                $table,
                $uid,
                $pageId,
                (int)$originalPageId,
                $pageTsConfig,
                $skipReason,
                $plan->context,
                array_values(array_unique($pageIds)),
                $tags,
                $commands,
            ));

            $plan->addRecord(new CacheFlushRecord($table, $uid, $pageId, $skipReason));
            foreach ($event->getPageIds() as $affectedPageId) {
                $plan->addTag('pageId_' . $affectedPageId);
            }
            foreach ($event->getTags() as $tag) {
                $plan->addTag($tag);
            }
            foreach ($event->getCommands() as $command) {
                $this->resolveCommand($command, $plan);
            }
        }
    }

    private function resolveCommand(string $command, CacheFlushPlan $plan): void
    {
        if (!$plan->addCommand($command)) {
            return;
        }
        $lowerCaseCommand = strtolower($command);
        if ($lowerCaseCommand === 'pages' || $lowerCaseCommand === 'all') {
            $isPermitted = $lowerCaseCommand === 'all' ? $plan->context->canFlushAllCaches : $plan->context->canFlushPagesGroup;
            if ($isPermitted) {
                $plan->addGroup($lowerCaseCommand);
            } else {
                $plan->skipGroup($lowerCaseCommand, CacheFlushSkipReason::NotPermitted);
            }
        } elseif (MathUtility::canBeInterpretedAsInteger($command)) {
            $plan->addTag('pageId_' . (int)$command);
        } elseif (str_starts_with($lowerCaseCommand, 'cachetag:')) {
            $plan->addTag(substr($command, 9));
        }
    }

    private function flushDependencyInjectionCacheInDevelopment(): void
    {
        if (!Environment::getContext()->isDevelopment()) {
            return;
        }
        $dependencyInjectionCacheBackend = $this->container->get('cache.di')->getBackend();
        if ($dependencyInjectionCacheBackend instanceof ContainerBackend) {
            $dependencyInjectionCacheBackend->forceFlush();
        }
    }
}
