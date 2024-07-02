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

namespace TYPO3\CMS\Core\Package;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Extension\ExtLocalconfFactory;
use TYPO3\CMS\Core\Configuration\Extension\ExtTablesFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Tca\TcaFactory;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Service for activating packages, enabling further initialization
 * functionality by dispatching the PackageInitializationEvent.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class PackageActivationService
{
    public function __construct(
        private Registry $registry,
        private SqlReader $sqlReader,
        private SchemaMigrator $schemaMigrator,
        private PackageManager $packageManager,
        private CacheManager $cacheManager,
        private BootService $bootService,
        private OpcodeCacheService $opcodeCacheService,
        private EventDispatcherInterface $eventDispatcher,
        private ExtensionConfiguration $extensionConfiguration,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function activate(array $extensionKeys, ?object $emitter = null): void
    {
        $packages = [];
        foreach ($extensionKeys as $extensionKey) {
            $this->packageManager->activatePackage($extensionKey);
            $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration($extensionKey);
            $packages[$extensionKey] = $this->packageManager->getPackage($extensionKey);
        }
        $this->cacheManager->flushCaches();
        // Load a new container as we are reloading ext_localconf.php files
        $container = $this->bootService->getContainer(false);
        $backup = $this->bootService->makeCurrent($container);
        // Reload cache files and Typo3LoadedExtensions
        $this->opcodeCacheService->clearAllActive();
        $container->get(ExtLocalconfFactory::class)->loadUncached();
        $tcaFactory = $container->get(TcaFactory::class);
        $GLOBALS['TCA'] = $tcaFactory->create();
        $container->get(ExtTablesFactory::class)->loadUncached();
        $this->tcaSchemaFactory->rebuild($GLOBALS['TCA']);
        $this->updateDatabase();
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        foreach ($packages as $extensionKey => $package) {
            $eventDispatcher->dispatch(new PackageInitializationEvent($extensionKey, $package, $container, $emitter));
        }
        // Reset to the original container instance
        $this->bootService->makeCurrent(null, $backup);
    }

    public function reloadExtensionData(array $extensionKeys, ?object $emitter = null): void
    {
        foreach ($extensionKeys as $extensionKey) {
            try {
                $package = $this->packageManager->getPackage($extensionKey);
                $this->registry->remove(
                    'extensionDataImport',
                    PathUtility::stripPathSitePrefix($package->getPackagePath() . 'ext_tables_static+adt.sql')
                );
                $this->eventDispatcher->dispatch(
                    new PackageInitializationEvent(extensionKey: $extensionKey, package: $package, emitter: $emitter)
                );
            } catch (Exception\UnknownPackageException) {
            }
        }
    }

    public function updateDatabase(): void
    {
        $sqlStatements = [];
        $sqlStatements[] = $this->sqlReader->getTablesDefinitionString();
        $sqlStatements = $this->sqlReader->getCreateTableStatementArray(implode(LF . LF, array_filter($sqlStatements)));
        $updateStatements = $this->schemaMigrator->getUpdateSuggestions($sqlStatements);
        $updateStatements = array_merge_recursive(...array_values($updateStatements));
        $selectedStatements = [];
        foreach (['add', 'change', 'create_table', 'change_table'] as $action) {
            if (empty($updateStatements[$action])) {
                continue;
            }
            $statements = array_combine(array_keys($updateStatements[$action]), array_fill(0, count($updateStatements[$action]), true));
            $selectedStatements = array_merge(
                $selectedStatements,
                $statements
            );
        }
        $this->schemaMigrator->migrate($sqlStatements, $selectedStatements);
    }
}
