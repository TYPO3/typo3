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

namespace TYPO3\CMS\Extbase;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
            Container::class => [ static::class, 'getObjectContainer' ],
            ObjectManager::class => [ static::class, 'getObjectManager' ],
            Dispatcher::class => [ static::class, 'getSignalSlotDispatcher' ],
            ConfigurationManager::class => [ static::class, 'getConfigurationManager' ],
            ReflectionService::class => [ static::class, 'getReflectionService' ],
            EnvironmentService::class => [ static::class, 'getEnvironmentService' ],
            ExtensionService::class => [ static::class, 'getExtensionService' ],
            HashService::class => [ static::class, 'getHashService' ],
        ];
    }

    public static function getObjectContainer(ContainerInterface $container): Container
    {
        return self::new($container, Container::class, [$container]);
    }

    public static function getObjectManager(ContainerInterface $container): ObjectManager
    {
        return self::new($container, ObjectManager::class, [$container, $container->get(Container::class)]);
    }

    public static function getSignalSlotDispatcher(ContainerInterface $container): Dispatcher
    {
        $logger = $container->get(LogManager::class)->getLogger(Dispatcher::class);
        return self::new($container, Dispatcher::class, [$container->get(ObjectManager::class), $logger]);
    }

    public static function getConfigurationManager(ContainerInterface $container): ConfigurationManager
    {
        return self::new($container, ConfigurationManager::class, [
            $container->get(ObjectManager::class),
            $container->get(EnvironmentService::class),
        ]);
    }

    public static function getReflectionService(ContainerInterface $container): ReflectionService
    {
        return self::new($container, ReflectionService::class, [$container->get(CacheManager::class)]);
    }

    public static function getEnvironmentService(ContainerInterface $container): EnvironmentService
    {
        return self::new($container, EnvironmentService::class);
    }

    public static function getExtensionService(ContainerInterface $container): ExtensionService
    {
        $extensionService = self::new($container, ExtensionService::class);
        $extensionService->injectObjectManager($container->get(ObjectManager::class));
        $extensionService->injectConfigurationManager($container->get(ConfigurationManager::class));
        return $extensionService;
    }

    public static function getHashService(ContainerInterface $container): HashService
    {
        return self::new($container, HashService::class);
    }
}
