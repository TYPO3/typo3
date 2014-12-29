<?php
namespace TYPO3\CMS\Core\Package;

/**
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

/**
 * This class takes care about dependencies between packages.
 * It provides functionality to resolve dependencies and to determine
 * the crucial loading order of the packages.
 *
 * @author Markus Klein <klein.t3@mfc-linz.at>
 */
class DependencyResolver {

	/**
	 * Folder with framework extensions
	 */
	const SYSEXT_FOLDER = 'typo3/sysext';

	/**
	 * @param array $packageStatesConfiguration
	 * @return array Returns the packageStatesConfiguration sorted by dependencies
	 * @throws \UnexpectedValueException
	 */
	public function sortPackageStatesConfigurationByDependency(array $packageStatesConfiguration) {
		// We just want to consider active packages
		$activePackageStatesConfiguration = $this->removeInactivePackagesFromPackageStateConfiguration($packageStatesConfiguration);
		$inactivePackageStatesConfiguration = array_diff_key($packageStatesConfiguration, $activePackageStatesConfiguration);

		/*
		 * Adjacency matrix for the dependency graph (DAG)
		 *
		 * Example structure is:
		 *    A => (A => FALSE, B => TRUE,  C => FALSE)
		 *    B => (A => FALSE, B => FALSE, C => FALSE)
		 *    C => (A => TRUE,  B => FALSE, C => FALSE)
		 *
		 *    A depends on B, C depends on A, B is independent
		 */
		$dependencyGraph = $this->buildDependencyGraph($activePackageStatesConfiguration);

		// Filter extensions with no incoming edge
		$rootPackageKeys = array();
		foreach ($dependencyGraph as $packageKey => $_) {
			if (!$this->getIncomingEdgeCount($dependencyGraph, $packageKey)) {
				$rootPackageKeys[] = $packageKey;
			}
		}

		// This will contain our final result
		$sortedPackageKeys = array();

		// Walk through the graph
		while (count($rootPackageKeys)) {
			$currentPackageKey = array_shift($rootPackageKeys);
			array_push($sortedPackageKeys, $currentPackageKey);

			foreach (array_filter($dependencyGraph[$currentPackageKey]) as $dependingPackageKey => $_) {
				// Remove the edge to this dependency
				$dependencyGraph[$currentPackageKey][$dependingPackageKey] = FALSE;
				if (!$this->getIncomingEdgeCount($dependencyGraph, $dependingPackageKey)) {
					// We found a new root, lets add it
					array_unshift($rootPackageKeys, $dependingPackageKey);
				}
			}
		}

		// Check for remaining edges in the graph
		$cycles = array();
		array_walk($dependencyGraph, function($dependencies, $packageKeyFrom) use(&$cycles) {
			array_walk($dependencies, function($dependency, $packageKeyTo) use(&$cycles, $packageKeyFrom) {
				if ($dependency) {
					$cycles[] = $packageKeyFrom . '->' . $packageKeyTo;
				}
			});
		});
		if (count($cycles)) {
			throw new \UnexpectedValueException('Your dependencies have cycles. That will not work out. Cycles found: ' . implode(', ', $cycles), 1381960493);
		}

		// We built now a list of dependencies
		// Reverse the list to get the correct loading order
		$sortedPackageKeys = array_reverse($sortedPackageKeys);

		// Reorder the package states according to the loading order
		$newPackageStatesConfiguration = array();
		foreach ($sortedPackageKeys as $packageKey) {
			$newPackageStatesConfiguration[$packageKey] = $packageStatesConfiguration[$packageKey];
		}

		// Append the inactive configurations again
		$newPackageStatesConfiguration = array_merge($newPackageStatesConfiguration, $inactivePackageStatesConfiguration);

		return $newPackageStatesConfiguration;
	}

	/**
	 * Returns only active package state configurations
	 *
	 * @param array $packageStatesConfiguration
	 * @return array
	 */
	protected function removeInactivePackagesFromPackageStateConfiguration(array $packageStatesConfiguration) {
		return array_filter($packageStatesConfiguration, function($packageState) {
			return isset($packageState['state']) && $packageState['state'] === 'active';
		});
	}

