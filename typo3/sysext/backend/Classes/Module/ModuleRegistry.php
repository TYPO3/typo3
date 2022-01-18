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

use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;

/**
 * @internal Always use the ModuleProvider API to access modules
 */
final class ModuleRegistry
{
    /**
     * @var ModuleInterface[]
     */
    private array $modules = [];

    /**
     * @param ModuleInterface[] $modules
     */
    public function __construct(array $modules)
    {
        array_walk($modules, [$this, 'addModule']);
    }

    private function addModule(ModuleInterface $module): void
    {
        $identifier = $module->getIdentifier();

        if (isset($this->modules[$identifier])) {
            throw new \LogicException(
                'A module with the identifier ' . $identifier . ' is already registered.',
                1642174843
            );
        }

        if (!$module->getParentIdentifier()) {
            // We are about to add a main module
            // Attach possible submodules (which were registered before the main modules)
            $possibleSubModules = array_filter(
                $this->modules,
                static fn ($mod) => $mod->getParentIdentifier() === $identifier
            );
            foreach ($possibleSubModules as $subModule) {
                if ($subModule->getParentIdentifier()) {
                    throw new \LogicException(
                        'The submodule ' . $subModule->getIdentifier() . ' does already contain a parent module with the name ' . $identifier,
                        1642174845
                    );
                }
                $module->addSubModule($subModule);
                $subModule->setParentModule($module);
            }
        } elseif ($this->hasModule($module->getParentIdentifier())) {
            // We are about to add a sub module
            $parentModule = $this->getModule($module->getParentIdentifier());
            if ($parentModule->hasSubModule($identifier)) {
                throw new \LogicException(
                    'A submodule ' . $identifier . ' for module ' . $parentModule->getIdentifier() . ' already exists.',
                    1642174846
                );
            }
            $module->setParentModule($parentModule);
            $parentModule->addSubModule($module);
        }

        if (in_array('top', $module->getPosition(), true)) {
            $this->modules = array_merge([$module->getIdentifier() => $module], $this->modules);
            return;
        }
        if (($module->getPosition()['before'] ?? false)
            && ($modulePosition = array_search(
                $module->getPosition()['before'],
                array_keys($this->modules),
                true
            )) !== false
        ) {
            $this->modules = array_slice($this->modules, 0, $modulePosition)
                + [$module->getIdentifier() => $module]
                + array_slice($this->modules, $modulePosition);
            return;
        }
        if (($module->getPosition()['after'] ?? false)
            && ($modulePosition = array_search(
                $module->getPosition()['after'],
                array_keys($this->modules),
                true
            )) !== false
        ) {
            $this->modules = array_slice($this->modules, 0, $modulePosition + 1)
                + [$module->getIdentifier() => $module]
                + array_slice($this->modules, $modulePosition + 1);
            return;
        }
        $this->modules = array_merge($this->modules, [$module->getIdentifier() => $module]);
    }

    public function hasModule(string $identifier): bool
    {
        return isset($this->modules[$identifier]);
    }

    public function getModule(string $identifier): ModuleInterface
    {
        if (!$this->hasModule($identifier)) {
            throw new \InvalidArgumentException(
                'Module with identifier ' . $identifier . ' does not exist.',
                1642375889
            );
        }

        return $this->modules[$identifier];
    }

    /**
     * @return ModuleInterface[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Needs to be called when the router is set up, AFTER all modules are loaded.
     */
    public function registerRoutesForModules(Router $router): void
    {
        foreach ($this->modules as $module) {
            if ($module->hasParentModule() || $module->isStandalone()) {
                $router->addRoute(
                    $module->getIdentifier(),
                    new Route($module->getPath(), $module->getDefaultRouteOptions())
                );
            }
        }
    }
}
