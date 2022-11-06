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
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Resource\ResourceFactory;

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
        return 'typo3/cms-extbase';
    }

    public function getFactories(): array
    {
        return [
            Configuration\ConfigurationManager::class => [ static::class, 'getConfigurationManager' ],
            Reflection\ReflectionService::class => [ static::class, 'getReflectionService' ],
            Service\ExtensionService::class => [ static::class, 'getExtensionService' ],
            Service\ImageService::class => [ static::class, 'getImageService' ],
            Security\Cryptography\HashService::class => [ static::class, 'getHashService' ],
        ];
    }

    public static function getConfigurationManager(ContainerInterface $container): Configuration\ConfigurationManager
    {
        return self::new($container, Configuration\ConfigurationManager::class, [$container]);
    }

    public static function getReflectionService(ContainerInterface $container): Reflection\ReflectionService
    {
        return self::new($container, Reflection\ReflectionService::class, [$container->get(CacheManager::class)->getCache('extbase'), $container->get(PackageDependentCacheIdentifier::class)->withPrefix('ClassSchemata')->toString()]);
    }

    public static function getExtensionService(ContainerInterface $container): Service\ExtensionService
    {
        $extensionService = self::new($container, Service\ExtensionService::class);
        $extensionService->injectConfigurationManager($container->get(Configuration\ConfigurationManager::class));
        return $extensionService;
    }

    public static function getImageService(ContainerInterface $container): Service\ImageService
    {
        return self::new($container, Service\ImageService::class, [
            $container->get(ResourceFactory::class),
        ]);
    }

    public static function getHashService(ContainerInterface $container): Security\Cryptography\HashService
    {
        return self::new($container, Security\Cryptography\HashService::class);
    }
}
