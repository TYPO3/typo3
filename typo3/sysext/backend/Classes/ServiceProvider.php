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
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Http\Application;
use TYPO3\CMS\Backend\Http\RequestHandler;
use TYPO3\CMS\Backend\Http\RouteDispatcher;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessFactory;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Routing\RequestContextFactory;

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
            Application::class => [ static::class, 'getApplication' ],
            RequestHandler::class => [ static::class, 'getRequestHandler' ],
            RouteDispatcher::class => [ static::class, 'getRouteDispatcher' ],
            UriBuilder::class => [ static::class, 'getUriBuilder' ],
            ModuleProvider::class => [ static::class, 'getModuleProvider' ],
            ModuleFactory::class => [ static::class, 'getModuleFactory' ],
            ModuleRegistry::class => [ static::class, 'getModuleRegistry' ],
            'backend.middlewares' => [ static::class, 'getBackendMiddlewares' ],
            'backend.routes' => [ static::class, 'getBackendRoutes' ],
            'backend.routes.warmer' => [ static::class, 'getBackendRoutesWarmer' ],
            'backend.modules' => [ static::class, 'getBackendModules' ],
            'backend.modules.warmer' => [ static::class, 'getBackendModulesWarmer' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            Router::class => [ static::class, 'configureBackendRouter' ],
            ListenerProvider::class => [ static::class, 'addEventListeners' ],
        ] + parent::getExtensions();
    }

    public static function getApplication(ContainerInterface $container): Application
    {
        $requestHandler = new MiddlewareDispatcher(
            $container->get(RequestHandler::class),
            $container->get('backend.middlewares'),
            $container
        );
        return new Application(
            $requestHandler,
            $container->get(ConfigurationManager::class),
            $container->get(Context::class),
            $container->get(BackendEntryPointResolver::class)
        );
    }

    public static function getRequestHandler(ContainerInterface $container): RequestHandler
    {
        return new RequestHandler(
            $container->get(RouteDispatcher::class),
            $container->get(UriBuilder::class),
            $container->get(ListenerProvider::class)
        );
    }

    public static function getRouteDispatcher(ContainerInterface $container): RouteDispatcher
    {
        return self::new($container, RouteDispatcher::class, [
            $container->get(FormProtectionFactory::class),
            $container->get(AccessFactory::class),
            $container->get(AccessStorage::class),
            $container,
        ]);
    }

    public static function getUriBuilder(ContainerInterface $container): UriBuilder
    {
        return self::new($container, UriBuilder::class, [
            $container->get(Router::class),
            $container->get(FormProtectionFactory::class),
            $container->get(RequestContextFactory::class),
        ]);
    }

    public static function getModuleProvider(ContainerInterface $container): ModuleProvider
    {
        return self::new($container, ModuleProvider::class, [
            $container->get(ModuleRegistry::class),
        ]);
    }

    public static function getModuleFactory(ContainerInterface $container): ModuleFactory
    {
        return self::new($container, ModuleFactory::class, [
            $container->get(IconRegistry::class),
            $container->get(EventDispatcherInterface::class),
        ]);
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

    /**
     * @throws InvalidDataException
     * @throws CoreException
     */
    public static function getBackendMiddlewares(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject($container->get(MiddlewareStackResolver::class)->resolve('backend'));
    }

    public static function configureBackendRouter(ContainerInterface $container, Router $router = null): Router
    {
        $router = $router ?? self::new($container, Router::class, [$container->get(RequestContextFactory::class)]);
        $cache = $container->get('cache.core');

        $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('BackendRoutes')->toString();
        $routesFromPackages = $cache->require($cacheIdentifier);
        if ($routesFromPackages === false) {
            $routesFromPackages = $container->get('backend.routes')->getArrayCopy();
            $cache->set($cacheIdentifier, 'return ' . var_export($routesFromPackages, true) . ';');
        }

        foreach ($routesFromPackages as $name => $options) {
            $path = $options['path'];
            $methods = $options['methods'] ?? [];
            $aliases = $options['aliases'] ?? [];
            unset($options['path'], $options['methods'], $options['aliases']);
            $route = new Route($path, $options);
            if (count($methods) > 0) {
                $route->setMethods($methods);
            }
            $router->addRoute($name, $route, $aliases);
        }

        // Add routes from all modules
        $container->get(ModuleRegistry::class)->registerRoutesForModules($router);

        return $router;
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
