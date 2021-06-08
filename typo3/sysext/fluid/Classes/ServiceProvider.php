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
            Core\ViewHelper\ViewHelperResolver::class => [ static::class, 'getViewHelperResolver' ],
        ];
    }

    public static function getRenderingContextFactory(ContainerInterface $container): Core\Rendering\RenderingContextFactory
    {
        return self::new($container, Core\Rendering\RenderingContextFactory::class, [
            $container,
            $container->get(CacheManager::class),
            $container->get(Core\ViewHelper\ViewHelperResolver::class)
        ]);
    }

    public static function getViewHelperResolver(ContainerInterface $container): Core\ViewHelper\ViewHelperResolver
    {
        return self::new($container, Core\ViewHelper\ViewHelperResolver::class);
    }
}
