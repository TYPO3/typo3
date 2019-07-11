<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend;

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
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
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
            Http\Application::class => [ static::class, 'getApplication' ],
            Http\RequestHandler::class => [ static::class, 'getRequestHandler' ],
            Http\RouteDispatcher::class => [ static::class, 'getRouteDispatcher' ],
            'backend.middlewares' => [ static::class, 'getBackendMiddlewares' ],
        ];
    }

    public static function getApplication(ContainerInterface $container): Http\Application
    {
        $requestHandler = new MiddlewareDispatcher(
            $container->get(Http\RequestHandler::class),
            $container->get('backend.middlewares'),
            $container
        );
        return new Http\Application(
            $requestHandler,
            $container->get(ConfigurationManager::class),
            $container->get(Context::class)
        );
    }

    public static function getRequestHandler(ContainerInterface $container): Http\RequestHandler
    {
        return new Http\RequestHandler($container->get(Http\RouteDispatcher::class));
    }

    public static function getRouteDispatcher(ContainerInterface $container): Http\RouteDispatcher
    {
        return self::new($container, Http\RouteDispatcher::class, [$container]);
    }

    /**
     * @param ContainerInterface $container
     * @return array
     * @throws InvalidDataException
     * @throws CoreException
     */
    public static function getBackendMiddlewares(ContainerInterface $container): array
    {
        return $container->get(MiddlewareStackResolver::class)->resolve('backend');
    }
}
