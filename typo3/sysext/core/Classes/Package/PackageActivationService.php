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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        private PackageManager $packageManager,
        private CacheManager $cacheManager,
        private BootService $bootService,
        private OpcodeCacheService $opcodeCacheService,
        private EventDispatcherInterface $eventDispatcher,
        private ExtensionConfiguration $extensionConfiguration,
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
        $backupContainer = $this->bootService->makeCurrent($container);
        $backupTca = $GLOBALS['TCA'];
        // Reload cache files and Typo3LoadedExtensions
        $this->opcodeCacheService->clearAllActive();
        $container->get(ExtLocalconfFactory::class)->loadUncached();
        $tcaFactory = $container->get(TcaFactory::class);
        $GLOBALS['TCA'] = $tcaFactory->create();
        $container->get(ExtTablesFactory::class)->loadUncached();
        $container->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->updateDatabase();
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        foreach ($packages as $extensionKey => $package) {
            $eventDispatcher->dispatch(new PackageInitializationEvent($extensionKey, $package, $container, $emitter));
        }
        // Reset to the original container instance and original TCA
        $GLOBALS['TCA'] = $backupTca;
        $this->bootService->makeCurrent(null, $backupContainer);
    }

    public function reloadExtensionData(array $extensionKeys, ?object $emitter = null): void
    {
        foreach ($extensionKeys as $extensionKey) {
            try {
                $package = $this->packageManager->getPackage($extensionKey);
                $registryKey = $extensionKey . ':ext_tables_static+adt.sql';
                $this->registry->remove('extensionDataImport', $registryKey);
                $this->eventDispatcher->dispatch(
                    new PackageInitializationEvent(extensionKey: $extensionKey, package: $package, emitter: $emitter)
                );
            } catch (Exception\UnknownPackageException) {
            }
        }
    }

    public function updateDatabase(): void
    {
        // The following will fetch the currently active container
        // Either the one initialized during boot (used in the extension:setup command)
        // or the one set by the BootService
        // Do NOT replace this with DI injection of those services in this class,
        // as this would only ever get the services initialized during boot.
        $container = GeneralUtility::getContainer();
        $sqlReader = $container->get(SqlReader::class);
        $schemaMigrator = $container->get(SchemaMigrator::class);
        $sqlStatements = [];
        $sqlStatements[] = $sqlReader->getTablesDefinitionString();
        $sqlStatements = $sqlReader->getCreateTableStatementArray(implode(LF . LF, array_filter($sqlStatements)));
        $updateStatements = $schemaMigrator->getUpdateSuggestions($sqlStatements);
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
        $schemaMigrator->migrate($sqlStatements, $selectedStatements);
    }
}
