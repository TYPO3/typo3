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

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\DependencyInjection\ServiceProviderInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationOrigin;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationOriginType;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Type\Map;
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
     */
    abstract protected static function getPackagePath(): string;

    /**
     * Return the composer package name. This is the 'name' attribute in composer.json.
     * Note composer.json existence for 'extensions' is still not mandatory
     * in non-composer mode, the method returns empty string in this case.
     */
    abstract protected static function getPackageName(): string;

    abstract public function getFactories(): array;

    public function getExtensions(): array
    {
        return [
            'middlewares' => [ static::class, 'configureMiddlewares' ],
            'backend.routes' => [ static::class, 'configureBackendRoutes' ],
            // @deprecated since v12, will be removed with v13 together with class PageTsConfigLoader.
            'globalPageTsConfig' => [ static::class, 'configureGlobalPageTsConfig' ],
            'backend.modules' => [ static::class, 'configureBackendModules' ],
            'content.security.policies' => [static::class, 'configureContentSecurityPolicies'],
            'icons' => [ static::class, 'configureIcons' ],
        ];
    }

    /**
     * @param string|null $path supplied when invoked internally through PseudoServiceProvider
     */
    public static function configureMiddlewares(ContainerInterface $container, \ArrayObject $middlewares, string $path = null): \ArrayObject
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
     * @param string|null $path supplied when invoked internally through PseudoServiceProvider
     * @param string|null $packageName supplied when invoked internally through PseudoServiceProvider
     */
    public static function configureBackendRoutes(ContainerInterface $container, \ArrayObject $routes, string $path = null, string $packageName = null): \ArrayObject
    {
        $path = $path ?? static::getPackagePath();
        $packageName = $packageName ?? static::getPackageName();
        $routesFileNameForPackage = $path . 'Configuration/Backend/Routes.php';
        if (file_exists($routesFileNameForPackage)) {
            $definedRoutesInPackage = require $routesFileNameForPackage;
            if (is_array($definedRoutesInPackage)) {
                array_walk($definedRoutesInPackage, static function (&$options) use ($packageName, $path) {
                    // Add packageName and absolutePackagePath to all routes
                    $options['packageName'] = $packageName;
                    $options['absolutePackagePath'] = $path;
                });
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
                    $routeOptions['packageName'] = $packageName;
                    $routeOptions['absolutePackagePath'] = $path;
                    $routes['ajax_' . $routeIdentifier] = $routeOptions;
                    $routes['ajax_' . $routeIdentifier]['ajax'] = true;
                }
            }
        }

        return $routes;
    }

    /**
     * @deprecated since v12, will be removed with v13 together with class PageTsConfigLoader.
     */
    public static function configureGlobalPageTsConfig(ContainerInterface $container, \ArrayObject $tsConfigFiles, string $path = null): \ArrayObject
    {
        $path = $path ?? static::getPackagePath();
        $tsConfigFile = null;
        if (file_exists($path . 'Configuration/page.tsconfig')) {
            $tsConfigFile = $path . 'Configuration/page.tsconfig';
        } elseif (file_exists($path . 'Configuration/Page.tsconfig')) {
            $tsConfigFile = $path . 'Configuration/Page.tsconfig';
        }
        if ($tsConfigFile) {
            $tsConfigContents = @file_get_contents($tsConfigFile);
            if (!empty($tsConfigContents)) {
                $tsConfigFiles->exchangeArray(array_merge($tsConfigFiles->getArrayCopy(), [$tsConfigContents]));
            }
        }
        return $tsConfigFiles;
    }

    /**
     * @param string|null $path supplied when invoked internally through PseudoServiceProvider
     * @param string|null $packageName supplied when invoked internally through PseudoServiceProvider
     */
    public static function configureBackendModules(ContainerInterface $container, \ArrayObject $modules, string $path = null, string $packageName = null): \ArrayObject
    {
        $path = $path ?? static::getPackagePath();
        $packageName = $packageName ?? static::getPackageName();
        $modulesFileNameForPackage = $path . 'Configuration/Backend/Modules.php';
        if (file_exists($modulesFileNameForPackage)) {
            $definedModulesInPackage = require $modulesFileNameForPackage;
            if (is_array($definedModulesInPackage)) {
                array_walk($definedModulesInPackage, static function (&$module) use ($packageName, $path) {
                    // Add packageName and absolutePackagePath to all modules
                    $module['packageName'] = $packageName;
                    $module['absolutePackagePath'] = $path;
                });
                $modules->exchangeArray(array_merge($modules->getArrayCopy(), $definedModulesInPackage));
            }
        }
        return $modules;
    }

    /**
     * @param Map<Scope, Map<MutationOrigin, MutationCollection>> $mutations
     * @return Map<Scope, Map<MutationOrigin, MutationCollection>>
     */
    public static function configureContentSecurityPolicies(ContainerInterface $container, Map $mutations, string $path = null, string $packageName = null): Map
    {
        $path = $path ?? static::getPackagePath();
        $packageName = $packageName ?? static::getPackageName();
        $fileName = $path . 'Configuration/ContentSecurityPolicies.php';
        if (file_exists($fileName)) {
            /** @var Map<Scope, MutationCollection> $mutationsInPackage */
            $mutationsInPackage = require $fileName;
            foreach ($mutationsInPackage as $scope => $mutation) {
                if (!isset($mutations[$scope])) {
                    $mutations[$scope] = new Map();
                }
                $origin = new MutationOrigin(MutationOriginType::package, $packageName);
                $mutations[$scope][$origin] = $mutation;
            }
        }
        return $mutations;
    }

    public static function configureIcons(ContainerInterface $container, \ArrayObject $icons, string $path = null): \ArrayObject
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