	/**
	 * Build the dependency graph for the given packages
	 *
	 * @param array $packageStatesConfiguration
	 * @param array $packageKeys
	 * @return array
	 * @throws \UnexpectedValueException
	 */
	protected function buildDependencyGraphForPackages(array $packageStatesConfiguration, array $packageKeys) {
		// Initialize the dependencies with FALSE
		sort($packageKeys);
		$dependencyGraph = array_fill_keys($packageKeys, array_fill_keys($packageKeys, FALSE));
		foreach ($packageKeys as $packageKey) {
			if (!isset($packageStatesConfiguration[$packageKey]['dependencies'])) {
				continue;
			}
			$dependentPackageKeys = $packageStatesConfiguration[$packageKey]['dependencies'];
			foreach ($dependentPackageKeys as $dependentPackageKey) {
				if (!in_array($dependentPackageKey, $packageKeys)) {
					throw new \UnexpectedValueException(
						'The package "' . $packageKey .'" depends on "'
						. $dependentPackageKey . '" which is not present in the system.',
						1382276561);
				}
				$dependencyGraph[$packageKey][$dependentPackageKey] = TRUE;
			}
		}
		foreach ($packageKeys as $packageKey) {
			if (!isset($packageStatesConfiguration[$packageKey]['suggestions'])) {
				continue;
			}
			$suggestedPackageKeys = $packageStatesConfiguration[$packageKey]['suggestions'];
			foreach ($suggestedPackageKeys as $suggestedPackageKey) {
				if (!in_array($suggestedPackageKey, $packageKeys)) {
					continue;
				}
				// Check if there's no dependency of the suggestion to the package
				// Dependencies take precedence over suggestions
				$dependencies = $this->findPathInGraph($dependencyGraph, $suggestedPackageKey, $packageKey);
				if (empty($dependencies)) {
					$dependencyGraph[$packageKey][$suggestedPackageKey] = TRUE;
				}
			}
		}
		return $dependencyGraph;
	}

	/**
	 * Find any path in the graph from given start node to destination node
	 *
	 * @param array $graph Directed graph
	 * @param string $from Start node
	 * @param string $to Destination node
	 * @return array Nodes of the found path; empty if no path is found
	 */
	protected function findPathInGraph(array $graph, $from, $to) {
		foreach (array_filter($graph[$from]) as $node => $_) {
			if ($node === $to) {
				return array($from, $to);
			} else {
				$subPath = $this->findPathInGraph($graph, $node, $to);
				if (!empty($subPath)) {
					array_unshift($subPath, $from);
					return $subPath;
				}
			}
		}
		return array();
	}

	/**
	 * Adds all root packages of current dependency graph as dependency
	 * to all extensions.
	 * This ensures that the framework extensions (aka sysext) are
	 * always loaded first, before any other external extension.
	 *
	 * @param array $packageStateConfiguration
	 * @param array $dependencyGraph
	 * @return array
	 */
	protected function addDependencyToFrameworkToAllExtensions(array $packageStateConfiguration, array $dependencyGraph) {
		$rootPackageKeys = array();
		foreach ($dependencyGraph as $packageKey => $_) {
			if (!$this->getIncomingEdgeCount($dependencyGraph, $packageKey)) {
				$rootPackageKeys[] = $packageKey;
			}
		}
		$extensionPackageKeys = $this->getPackageKeysInBasePath($packageStateConfiguration, '', array(self::SYSEXT_FOLDER));
		$frameworkPackageKeys = $this->getPackageKeysInBasePath($packageStateConfiguration, self::SYSEXT_FOLDER);
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
	protected function buildDependencyGraph(array $packageStateConfiguration) {
		$frameworkPackageKeys = $this->getPackageKeysInBasePath($packageStateConfiguration, self::SYSEXT_FOLDER);
		$dependencyGraph = $this->buildDependencyGraphForPackages($packageStateConfiguration, $frameworkPackageKeys);
		$packageStateConfiguration = $this->addDependencyToFrameworkToAllExtensions($packageStateConfiguration, $dependencyGraph);

		$packageKeys = array_keys($packageStateConfiguration);
		$dependencyGraph = $this->buildDependencyGraphForPackages($packageStateConfiguration, $packageKeys);
		return $dependencyGraph;
	}



	/**
	 * Get the number of incoming edges in the dependency graph
	 * for given package key.
	 *
	 * @param array $dependencyGraph
	 * @param string $packageKey
	 * @return integer
	 */
	protected function getIncomingEdgeCount(array $dependencyGraph, $packageKey) {
		$incomingEdgeCount = 0;
		foreach ($dependencyGraph as $dependencies) {
			if ($dependencies[$packageKey]) {
				$incomingEdgeCount++;
			}
		}
		return $incomingEdgeCount;
	}

	/**
	 * Get packages of specific type
	 *
	 * @param array $packageStateConfiguration
	 * @param string $basePath Base path of package. Empty string for all types
	 * @param array $excludedPaths Array of package base paths to exclude
	 * @return array List of packages
	 */
	protected function getPackageKeysInBasePath(array $packageStateConfiguration, $basePath, array $excludedPaths = array()) {
		$packageKeys = array();
		foreach ($packageStateConfiguration as $packageKey => $package) {
			if (($basePath === '' || strpos($package['packagePath'], $basePath) === 0)) {
				$isExcluded = FALSE;
				foreach ($excludedPaths as $excludedPath) {
					if (strpos($package['packagePath'], $excludedPath) === 0) {
						$isExcluded = TRUE;
						break;
					}
				}
				if (!$isExcluded) {
					$packageKeys[] = $packageKey;
				}
			}
		}
		return $packageKeys;
	}

}
