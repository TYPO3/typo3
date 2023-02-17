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
use TYPO3\CMS\Core\Routing\RouteCollection;

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
     * This contains all available "aliases" as key, and the real module identifier as value
     * @var array<string, string>
     */
    private array $moduleAliases = [];

    /**
     * @param ModuleInterface[] $modules
     */
    public function __construct(array $modules)
    {
        array_walk($modules, [$this, 'addModule']);
        $this->modules = $this->applyHierarchy($this->modules);
        $this->populateAliasMapping();
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
        $this->modules[$module->getIdentifier()] = $module;
    }

    public function hasModule(string $identifier): bool
    {
        return isset($this->modules[$identifier]) || isset($this->moduleAliases[$identifier]);
    }

    public function getModule(string $identifier): ModuleInterface
    {
        if (!$this->hasModule($identifier)) {
            throw new \InvalidArgumentException(
                'Module with identifier ' . $identifier . ' does not exist.',
                1642375889
            );
        }

        // Resolve the alias to the real module
        if (isset($this->moduleAliases[$identifier])) {
            $identifier = $this->moduleAliases[$identifier];
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
            if (!$module->hasParentModule() && !$module->isStandalone()) {
                // Skip first level modules, which are not standalone
                continue;
            }
            $routeCollection = new RouteCollection();
            foreach ($module->getDefaultRouteOptions() as $routeIdentifier => $routeOptions) {
                $path = (string)(($routeOptions['path'] ?? false) ?: ('/' . $routeIdentifier));
                $methods = (array)($routeOptions['methods'] ?? []);
                unset($routeOptions['path'], $routeOptions['methods']);
                if ($routeIdentifier === '_default') {
                    // Add the first
                    $route = new Route($module->getPath(), $routeOptions);
                    if ($methods !== []) {
                        $route->setMethods($methods);
                    }
                    $router->addRoute($module->getIdentifier(), $route, $module->getAliases());
                } else {
                    $route = new Route($path, $routeOptions);
                    if ($methods !== []) {
                        $route->setMethods($methods);
                    }
                    $routeCollection->add($routeIdentifier, $route);
                }
            }
            $routeCollection->addNamePrefix($module->getIdentifier() . '.');
            $routeCollection->addPrefix($module->getPath());
            $router->addRouteCollection($routeCollection);
        }
    }

    /**
     * Applies sorting (based on the "position" configuration) to all
     * registered modules and resolves the hierarchy (creating relations
     * by attaching modules to the $parentModule and $subModules properties).
     *
     * @param ModuleInterface[] $modules
     * @return ModuleInterface[]
     */
    protected function applyHierarchy(array $modules): array
    {
        // Fetch top-level (parent) modules and fill them with sorted sub modules
        $topLevelModules = [];
        foreach ($modules as $identifier => $module) {
            if ($module->getParentIdentifier() === '') {
                $topLevelModules[$identifier] = $module;
            }
            $subModules = array_filter(
                $modules,
                static fn ($mod) => $mod->getParentIdentifier() === $identifier
            );
            if ($subModules === []) {
                continue;
            }
            // Sort sub modules and connect them with their parent module
            $subModules = $this->applySorting($subModules);
            foreach ($subModules as $subModule) {
                $module->addSubModule($subModule);
                $subModule->setParentModule($module);
            }
        }
        // Sort top level modules and return all modules (flat) with the correct sorting
        return $this->flattenModules($this->applySorting($topLevelModules));
    }

    /**
     * Ensures that modules within one level of hierarchy are ordered properly the way
     * they were given respecting their "top", "bottom", "before" or "after" definition.
     *
     * @param ModuleInterface[] $modules
     * @return ModuleInterface[]
     */
    protected function applySorting(array $modules): array
    {
        $modulePositionInformation = [];
        // First create a list of all needed data, that is the identifier, and its position
        foreach ($modules as $identifier => $module) {
            // @todo Should we enforce ['after' => '*'] in case ->getPosition() is empty?
            $modulePositionInformation[$identifier] = $module->getPosition();
            $modulePositionInformation[$identifier]['modulesToBeAddedDirectlyBefore'] = [];
            $modulePositionInformation[$identifier]['modulesToBeAddedDirectlyAfter'] = [];
        }

        // Identifiers of modules to be added at the top
        $highPriorityModules = [];
        // Identifiers of modules to be added at the bottom
        $lowPriorityModules = [];

        // Sort out the "top" and "bottom", and also build a graph of directly dependant (before/after) modules
        foreach ($modulePositionInformation as $identifier => $positionInformation) {
            if ($positionInformation['before'] ?? false) {
                if ($positionInformation['before'] === '*') {
                    // Module should be added on top
                    $highPriorityModules[] = $identifier;
                } elseif (isset($modules[$positionInformation['before']])) {
                    // Build the dependencies in case a valid module identifier is configured
                    $modulePositionInformation[$positionInformation['before']]['modulesToBeAddedDirectlyBefore'][] = $identifier;
                    $modulePositionInformation[$identifier]['modulesToBeAddedDirectlyAfter'][] = $positionInformation['before'];
                }
            } elseif ($positionInformation['after'] ?? false) {
                if ($positionInformation['after'] === '*') {
                    // Module should be added at the bottom
                    $lowPriorityModules[] = $identifier;
                } elseif (isset($modules[$positionInformation['after']])) {
                    // Build the dependencies in case a valid module identifier is configured
                    $modulePositionInformation[$identifier]['modulesToBeAddedDirectlyBefore'][] = $positionInformation['after'];
                    $modulePositionInformation[$positionInformation['after']]['modulesToBeAddedDirectlyAfter'][] = $identifier;
                }
            }
        }

        // First add the top items and their dependant modules
        $orderedModuleIdentifiers = $this->populateOrderingsForDependencies($highPriorityModules, $modulePositionInformation);
        // Now add the bottom items and their dependant modules
        // They will be cut out later, however, this is done now to also add their dependencies NOW (and not when looping over all items again)
        $orderedModuleIdentifiers = $this->populateOrderingsForDependencies($lowPriorityModules, $modulePositionInformation, $orderedModuleIdentifiers);
        $lastLowPriorityModule = end($orderedModuleIdentifiers);
        // Loop through all items and see which have not been added yet. Keep the original sorting.
        $orderedModuleIdentifiers = $this->populateOrderingsForDependencies(array_keys($modulePositionInformation), $modulePositionInformation, $orderedModuleIdentifiers);
        // Find the lowest priority module and move everything after that module to the very end
        if ($lowPriorityModules !== [] && $lastLowPriorityModule) {
            $firstLowPriorityModule = reset($lowPriorityModules);
            if ($firstLowPriorityModule !== $lastLowPriorityModule) {
                $firstPosition = array_search($firstLowPriorityModule, $orderedModuleIdentifiers, true);
                $lastPosition = array_search($lastLowPriorityModule, $orderedModuleIdentifiers, true);
                if ($firstPosition !== false && $lastPosition !== false) {
                    $extractedItems = array_slice($orderedModuleIdentifiers, $firstPosition, $lastPosition);
                    $orderedModuleIdentifiers = array_merge($orderedModuleIdentifiers, $extractedItems);
                }
            }
        }
        // Use the ordered list and replace all valid identifiers with the corresponding
        return array_replace(array_intersect_key(array_flip($orderedModuleIdentifiers), $modules), $modules);
    }

    /**
     * Given module identifiers are added based on their module position information to the
     * $alreadyOrderedModuleIdentifiers array (if not already present). In case the modules
     * to be added have dependencies to other modules and those modules exist, corresponding
     * modules are added to $alreadyOrderedModuleIdentifiers on the correct position as well.
     */
    protected function populateOrderingsForDependencies(
        array $moduleIdentifiersToBeAdded,
        array $modulePositionInformation,
        array $alreadyOrderedModuleIdentifiers = []
    ): array {
        foreach ($moduleIdentifiersToBeAdded as $identifier) {
            // already placed somewhere
            if (in_array($identifier, $alreadyOrderedModuleIdentifiers, true)) {
                continue;
            }
            // Check if the current module has dependencies, which should be added BEFORE
            foreach ($modulePositionInformation[$identifier]['modulesToBeAddedDirectlyBefore'] ?? [] as $dependantIdentifier) {
                // already placed somewhere
                if (in_array($dependantIdentifier, $alreadyOrderedModuleIdentifiers, true)) {
                    continue;
                }
                // Check if the dependent module has dependencies, which should be added BEFORE
                foreach ($modulePositionInformation[$dependantIdentifier]['modulesToBeAddedDirectlyBefore'] ?? [] as $dependantDependantIdentifier) {
                    // already placed somewhere
                    if (in_array($dependantDependantIdentifier, $alreadyOrderedModuleIdentifiers, true)) {
                        continue;
                    }
                    // Add the sub dependency right away
                    $alreadyOrderedModuleIdentifiers[] = $dependantDependantIdentifier;
                }
                // Add the dependant module now
                $alreadyOrderedModuleIdentifiers[] = $dependantIdentifier;
            }
            // Add the actual module now
            $alreadyOrderedModuleIdentifiers[] = $identifier;
            // Check if the current module has dependencies, which should be added AFTER
            foreach ($modulePositionInformation[$identifier]['modulesToBeAddedDirectlyAfter'] ?? [] as $dependantIdentifier) {
                // already placed somewhere
                if (in_array($dependantIdentifier, $alreadyOrderedModuleIdentifiers, true)) {
                    continue;
                }
                // Add the dependant module right away
                $alreadyOrderedModuleIdentifiers[] = $dependantIdentifier;
                // Check if the dependent module has dependencies, which should be added AFTER
                foreach ($modulePositionInformation[$dependantIdentifier]['modulesToBeAddedDirectlyAfter'] ?? [] as $dependantDependantIdentifier) {
                    // already placed somewhere
                    if (in_array($dependantDependantIdentifier, $alreadyOrderedModuleIdentifiers, true)) {
                        continue;
                    }
                    // Add the sub dependency right away
                    $alreadyOrderedModuleIdentifiers[] = $dependantDependantIdentifier;
                }
            }
        }
        return $alreadyOrderedModuleIdentifiers;
    }

    /**
     * Create a flat modules array (looping through each level by calling "getSubmodules()" on the parent)
     */
    protected function flattenModules(array $modules, $flatModules = []): array
    {
        foreach ($modules as $module) {
            $flatModules[$module->getIdentifier()] = $module;
            if ($module->hasSubmodules()) {
                $flatModules = $this->flattenModules($module->getSubmodules(), $flatModules);
            }
        }
        return $flatModules;
    }

    protected function populateAliasMapping(): void
    {
        foreach ($this->modules as $moduleIdentifier => $module) {
            foreach ($module->getAliases() as $aliasIdentifier) {
                // Note: The last module defining the same alias wins in general
                $this->moduleAliases[$aliasIdentifier] = $moduleIdentifier;
            }
        }
    }

    public function getModuleAliases(): array
    {
        return $this->moduleAliases;
    }
}
