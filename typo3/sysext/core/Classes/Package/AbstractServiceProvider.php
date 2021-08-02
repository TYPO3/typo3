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

namespace TYPO3\CMS\Core\Package;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    /**
     * Return the path to the package location, including trailing slash
     * should return the value of: __DIR__ . '/../'
     * for ServiceProviders located in the Classes/ directory
     *
     * @return string
     */
    abstract protected static function getPackagePath(): string;

    /**
     * @return array
     */
    abstract public function getFactories(): array;

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return [
            'middlewares' => [ static::class, 'configureMiddlewares' ],
            'backend.routes' => [ static::class, 'configureBackendRoutes' ],
            'icons' => [ static::class, 'configureIcons' ],
        ];
    }

    /**
     * @param ContainerInterface $container
     * @param ArrayObject $middlewares
     * @param string $path supplied when invoked internally through PseudoServiceProvider
     * @return ArrayObject
     */
    public static function configureMiddlewares(ContainerInterface $container, ArrayObject $middlewares, string $path = null): ArrayObject
    {
        $packageConfiguration = ($path ?? static::getPackagePath()) . 'Configuration/RequestMiddlewares.php';
        if (file_exists($packageConfiguration)) {
            $middlewaresInPackage = require $packageConfiguration;
            if (is_array($middlewaresInPackage)) {
                $middlewares->exchangeArray(array_replace_recursive($middlewares->getArrayCopy(), $middlewaresInPackage));
            }
        }

        return $middlewares;
    }

    /**
     * @param ContainerInterface $container
     * @param ArrayObject $routes
     * @param string|null $path supplied when invoked internally through PseudoServiceProvider
     * @return ArrayObject
     */
    public static function configureBackendRoutes(ContainerInterface $container, ArrayObject $routes, string $path = null): ArrayObject
    {
        $path = $path ?? static::getPackagePath();
        $routesFileNameForPackage = $path . 'Configuration/Backend/Routes.php';
        if (file_exists($routesFileNameForPackage)) {
            $definedRoutesInPackage = require $routesFileNameForPackage;
            if (is_array($definedRoutesInPackage)) {
                $routes->exchangeArray(array_merge($routes->getArrayCopy(), $definedRoutesInPackage));
            }
        }
        $routesFileNameForPackage = $path . 'Configuration/Backend/AjaxRoutes.php';
        if (file_exists($routesFileNameForPackage)) {
            $definedRoutesInPackage = require $routesFileNameForPackage;
            if (is_array($definedRoutesInPackage)) {
                foreach ($definedRoutesInPackage as $routeIdentifier => $routeOptions) {
                    // prefix the route with "ajax_" as "namespace"
                    $routeOptions['path'] = '/ajax' . $routeOptions['path'];
                    $routes['ajax_' . $routeIdentifier] = $routeOptions;
                    $routes['ajax_' . $routeIdentifier]['ajax'] = true;
                }
            }
        }

        return $routes;
    }

    public static function configureIcons(ContainerInterface $container, ArrayObject $icons, string $path = null): ArrayObject
    {
        $path = $path ?? static::getPackagePath();
        $iconsFileNameForPackage = $path . 'Configuration/Icons.php';
        if (file_exists($iconsFileNameForPackage)) {
            $definedIconsInPackage = require $iconsFileNameForPackage;
            if (is_array($definedIconsInPackage)) {
                $icons->exchangeArray(array_merge($icons->getArrayCopy(), $definedIconsInPackage));
            }
        }
        return $icons;
    }

    /**
     * Create an instance of a class. Supports auto injection of the logger.
     *
     * @param ContainerInterface $container
     * @param string $className name of the class to instantiate, must not be empty and not start with a backslash
     * @param array $constructorArguments Arguments for the constructor
     * @return mixed
     */
    protected static function new(ContainerInterface $container, string $className, array $constructorArguments = [])
    {
        // Support $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] (xclasses) and class alias maps
        $instance = GeneralUtility::makeInstanceForDi($className, ...$constructorArguments);

        if ($instance instanceof LoggerAwareInterface) {
            $instance->setLogger($container->get(LogManager::class)->getLogger($className));
        }
        return $instance;
    }
}
