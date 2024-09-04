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
use TYPO3\CMS\Core\View\FluidViewFactory;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

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
        return 'typo3/cms-fluid';
    }

    public function getFactories(): array
    {
        return [
            Core\Rendering\RenderingContextFactory::class => self::getRenderingContextFactory(...),
            Core\ViewHelper\ViewHelperResolverFactory::class => self::getViewHelperResolverFactory(...),
            Core\ViewHelper\ViewHelperResolverFactoryInterface::class => self::getViewHelperResolverFactoryInterface(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ViewFactoryInterface::class => self::provideFallbackViewFactory(...),
        ] + parent::getExtensions();
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
        ]);
    }

    public static function getViewHelperResolverFactoryInterface(ContainerInterface $container): Core\ViewHelper\ViewHelperResolverFactoryInterface
    {
        return $container->get(Core\ViewHelper\ViewHelperResolverFactory::class);
    }

    public static function provideFallbackViewFactory(
        ContainerInterface $container,
        ?ViewFactoryInterface $viewFactory = null
    ): ViewFactoryInterface {
        // Provide the default FluidViewFactory for the install tool when $viewFactory is null (that means when we run without symfony DI)
        return $viewFactory ?? new FluidViewFactory(
            $container->get(Core\Rendering\RenderingContextFactory::class),
        );
    }
}
