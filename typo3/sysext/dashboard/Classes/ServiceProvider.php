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

namespace TYPO3\CMS\Dashboard;

use ArrayObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class ServiceProvider extends AbstractServiceProvider
{
    private const CACHE_IDENTIFIER_PREFIX = 'Dashboard_';

    /**
     * @inheritDoc
     */
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    /**
     * @inheritDoc
     */
    public function getFactories(): array
    {
        return [
            'dashboard.presets' => [ static::class, 'getDashboardPresets' ],
            'dashboard.widgetGroups' => [ static::class, 'getWidgetGroups' ],
            'dashboard.widgets' => [ static::class, 'getWidgets' ],
            'dashboard.configuration.warmer' => [ static::class, 'getConfigurationWarmer' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            DashboardPresetRegistry::class => [ static::class, 'configureDashboardPresetRegistry' ],
            ListenerProvider::class => [ static::class, 'addEventListeners' ],
            WidgetGroupRegistry::class => [ static::class, 'configureWidgetGroupRegistry' ],
            'dashboard.presets' => [ static::class, 'configureDashboardPresets' ],
            'dashboard.widgetGroups' => [ static::class, 'configureWidgetGroups' ],
            'dashboard.widgets' => [ static::class, 'configureWidgets' ],
        ] + parent::getExtensions();
    }

    public static function getDashboardPresets(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject();
    }

    public static function getWidgetGroups(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject();
    }

    public static function getWidgets(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject();
    }

    public static function configureDashboardPresetRegistry(
        ContainerInterface $container,
        DashboardPresetRegistry $dashboardPresetRegistry = null
    ): DashboardPresetRegistry {
        $dashboardPresetRegistry = $dashboardPresetRegistry ?? self::new($container, DashboardPresetRegistry::class);
        $cache = $container->get('cache.core');

        $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix(self::CACHE_IDENTIFIER_PREFIX . 'Presets')->toString();
        if (!$dashboardPresetsFromPackages = $cache->require($cacheIdentifier)) {
            $dashboardPresetsFromPackages = $container->get('dashboard.presets')->getArrayCopy();
            $cache->set($cacheIdentifier, 'return ' . var_export($dashboardPresetsFromPackages, true) . ';');
        }

        foreach ($dashboardPresetsFromPackages as $identifier => $options) {
            $preset = new DashboardPreset(
                $identifier,
                $options['title'],
                $options['description'],
                $options['iconIdentifier'],
                $options['defaultWidgets'],
                $options['showInWizard']
            );
            $dashboardPresetRegistry->registerDashboardPreset($preset);
        }

        return $dashboardPresetRegistry;
    }

    public static function configureWidgetGroupRegistry(
        ContainerInterface $container,
        WidgetGroupRegistry $widgetGroupRegistry = null
    ): WidgetGroupRegistry {
        $widgetGroupRegistry = $widgetGroupRegistry ?? self::new($container, WidgetGroupRegistry::class);
        $cache = $container->get('cache.core');

        $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix(self::CACHE_IDENTIFIER_PREFIX . 'WidgetGroups')->toString();
        if (!$widgetGroupsFromPackages = $cache->require($cacheIdentifier)) {
            $widgetGroupsFromPackages = $container->get('dashboard.widgetGroups')->getArrayCopy();
            $cache->set($cacheIdentifier, 'return ' . var_export($widgetGroupsFromPackages, true) . ';');
        }

        foreach ($widgetGroupsFromPackages as $identifier => $options) {
            $group = new WidgetGroup(
                $identifier,
                $options['title']
            );
            $widgetGroupRegistry->registerWidgetGroup($group);
        }

        return $widgetGroupRegistry;
    }

    /**
     * @param ContainerInterface $container
     * @param ArrayObject $presets
     * @return ArrayObject
     */
    public static function configureDashboardPresets(ContainerInterface $container, ArrayObject $presets): ArrayObject
    {
        $paths = self::getPathsOfInstalledPackages();

        foreach ($paths as $pathOfPackage) {
            $dashboardPresetsFileNameForPackage = $pathOfPackage . 'Configuration/Backend/DashboardPresets.php';
            if (file_exists($dashboardPresetsFileNameForPackage)) {
                $definedPresetsInPackage = require $dashboardPresetsFileNameForPackage;
                if (is_array($definedPresetsInPackage)) {
                    $presets->exchangeArray(array_merge($presets->getArrayCopy(), $definedPresetsInPackage));
                }
            }
        }

        return $presets;
    }

    /**
     * @param ContainerInterface $container
     * @param ArrayObject $widgetGroups
     * @param string $path supplied when invoked internally through PseudoServiceProvider
     * @return ArrayObject
     */
    public static function configureWidgetGroups(ContainerInterface $container, ArrayObject $widgetGroups, string $path = null): ArrayObject
    {
        $paths = self::getPathsOfInstalledPackages();

        foreach ($paths as $pathOfPackage) {
            $widgetGroupsFileNameForPackage = $pathOfPackage . 'Configuration/Backend/DashboardWidgetGroups.php';
            if (file_exists($widgetGroupsFileNameForPackage)) {
                $definedGroupsInPackage = require $widgetGroupsFileNameForPackage;
                if (is_array($definedGroupsInPackage)) {
                    $widgetGroups->exchangeArray(array_merge($widgetGroups->getArrayCopy(), $definedGroupsInPackage));
                }
            }
        }
        return $widgetGroups;
    }

    /**
     * @param ContainerInterface $container
     * @param ArrayObject $widgets
     * @param string $path supplied when invoked internally through PseudoServiceProvider
     * @return ArrayObject
     */
    public static function configureWidgets(ContainerInterface $container, ArrayObject $widgets, string $path = null): ArrayObject
    {
        $paths = self::getPathsOfInstalledPackages();

        foreach ($paths as $pathOfPackage) {
            $widgetsFileNameForPackage = $pathOfPackage . 'Configuration/Backend/DashboardWidgets.php';
            if (file_exists($widgetsFileNameForPackage)) {
                $definedWidgetsInPackage = require $widgetsFileNameForPackage;
                if (is_array($definedWidgetsInPackage)) {
                    $widgets->exchangeArray(array_merge($widgets->getArrayCopy(), $definedWidgetsInPackage));
                }
            }
        }
        return $widgets;
    }

    protected static function getPathsOfInstalledPackages(): array
    {
        $paths = [];
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);

        foreach ($packageManager->getActivePackages() as $package) {
            $paths[] = $package->getPackagePath();
        }

        return $paths;
    }

    public static function getConfigurationWarmer(ContainerInterface $container): \Closure
    {
        $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class);
        $presetsCacheIdentifier = $cacheIdentifier->withPrefix(self::CACHE_IDENTIFIER_PREFIX . 'Presets')->toString();
        $widgetGroupsCacheIdentifier = $cacheIdentifier->withPrefix(self::CACHE_IDENTIFIER_PREFIX . 'WidgetGroups')->toString();
        return static function (CacheWarmupEvent $event) use ($container, $presetsCacheIdentifier, $widgetGroupsCacheIdentifier) {
            if ($event->hasGroup('system')) {
                $cache = $container->get('cache.core');

                $dashboardPresetsFromPackages = $container->get('dashboard.presets')->getArrayCopy();
                $cache->set($presetsCacheIdentifier, 'return ' . var_export($dashboardPresetsFromPackages, true) . ';');

                $widgetGroupsFromPackages = $container->get('dashboard.widgetGroups')->getArrayCopy();
                $cache->set($widgetGroupsCacheIdentifier, 'return ' . var_export($widgetGroupsFromPackages, true) . ';');
            }
        };
    }

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(CacheWarmupEvent::class, 'dashboard.configuration.warmer');

        return $listenerProvider;
    }
}
