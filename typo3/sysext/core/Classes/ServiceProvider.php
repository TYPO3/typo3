<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core;

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
use TYPO3\CMS\Core\Package\AbstractServiceProvider;

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
            Cache\CacheManager::class => [ static::class, 'getCacheManager' ],
            Console\CommandApplication::class => [ static::class, 'getConsoleCommandApplication' ],
            Context\Context::class => [ static::class, 'getContext' ],
            EventDispatcher\EventDispatcher::class => [ static::class, 'getEventDispatcher' ],
            EventDispatcher\ListenerProvider::class => [ static::class, 'getEventListenerProvider' ],
            Http\MiddlewareStackResolver::class => [ static::class, 'getMiddlewareStackResolver' ],
            Service\DependencyOrderingService::class => [ static::class, 'getDependencyOrderingService' ],
            Crypto\PasswordHashing\PasswordHashFactory::class => [ static::class, 'getPasswordHashFactory' ],
            'middlewares' => [ static::class, 'getMiddlewares' ],
        ];
    }

    public static function getCacheManager(ContainerInterface $container): Cache\CacheManager
    {
        if (!$container->get('boot.state')->done) {
            throw new \LogicException(Cache\CacheManager::class . ' can not be injected/instantiated during ext_localconf.php loading. Use lazy loading instead.', 1549446998);
        }

        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        $disableCaching = $container->get('cache.disabled');
        $defaultCaches = [
            $container->get('cache.core'),
            $container->get('cache.assets'),
        ];

        $cacheManager = self::new($container, Cache\CacheManager::class, [$disableCaching]);
        $cacheManager->setCacheConfigurations($cacheConfigurations);
        foreach ($defaultCaches as $cache) {
            $cacheManager->registerCache($cache, $cacheConfigurations[$cache->getIdentifier()]['groups'] ?? ['all']);
        }

        return $cacheManager;
    }

    public static function getConsoleCommandApplication(ContainerInterface $container): Console\CommandApplication
    {
        return new Console\CommandApplication($container->get(Context\Context::class));
    }

    public static function getEventDispatcher(ContainerInterface $container): EventDispatcher\EventDispatcher
    {
        return new EventDispatcher\EventDispatcher(
            $container->get(EventDispatcher\ListenerProvider::class)
        );
    }

    public static function getEventListenerProvider(ContainerInterface $container): EventDispatcher\ListenerProvider
    {
        return new EventDispatcher\ListenerProvider($container);
    }

    public static function getDependencyOrderingService(ContainerInterface $container): Service\DependencyOrderingService
    {
        return new Service\DependencyOrderingService;
    }

    public static function getContext(ContainerInterface $container): Context\Context
    {
        return new Context\Context;
    }

    public static function getPasswordHashFactory(ContainerInterface $container): Crypto\PasswordHashing\PasswordHashFactory
    {
        return new Crypto\PasswordHashing\PasswordHashFactory;
    }

    public static function getMiddlewareStackResolver(ContainerInterface $container): Http\MiddlewareStackResolver
    {
        return new Http\MiddlewareStackResolver(
            $container,
            $container->get(Service\DependencyOrderingService::class),
            $container->get('cache.core')
        );
    }

    public static function getMiddlewares(ContainerInterface $container): array
    {
        return [];
    }
}
