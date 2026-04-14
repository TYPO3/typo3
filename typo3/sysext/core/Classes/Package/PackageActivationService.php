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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Extension\ExtLocalconfFactory;
use TYPO3\CMS\Core\Configuration\Extension\ExtTablesFactory;
use TYPO3\CMS\Core\Configuration\Tca\TcaFactory;
use TYPO3\CMS\Core\Core\BootService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\OpcodeCacheService;

/**
 * Service for activating packages in classic mode.
 * Takes care of DI container juggling and flushing caches,
 * while also setting up the activated package
 *
 * @internal Only for use in InstallUtility
 */
#[Autoconfigure(public: true)]
class PackageActivationService
{
    public function __construct(
        private PackageManager $packageManager,
        private BootService $bootService,
        private OpcodeCacheService $opcodeCacheService,
    ) {}

    public function activate(array $packageKeys, ?object $emitter = null): void
    {
        $packages = [];
        foreach ($packageKeys as $packageKey) {
            $this->packageManager->activatePackage($packageKey);
            $packages[$packageKey] = $this->packageManager->getPackage($packageKey);
        }
        // Load a new container as we are reloading ext_localconf.php files
        $container = $this->bootService->getContainer();
        $backupContainer = $this->bootService->makeCurrent($container);

        // Reload cache files and Typo3LoadedExtensions
        $backupTca = $GLOBALS['TCA'];
        $container->get(CacheManager::class)->flushCaches();
        $this->opcodeCacheService->clearAllActive();
        $container->get(ExtLocalconfFactory::class)->loadUncached();
        $tcaFactory = $container->get(TcaFactory::class);
        $GLOBALS['TCA'] = $tcaFactory->create();
        $container->get(ExtTablesFactory::class)->loadUncached();
        $container->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Set up packages
        $packageSetup = $container->get(PackageSetup::class);
        $packageSetup->setup($packages, $emitter);

        // Reset to the original container instance and original TCA
        $GLOBALS['TCA'] = $backupTca;
        $this->bootService->makeCurrent(null, $backupContainer);
    }
}
