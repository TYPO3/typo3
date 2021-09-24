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

namespace TYPO3\CMS\Fluid;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
            Core\Rendering\RenderingContextFactory::class => [ static::class, 'getRenderingContextFactory' ],
            Core\ViewHelper\ViewHelperResolverFactory::class => [ static::class, 'getViewHelperResolverFactory' ],
            Core\ViewHelper\ViewHelperResolverFactoryInterface::class => [ static::class, 'getViewHelperResolverFactoryInterface' ],
        ];
    }

    public static function getRenderingContextFactory(ContainerInterface $container): Core\Rendering\RenderingContextFactory
    {
        return self::new($container, Core\Rendering\RenderingContextFactory::class, [
            $container,
            $container->get(CacheManager::class),
            $container->get(Core\ViewHelper\ViewHelperResolverFactoryInterface::class),
        ]);
    }

    public static function getViewHelperResolverFactory(ContainerInterface $container): Core\ViewHelper\ViewHelperResolverFactory
    {
        return self::new($container, Core\ViewHelper\ViewHelperResolverFactory::class, [
            $container,
            // @deprecated since v11, will be removed with 12.
            $container->get(ObjectManager::class),
        ]);
    }

    public static function getViewHelperResolverFactoryInterface(ContainerInterface $container): Core\ViewHelper\ViewHelperResolverFactoryInterface
    {
        return $container->get(Core\ViewHelper\ViewHelperResolverFactory::class);
    }
}
