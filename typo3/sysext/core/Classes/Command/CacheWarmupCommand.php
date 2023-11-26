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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Extension\ExtLocalconfFactory;
use TYPO3\CMS\Core\Configuration\Extension\ExtTablesFactory;
use TYPO3\CMS\Core\Configuration\Tca\TcaFactory;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Package\PackageManager;

class CacheWarmupCommand extends Command
{
    public function __construct(
        protected readonly ContainerBuilder $containerBuilder,
        protected readonly PackageManager $packageManager,
        protected readonly BootService $bootService,
        protected readonly FrontendInterface $dependencyInjectionCache
    ) {
        parent::__construct('cache:warmup');
    }

    /**
     * Defines the allowed options for this command
     */
    protected function configure(): void
    {
        $this->setDescription('Warmup TYPO3 caches.');
        $this->setHelp('This command is useful for deployments to warmup caches during release preparation.');
        $this->setDefinition([
            new InputOption('group', 'g', InputOption::VALUE_OPTIONAL, 'The cache group to warmup (system, pages, di or all)', 'all'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $group = $input->getOption('group') ?? 'all';

        if ($group === 'di' || $group === 'system' || $group === 'all') {
            $this->containerBuilder->warmupCache($this->packageManager, $this->dependencyInjectionCache);
            if ($group === 'di') {
                return Command::SUCCESS;
            }
        }

        $container = $this->bootService->getContainer();

        $allowExtFileCaches = true;
        if ($group === 'system' || $group === 'all') {
            $allowExtFileCaches = false;
            $container->get(ExtLocalconfFactory::class)->createCacheEntry();
            $container->get(ExtTablesFactory::class)->createCacheEntry();
        }
        // Perform a full boot to load localconf (requirement for extensions and for TCA loading).
        $this->bootService->loadExtLocalconfDatabaseAndExtTables(false, $allowExtFileCaches, false);
        if ($group === 'system' || $group === 'all') {
            $tcaFactory = $container->get(TcaFactory::class);
            $tcaFactory->createBaseTcaCacheFile($GLOBALS['TCA']);
        }
        if ($allowExtFileCaches) {
            $container->get(ExtTablesFactory::class)->load();
        } else {
            $container->get(ExtTablesFactory::class)->loadUncached();
        }

        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        $groups = $group === 'all' ? $container->get(CacheManager::class)->getCacheGroups() : [$group];
        $event = new CacheWarmupEvent($groups);
        $eventDispatcher->dispatch($event);

        if (count($event->getErrors()) > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
