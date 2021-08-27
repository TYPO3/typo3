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

namespace TYPO3\CMS\Core\Command;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DependencyInjection\Cache\ContainerBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheFlushCommand extends Command
{
    protected BootService $bootService;
    protected FrontendInterface $dependencyInjectionCache;

    public function __construct(
        BootService $bootService,
        FrontendInterface $dependencyInjectionCache
    ) {
        $this->bootService = $bootService;
        $this->dependencyInjectionCache = $dependencyInjectionCache;
        parent::__construct('cache:flush');
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setDescription('Flush TYPO3 caches.');
        $this->setHelp('This command is useful for deployments to clear caches during release postparation.');
        $this->setDefinition([
            new InputOption('group', 'g', InputOption::VALUE_OPTIONAL, 'The cache group to flush (system, pages, di or all)', 'all'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $group = $input->getOption('group') ?? 'all';

        $this->flushLegacyDatabaseCacheTables($group);
        $this->flushDependencyInjectionCaches($group);
        if ($group === 'di') {
            return 0;
        }

        $container = $this->bootService->getContainer(true);

        $this->flushCoreCaches($group, $container);

        $this->bootService->loadExtLocalconfDatabaseAndExtTables(false, true);

        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        $groups = $group === 'all' ? $container->get(CacheManager::class)->getCacheGroups() : [$group];
        $event = new CacheFlushEvent($groups);
        $eventDispatcher->dispatch($event);

        if (count($event->getErrors()) > 0) {
            return 1;
        }

        return 0;
    }

    protected function flushDependencyInjectionCaches(string $group): void
    {
        if ($group !== 'di' && $group !== 'system' && $group !== 'all') {
            return;
        }

        if ($this->dependencyInjectionCache->getBackend() instanceof ContainerBackend) {
            $diCacheBackend = $this->dependencyInjectionCache->getBackend();
            // We need to remove using the forceFlush method because the DI cache backend disables the flush method
            $diCacheBackend->forceFlush();
        }
    }

    protected function flushCoreCaches(string $group, ContainerInterface $container): void
    {
        if ($group !== 'system' && $group !== 'all') {
            return;
        }

        $container->get('cache.core')->flush();
    }

    protected function flushLegacyDatabaseCacheTables($group): void
    {
        if ($group !== 'all') {
            return;
        }

        $legacyDatabaseCacheTables = [
            'cache_treelist',
        ];

        // Low level flush of legacy database cache tables
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($legacyDatabaseCacheTables as $tableName) {
            $connection = $connectionPool->getConnectionForTable($tableName);
            $connection->truncate($tableName);
        }
    }
}
