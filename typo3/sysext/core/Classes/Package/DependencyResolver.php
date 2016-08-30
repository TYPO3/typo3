<?php
namespace TYPO3\CMS\Core\Package;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * This class takes care about dependencies between packages.
 * It provides functionality to resolve dependencies and to determine
 * the crucial loading order of the packages.
 */
class DependencyResolver
{
    /**
     * Folder with framework extensions
     */
    const SYSEXT_FOLDER = 'typo3/sysext';

    /**
     * @var DependencyOrderingService
     */
    protected $dependencyOrderingService;

    /**
     * @param DependencyOrderingService $dependencyOrderingService
     */
    public function injectDependencyOrderingService(DependencyOrderingService $dependencyOrderingService)
    {
        $this->dependencyOrderingService = $dependencyOrderingService;
    }

    /**
     * @param array $packageStatesConfiguration
     * @return array Returns the packageStatesConfiguration sorted by dependencies
     * @throws \UnexpectedValueException
     */
    public function sortPackageStatesConfigurationByDependency(array $packageStatesConfiguration)
    {
        // We just want to consider active packages
        $activePackageStatesConfiguration = array_filter($packageStatesConfiguration, function ($packageState) {
            return isset($packageState['state']) && $packageState['state'] === 'active';
        });
        $inactivePackageStatesConfiguration = array_diff_key($packageStatesConfiguration, $activePackageStatesConfiguration);

        $sortedPackageKeys = $this->dependencyOrderingService->calculateOrder($this->buildDependencyGraph($activePackageStatesConfiguration));

        // Reorder the package states according to the loading order
        $newPackageStatesConfiguration = [];
        foreach ($sortedPackageKeys as $packageKey) {
            $newPackageStatesConfiguration[$packageKey] = $packageStatesConfiguration[$packageKey];
        }

        // Append the inactive configurations again
        $newPackageStatesConfiguration = array_merge($newPackageStatesConfiguration, $inactivePackageStatesConfiguration);

        return $newPackageStatesConfiguration;
    }

    /**
     * Convert the package configuration into a dependency definition
     *
     * This converts "dependencies" and "suggestions" to "after" syntax for the usage in DependencyOrderingService
     *
     * @param array $packageStatesConfiguration
     * @param array $packageKeys
     * @return array
     * @throws \UnexpectedValueException
     */
    protected function convertConfigurationForGraph(array $packageStatesConfiguration, array $packageKeys)
    {
        $dependencies = [];
        foreach ($packageKeys as $packageKey) {
            if (!isset($packageStatesConfiguration[$packageKey]['dependencies']) && !isset($packageStatesConfiguration[$packageKey]['suggestions'])) {
                continue;
            }
            $dependencies[$packageKey] = [
                'after' => []
            ];
            if (isset($packageStatesConfiguration[$packageKey]['dependencies'])) {
                foreach ($packageStatesConfiguration[$packageKey]['dependencies'] as $dependentPackageKey) {
                    if (!in_array($dependentPackageKey, $packageKeys, true)) {
                        throw new \UnexpectedValueException(
                            'The package "' . $packageKey . '" depends on "'
                            . $dependentPackageKey . '" which is not present in the system.',
                            1382276561);
                    }
                    $dependencies[$packageKey]['after'][] = $dependentPackageKey;
                }
            }
            if (isset($packageStatesConfiguration[$packageKey]['suggestions'])) {
                foreach ($packageStatesConfiguration[$packageKey]['suggestions'] as $suggestedPackageKey) {
                    // skip suggestions on not existing packages
                    if (in_array($suggestedPackageKey, $packageKeys, true)) {
                        // Suggestions actually have never been meant to influence loading order.
                        // We misuse this currently, as there is no other way to influence the loading order
                        // for not-required packages (soft-dependency).
                        // When considering suggestions for the loading order, we might create a cyclic dependency
                        // if the suggested package already has a real dependency on this package, so the suggestion
                        // has do be dropped in this case and must *not* be taken into account for loading order evaluation.
                        $dependencies[$packageKey]['after-resilient'][] = $suggestedPackageKey;
                    }
                }
            }
        }
        return $dependencies;
    }

    /**
     * Adds all root packages of current dependency graph as dependency to all extensions
     *
     * This ensures that the framework extensions (aka sysext) are
     * always loaded first, before any other external extension.
     *
     * @param array $packageStateConfiguration
     * @param array $rootPackageKeys
     * @return array
     */
    protected function addDependencyToFrameworkToAllExtensions(array $packageStateConfiguration, array $rootPackageKeys)
    {
        $frameworkPackageKeys = $this->findFrameworkPackages($packageStateConfiguration);
        $extensionPackageKeys = array_diff(array_keys($packageStateConfiguration), $frameworkPackageKeys);
        foreach ($extensionPackageKeys as $packageKey) {
            // Remove framework packages from list
            $packageKeysWithoutFramework = array_diff(
                $packageStateConfiguration[$packageKey]['dependencies'],
                $frameworkPackageKeys
            );
            // The order of the array_merge is crucial here,
            // we want the framework first
            $packageStateConfiguration[$packageKey]['dependencies'] = array_merge(
                $rootPackageKeys, $packageKeysWithoutFramework
            );
        }
        return $packageStateConfiguration;
    }

    /**
     * Builds the dependency graph for all packages
     *
     * This method also introduces dependencies among the dependencies
     * to ensure the loading order is exactly as specified in the list.
     *
     * @param array $packageStateConfiguration
     * @return array
     */
    protected function buildDependencyGraph(array $packageStateConfiguration)
    {
        $frameworkPackageKeys = $this->findFrameworkPackages($packageStateConfiguration);
        $frameworkPackagesDependencyGraph = $this->dependencyOrderingService->buildDependencyGraph($this->convertConfigurationForGraph($packageStateConfiguration, $frameworkPackageKeys));
        $packageStateConfiguration = $this->addDependencyToFrameworkToAllExtensions($packageStateConfiguration, $this->dependencyOrderingService->findRootIds($frameworkPackagesDependencyGraph));

        $packageKeys = array_keys($packageStateConfiguration);
        return $this->dependencyOrderingService->buildDependencyGraph($this->convertConfigurationForGraph($packageStateConfiguration, $packageKeys));
    }

    /**
     * @param array $packageStateConfiguration
     * @return array
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function findFrameworkPackages(array $packageStateConfiguration)
    {
        $frameworkPackageKeys = [];
        /** @var PackageManager $packageManager */
        $packageManager = Bootstrap::getInstance()->getEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        foreach ($packageStateConfiguration as $packageKey => $packageConfiguration) {
            /** @var Package $package */
            $package = $packageManager->getPackage($packageKey);
            if ($package->getValueFromComposerManifest('type') === 'typo3-cms-framework') {
                $frameworkPackageKeys[] = $packageKey;
            }
        }

        return $frameworkPackageKeys;
    }
}
