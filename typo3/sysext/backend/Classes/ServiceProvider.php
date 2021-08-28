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

use ArrayObject;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Backend\Http\Application;
use TYPO3\CMS\Backend\Http\RequestHandler;
use TYPO3\CMS\Backend\Http\RouteDispatcher;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
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

    public function getFactories(): array
    {
        return [
            Application::class => [ static::class, 'getApplication' ],
            RequestHandler::class => [ static::class, 'getRequestHandler' ],
            RouteDispatcher::class => [ static::class, 'getRouteDispatcher' ],
            UriBuilder::class => [ static::class, 'getUriBuilder' ],
            'backend.middlewares' => [ static::class, 'getBackendMiddlewares' ],
            'backend.routes' => [ static::class, 'getBackendRoutes' ],
            'backend.routes.warmer' => [ static::class, 'getBackendRoutesWarmer' ],
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
            $container->get(Context::class)
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
            $container,
            $container->get(UriBuilder::class),
        ]);
    }

    public static function getUriBuilder(ContainerInterface $container): UriBuilder
    {
        return self::new($container, UriBuilder::class, [
            $container->get(Router::class),
        ]);
    }

    /**
     * @param ContainerInterface $container
     * @return ArrayObject
     * @throws InvalidDataException
     * @throws CoreException
     */
    public static function getBackendMiddlewares(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject($container->get(MiddlewareStackResolver::class)->resolve('backend'));
    }

    public static function configureBackendRouter(ContainerInterface $container, Router $router = null): Router
    {
        $router = $router ?? self::new($container, Router::class);
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
            unset($options['path']);
            unset($options['methods']);
            $route = new Route($path, $options);
            if (count($methods) > 0) {
                $route->setMethods($methods);
            }
            $router->addRoute($name, $route);
        }

        return $router;
    }

    public static function getBackendRoutes(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject();
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

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(CacheWarmupEvent::class, 'backend.routes.warmer');

        return $listenerProvider;
    }
}
