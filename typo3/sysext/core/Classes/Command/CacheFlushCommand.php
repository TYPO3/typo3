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
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\CacheFlushEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\DependencyInjection\Cache\ContainerBackend;

class CacheFlushCommand extends Command
{
    public function __construct(
        protected readonly BootService $bootService,
        protected readonly FrontendInterface $dependencyInjectionCache
    ) {
        parent::__construct('cache:flush');
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setDescription('Flush TYPO3 caches.');
        $this->setHelp(
            'Clears TYPO3 caches. '
            . 'Useful after code changes during development or after deployments. '
            . 'You can flush a specific cache group (system, pages, di) or all caches.'
        );
        $this->setDefinition([
            new InputOption('group', 'g', InputOption::VALUE_OPTIONAL, 'Cache group to flush (system, pages, di, or all).', 'all'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $group = $input->getOption('group') ?? 'all';

        $this->flushDependencyInjectionCaches($group);
        if ($group === 'di') {
            if ($output->isVerbose()) {
                $io->success('Dependency Injection caches flushed.');
            }
            return Command::SUCCESS;
        }

        $container = $this->bootService->getContainer(true);

        $this->flushCoreCaches($group, $container);

        $this->bootService->loadExtLocalconfDatabaseAndExtTables(false, true);

        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        $groups = $group === 'all' ? $container->get(CacheManager::class)->getCacheGroups() : [$group];
        $event = new CacheFlushEvent($groups);
        $eventDispatcher->dispatch($event);

        if (count($event->getErrors()) > 0) {
            $io->error('Errors occurred while flushing caches.');
            foreach ($event->getErrors() as $error) {
                $io->error($error);
            }
            return Command::FAILURE;
        }
        if ($output->isVerbose()) {
            if ($group === 'all') {
                $io->success('All caches flushed.');
            } else {
                $io->success(sprintf('Caches for group "%s" flushed.', $group));
            }
        }
        return Command::SUCCESS;
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
}
