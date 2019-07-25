<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Service;

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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This is NOT an API class, it is for internal use in the install tool only.
 */
class LateBootService
{
    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var ContainerInterface
     */
    private $failsafeContainer;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerBuilder $containerBuilder
     * @param ConteinerInterface $failsafeContainer
     */
    public function __construct(ContainerBuilder $containerBuilder, ContainerInterface $failsafeContainer)
    {
        $this->containerBuilder = $containerBuilder;
        $this->failsafeContainer = $failsafeContainer;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container ?? $this->prepareContainer();
    }

    /**
     * @return ContainerInterface
     */
    private function prepareContainer(): ContainerInterface
    {
        $packageManager = $this->failsafeContainer->get(PackageManager::class);

        // Use caching for the full boot – uncached symfony autowiring for every install-tool lateboot request would be too slow.
        $disableCaching = false;
        $coreCache = Bootstrap::createCache('core', $disableCaching);

        $failsafe = false;

        // Build a non-failsafe container which is required for loading ext_localconf
        return $this->container = $this->containerBuilder->createDependencyInjectionContainer($packageManager, $coreCache, $failsafe);
    }

    /**
     * Switch global context to a new context, or revert
     * to the original booting container if no container
     * is specified
     *
     * @param ContainerInterface $container
     * @param array $backup
     * @return array
     */
    public function makeCurrent(ContainerInterface $container = null, array $oldBackup = []): array
    {
        $container = $container ?? $this->failsafeContainer;

        $backup = [
            'singletonInstances', GeneralUtility::getSingletonInstances(),
        ];

        GeneralUtility::purgeInstances();

        // Set global state to the non-failsafe container and it's instances
        GeneralUtility::setContainer($container);
        ExtensionManagementUtility::setPackageManager($container->get(PackageManager::class));

        $backupSingletonInstances = $oldBackup['singletonInstances'] ?? [];
        foreach ($backupSingletonInstances as $className => $instance) {
            GeneralUtility::setSingletonInstance($className, $instance);
        }

        return $backup;
    }

    /**
     * Bootstrap a non-failsafe container and load ext_localconf
     *
     * @return ContainerInterface
     */
    public function loadExtLocalconfDatabaseAndExtTables(): ContainerInterface
    {
        $container = $this->getContainer();

        $backup = $this->makeCurrent($container);

        $container->get('boot.state')->done = false;
        $assetsCache = $container->get('cache.assets');
        IconRegistry::setCache($assetsCache);
        PageRenderer::setCache($assetsCache);
        Bootstrap::loadTypo3LoadedExtAndExtLocalconf(false);
        Bootstrap::unsetReservedGlobalVariables();
        $container->get('boot.state')->done = true;
        Bootstrap::loadBaseTca(false);
        Bootstrap::loadExtTables(false);

        $this->makeCurrent(null, $backup);

        return $container;
    }
}
