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

namespace TYPO3\CMS\Backend;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;

/**
 * @internal
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'typo3/cms-backend';
    }

    public function getFactories(): array
    {
        return [
            ModuleRegistry::class => self::getModuleRegistry(...),
            'backend.routes' => self::getBackendRoutes(...),
            'backend.routes.warmer' => self::getBackendRoutesWarmer(...),
            'backend.modules' => self::getBackendModules(...),
            'backend.modules.warmer' => self::getBackendModulesWarmer(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProvider::class => self::addEventListeners(...),
        ] + parent::getExtensions();
    }

    public static function getModuleRegistry(ContainerInterface $container): ModuleRegistry
    {
        $moduleFactory = $container->get(ModuleFactory::class);
        $cache = $container->get('cache.core');
        $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('BackendModules')->toString();
        $modulesFromPackages = $cache->require($cacheIdentifier);
        if ($modulesFromPackages === false) {
            $modulesFromPackages = $container->get('backend.modules')->getArrayCopy();
            $modulesFromPackages = $moduleFactory->adaptAliasMappingFromModuleConfiguration($modulesFromPackages);
            $cache->set($cacheIdentifier, 'return ' . var_export($modulesFromPackages, true) . ';');
        }

        foreach ($modulesFromPackages as $identifier => $configuration) {
            $modulesFromPackages[$identifier] = $moduleFactory->createModule($identifier, $configuration);
        }

        return self::new($container, ModuleRegistry::class, [$modulesFromPackages]);
    }

    public static function getBackendRoutes(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject();
    }

    public static function getBackendRoutesWarmer(ContainerInterface $container): \Closure
    {
        return static function (CacheWarmupEvent $event) use ($container) {
            if ($event->hasGroup('system')) {
                $cache = $container->get('cache.core');
                $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('BackendRoutes')->toString();
                $routesFromPackages = $container->get('backend.routes')->getArrayCopy();
                $cache->set($cacheIdentifier, 'return ' . var_export($routesFromPackages, true) . ';');
            }
        };
    }

    public static function getBackendModules(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject();
    }

    public static function getBackendModulesWarmer(ContainerInterface $container): \Closure
    {
        return static function (CacheWarmupEvent $event) use ($container) {
            if ($event->hasGroup('system')) {
                $cache = $container->get('cache.core');
                $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('BackendModules')->toString();
                $modulesFromPackages = $container->get('backend.modules')->getArrayCopy();
                $cache->set($cacheIdentifier, 'return ' . var_export($modulesFromPackages, true) . ';');
            }
        };
    }

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(CacheWarmupEvent::class, 'backend.routes.warmer');
        $listenerProvider->addListener(CacheWarmupEvent::class, 'backend.modules.warmer');

        return $listenerProvider;
    }
}
