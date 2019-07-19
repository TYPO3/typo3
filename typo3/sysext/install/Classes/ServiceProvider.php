<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install;

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
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\PackageManager;

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
            Http\NotFoundRequestHandler::class => [ static::class, 'getNotFoundRequestHandler' ],
            Service\LateBootService::class => [ static::class, 'getLateBootService' ],
            Service\ClearCacheService::class => [ static::class, 'getClearCacheService' ],
            Service\LoadTcaService::class => [ static::class, 'getLoadTcaService' ],
            Middleware\Maintenance::class => [ static::class, 'getMaintenanceMiddleware' ],
            Controller\UpgradeController::class => [ static::class, 'getUpgradeController' ],
        ];
    }

    public static function getApplication(ContainerInterface $container): Http\Application
    {
        $requestHandler = $container->get(Http\NotFoundRequestHandler::class);
        $dispatcher = new MiddlewareDispatcher($requestHandler, [], $container);

        // Stack of middlewares, executed LIFO
        $dispatcher->lazy(Middleware\Installer::class);
        $dispatcher->add($container->get(Middleware\Maintenance::class));

        return new Http\Application($dispatcher, $container->get(Context::class));
    }

    public static function getNotFoundRequestHandler(ContainerInterface $container): Http\NotFoundRequestHandler
    {
        return new Http\NotFoundRequestHandler;
    }

    public static function getLateBootService(ContainerInterface $container): Service\LateBootService
    {
        return new Service\LateBootService(
            $container->get(ContainerBuilder::class),
            $container
        );
    }

    public static function getClearCacheService(ContainerInterface $container): Service\ClearCacheService
    {
        return new Service\ClearCacheService($container->get(Service\LateBootService::class));
    }

    public static function getLoadTcaService(ContainerInterface $container): Service\LoadTcaService
    {
        return new Service\LoadTcaService($container->get(Service\LateBootService::class));
    }

    public static function getMaintenanceMiddleware(ContainerInterface $container): Middleware\Maintenance
    {
        return new Middleware\Maintenance(
            $container->get(PackageManager::class),
            $container->get(ConfigurationManager::class),
            $container->get(PasswordHashFactory::class),
            $container
        );
    }

    public static function getUpgradeController(ContainerInterface $container): Controller\UpgradeController
    {
        return new Controller\UpgradeController(
            $container->get(PackageManager::class),
            $container->get(Service\LateBootService::class)
        );
    }
}
