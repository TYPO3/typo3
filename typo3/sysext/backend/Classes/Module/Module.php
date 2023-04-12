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

namespace TYPO3\CMS\Backend\Module;

use TYPO3\CMS\Backend\Exception\NonRoutableModuleException;

/**
 * A standard backend nodule
 */
class Module extends BaseModule implements ModuleInterface
{
    protected array $routes;

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getDefaultRouteOptions(): array
    {
        $defaultRouteOptions = [];

        if ($this->routes !== []) {
            foreach ($this->routes as $routeIdentifier => $routeOptions) {
                $defaultRouteOptions[$routeIdentifier] = array_replace_recursive(
                    $this->routeOptions,
                    $routeOptions,
                    [
                        'module' => $this,
                        'packageName' => $this->packageName,
                        'absolutePackagePath' => $this->absolutePackagePath,
                        'access' => $this->access,
                    ]
                );
            }
        } elseif ($this->hasSubModules()) {
            // In case no routes are defined but the module has submodules,
            // fall back and use the first submodules' route options instead.
            $submodules = $this->getSubModules();
            $firstSubModule = reset($submodules);
            $defaultRouteOptions = $firstSubModule->getDefaultRouteOptions();
        }

        if (!isset($defaultRouteOptions['_default'])) {
            throw new NonRoutableModuleException(
                'No default route could be resolved for module ' . $this->identifier,
                1674063354
            );
        }

        return $defaultRouteOptions;
    }

    public static function createFromConfiguration(string $identifier, array $configuration): static
    {
        $obj = parent::createFromConfiguration($identifier, $configuration);
        $obj->routes = $configuration['routes'] ?? [];
        return $obj;
    }
}
