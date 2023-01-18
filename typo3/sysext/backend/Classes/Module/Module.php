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
        $defaultTarget = '';

        if (!isset($this->routes['_default']['target'])) {
            if ($this->hasSubModules()) {
                $submodules = $this->getSubModules();
                $firstSubModule = reset($submodules);
                if ($firstSubModule->getDefaultRouteOptions()['target'] ?? false) {
                    $defaultTarget = $firstSubModule->getDefaultRouteOptions()['target'];
                }
            }
        } else {
            $defaultTarget = $this->routes['_default']['target'];
        }

        if ($defaultTarget === '') {
            throw new \InvalidArgumentException(
                'No default target could be resolved for module ' . $this->identifier,
                1674063354
            );
        }

        return [
            'module' => $this,
            'packageName' => $this->packageName,
            'absolutePackagePath' => $this->absolutePackagePath,
            'access' => $this->access,
            'target' => $defaultTarget,
        ];
    }

    public static function createFromConfiguration(string $identifier, array $configuration): static
    {
        $obj = parent::createFromConfiguration($identifier, $configuration);
        $obj->routes = $configuration['routes'] ?? [];
        return $obj;
    }
}
