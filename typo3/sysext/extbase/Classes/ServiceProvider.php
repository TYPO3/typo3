<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase;

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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Log\LogManager;
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
            Object\Container\Container::class => [ static::class, 'getObjectContainer' ],
            Object\ObjectManager::class => [ static::class, 'getObjectManager' ],
            SignalSlot\Dispatcher::class => [ static::class, 'getSignalSlotDispatcher' ],
            Configuration\ConfigurationManager::class => [ static::class, 'getConfigurationManager' ],
            Reflection\ReflectionService::class => [ static::class, 'getReflectionService' ],
            Service\EnvironmentService::class => [ static::class, 'getEnvironmentService' ],
            Service\ExtensionService::class => [ static::class, 'getExtensionService' ],
            Security\Cryptography\HashService::class => [ static::class, 'getHashService' ],
        ];
    }

    public static function getObjectContainer(ContainerInterface $container): Object\Container\Container
    {
        return self::new($container, Object\Container\Container::class, [$container]);
    }

    public static function getObjectManager(ContainerInterface $container): Object\ObjectManager
    {
        return self::new($container, Object\ObjectManager::class, [$container, $container->get(Object\Container\Container::class)]);
    }

    public static function getSignalSlotDispatcher(ContainerInterface $container): SignalSlot\Dispatcher
    {
        $logger = $container->get(LogManager::class)->getLogger(SignalSlot\Dispatcher::class);
        return self::new($container, SignalSlot\Dispatcher::class, [$container->get(Object\ObjectManager::class), $logger]);
    }

    public static function getConfigurationManager(ContainerInterface $container): Configuration\ConfigurationManager
    {
        return self::new($container, Configuration\ConfigurationManager::class, [
            $container->get(Object\ObjectManager::class),
            $container->get(Service\EnvironmentService::class),
        ]);
    }

    public static function getReflectionService(ContainerInterface $container): Reflection\ReflectionService
    {
        return self::new($container, Reflection\ReflectionService::class, [$container->get(CacheManager::class)]);
    }

    public static function getEnvironmentService(ContainerInterface $container): Service\EnvironmentService
    {
        return self::new($container, Service\EnvironmentService::class);
    }

    public static function getExtensionService(ContainerInterface $container): Service\ExtensionService
    {
        $extensionService = self::new($container, Service\ExtensionService::class);
        $extensionService->injectObjectManager($container->get(Object\ObjectManager::class));
        $extensionService->injectConfigurationManager($container->get(Configuration\ConfigurationManager::class));
        return $extensionService;
    }

    public static function getHashService(ContainerInterface $container): Security\Cryptography\HashService
    {
        return self::new($container, Security\Cryptography\HashService::class);
    }
}
