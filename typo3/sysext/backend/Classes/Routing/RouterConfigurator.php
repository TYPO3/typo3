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

namespace TYPO3\CMS\Backend\Routing;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;

/**
 * @internal
 */
final readonly class RouterConfigurator
{
    public function __construct(
        private ModuleRegistry $moduleRegistry,
        #[Autowire(service: 'backend.routes', lazy: true)]
        private \ArrayObject $backendRoutes,
        #[Autowire(service: 'cache.core')]
        private PhpFrontend $coreCache,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("BackendRoutes").toString()')]
        private string $cacheIdentifier,
    ) {}

    public function __invoke(Router $router): void
    {
        $routesFromPackages = $this->coreCache->require($this->cacheIdentifier);
        if ($routesFromPackages === false) {
            $routesFromPackages = $this->backendRoutes->getArrayCopy();
            $this->coreCache->set($this->cacheIdentifier, 'return ' . var_export($routesFromPackages, true) . ';');
        }

        foreach ($routesFromPackages as $name => $options) {
            $path = $options['path'];
            $methods = $options['methods'] ?? [];
            $aliases = $options['aliases'] ?? [];
            unset($options['path'], $options['methods'], $options['aliases']);
            $route = new Route($path, $options);
            if ($methods !== []) {
                $route->setMethods($methods);
            }
            $router->addRoute($name, $route, $aliases);
        }

        // Add routes from all modules
        $this->moduleRegistry->registerRoutesForModules($router);
    }
}
