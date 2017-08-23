<?php
namespace TYPO3\CMS\Core\Service;

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

/**
 * This class provides functionality to build
 * an ordered list from a set of dependencies.
 *
 * We use an adjacency matrix for the dependency graph (DAG)
 *
 * Example structure of the DAG is:
 *    A => (A => FALSE, B => TRUE,  C => FALSE)
 *    B => (A => FALSE, B => FALSE, C => FALSE)
 *    C => (A => TRUE,  B => FALSE, C => FALSE)
 *
 *    A depends on B, C depends on A, B is independent
 */
class DependencyOrderingService
{
    /**
     * Order items by specified dependencies before/after
     *
     * The dependencies of an items are specified as:
     *   'someItemKey' => [
     *      'before' => ['someItemKeyA', 'someItemKeyB']
     *      'after' => ['someItemKeyC']
     *   ]
     *
     * If your items use different keys for specifying the relations, you can define the appropriate keys
     * by setting the $beforeKey and $afterKey parameters accordingly.
     *
     * @param array $items
     * @param string $beforeKey The key to use in a dependency which specifies the "before"-relation. eg. 'sortBefore', 'loadBefore'
     * @param string $afterKey The key to use in a dependency which specifies the "after"-relation. eg. 'sortAfter', 'loadAfter'
     * @return array
     * @throws \UnexpectedValueException
     */
    public function orderByDependencies(array $items, $beforeKey = 'before', $afterKey = 'after')
    {
        $graph = $this->buildDependencyGraph($items, $beforeKey, $afterKey);
        $sortedItems = [];
        foreach ($this->calculateOrder($graph) as $id) {
            if (isset($items[$id])) {
                $sortedItems[$id] = $items[$id];
            }
        }
        return $sortedItems;
    }

    /**
     * Builds the dependency graph for the given dependencies
     *
     * The dependencies have to specified in the following structure:
     * $dependencies = [
     *   'someKey' => [
     *      'before' => ['someKeyA', 'someKeyB']
     *      'after' => ['someKeyC']
     *   ]
     * ]
     *
     * We interpret a dependency like
     *   'A' => [
     *     'before' => ['B'],
     *     'after' => ['C', 'D']
     *   ]
     * as
     *   - A depends on C
     *   - A depends on D
     *   - B depends on A
     *
     * @param array $dependencies
     * @param string $beforeKey The key to use in a dependency which specifies the "before"-relation. eg. 'sortBefore', 'loadBefore'
     * @param string $afterKey The key to use in a dependency which specifies the "after"-relation. eg. 'sortAfter', 'loadAfter'
     * @return bool[][] The dependency graph
     */
    public function buildDependencyGraph(array $dependencies, $beforeKey = 'before', $afterKey = 'after')
    {
        $dependencies = $this->prepareDependencies($dependencies, $beforeKey, $afterKey);

        $identifiers = array_keys($dependencies);
        sort($identifiers);
        // $dependencyGraph is the adjacency matrix as two-dimensional array initialized to FALSE (empty graph)
        /** @var bool[][] $dependencyGraph */
        $dependencyGraph = array_fill_keys($identifiers, array_fill_keys($identifiers, false));

        foreach ($identifiers as $id) {
            foreach ($dependencies[$id][$beforeKey] as $beforeId) {
                $dependencyGraph[$beforeId][$id] = true;
            }
            foreach ($dependencies[$id][$afterKey] as $afterId) {
                $dependencyGraph[$id][$afterId] = true;
            }
        }

        // @internal DependencyResolver
        // this is a dirty special case for suggestion handling of packages
        // see \TYPO3\CMS\Core\Package\DependencyResolver::convertConfigurationForGraph for details
        // DO NOT use this for any other case
        foreach ($identifiers as $id) {
            if (isset($dependencies[$id]['after-resilient'])) {
                foreach ($dependencies[$id]['after-resilient'] as $afterId) {
                    $reverseDependencies = $this->findPathInGraph($dependencyGraph, $afterId, $id);
                    if (empty($reverseDependencies)) {
                        $dependencyGraph[$id][$afterId] = true;
                    }
                }
            }
        }

        return $dependencyGraph;
    }

    /**
     * Calculate an ordered list for a dependencyGraph
     *
     * @param bool[][] $dependencyGraph
     * @return mixed[] Sorted array of keys of $dependencies
     * @throws \UnexpectedValueException
     */
    public function calculateOrder(array $dependencyGraph)
    {
        $rootIds = array_flip($this->findRootIds($dependencyGraph));

        // Add number of dependencies for each root node
        foreach ($rootIds as $id => &$dependencies) {
            $dependencies = count(array_filter($dependencyGraph[$id]));
        }
        unset($dependencies);

        // This will contain our final result in reverse order,
        // meaning a result of [A, B, C] equals "A after B after C"
        $sortedIds = [];

        // Walk through the graph, level by level
        while (!empty($rootIds)) {
            ksort($rootIds);
            // We take those with fewer dependencies first, to have them at the end of the list in the final result.
            $minimum = PHP_INT_MAX;
            $currentId = 0;
            foreach ($rootIds as $id => $count) {
                if ($count <= $minimum) {
                    $minimum = $count;
                    $currentId = $id;
                }
            }
            unset($rootIds[$currentId]);

            $sortedIds[] = $currentId;

            // Process the dependencies of the current node
            foreach (array_filter($dependencyGraph[$currentId]) as $dependingId => $_) {
                // Remove the edge to this dependency
                $dependencyGraph[$currentId][$dependingId] = false;
                if (!$this->getIncomingEdgeCount($dependencyGraph, $dependingId)) {
                    // We found a new root, lets add it to the list
                    $rootIds[$dependingId] = count(array_filter($dependencyGraph[$dependingId]));
                }
            }
        }

        // Check for remaining edges in the graph
        $cycles = [];
        array_walk($dependencyGraph, function ($dependencies, $fromId) use (&$cycles) {
            array_walk($dependencies, function ($dependency, $toId) use (&$cycles, $fromId) {
                if ($dependency) {
                    $cycles[] = $fromId . '->' . $toId;
                }
            });
        });
        if (!empty($cycles)) {
            throw new \UnexpectedValueException('Your dependencies have cycles. That will not work out. Cycles found: ' . implode(', ', $cycles), 1381960494);
        }

        // We now built a list of dependencies
        // Reverse the list to get the correct sorting order
        return array_reverse($sortedIds);
    }

    /**
     * Get the number of incoming edges in the dependency graph for given identifier
     *
     * @param array $dependencyGraph
     * @param string $identifier
     * @return int
     */
    protected function getIncomingEdgeCount(array $dependencyGraph, $identifier)
    {
        $incomingEdgeCount = 0;
        foreach ($dependencyGraph as $dependencies) {
            if ($dependencies[$identifier]) {
                $incomingEdgeCount++;
            }
        }
        return $incomingEdgeCount;
    }

    /**
     * Find all root nodes of a graph
     *
     * Root nodes are those, where nothing else depends on (they can be the last in the loading order).
     * If there are no dependencies at all, all nodes are root nodes.
     *
     * @param bool[][] $dependencyGraph
     * @return array List of identifiers which are root nodes
     */
    public function findRootIds(array $dependencyGraph)
    {
        // Filter nodes with no incoming edge (aka root nodes)
        $rootIds = [];
        foreach ($dependencyGraph as $id => $_) {
            if (!$this->getIncomingEdgeCount($dependencyGraph, $id)) {
                $rootIds[] = $id;
            }
        }
        return $rootIds;
    }

    /**
     * Find any path in the graph from given start node to destination node
     *
     * @param array $graph Directed graph
     * @param string $from Start node
     * @param string $to Destination node
     * @return array Nodes of the found path; empty if no path is found
     */
    protected function findPathInGraph(array $graph, $from, $to)
    {
        foreach (array_filter($graph[$from]) as $node => $_) {
            if ($node === $to) {
                return [$from, $to];
            }
            $subPath = $this->findPathInGraph($graph, $node, $to);
            if (!empty($subPath)) {
                array_unshift($subPath, $from);
                return $subPath;
            }
        }
        return [];
    }

    /**
     * Prepare dependencies
     *
     * Ensure that all discovered identifiers are added to the dependency list
     * so we can reliably use the identifiers to build the matrix.
     * Additionally fix all invalid or missing before/after arrays
     *
     * @param array $dependencies
     * @param string $beforeKey The key to use in a dependency which specifies the "before"-relation. eg. 'sortBefore', 'loadBefore'
     * @param string $afterKey The key to use in a dependency which specifies the "after"-relation. eg. 'sortAfter', 'loadAfter'
     * @return array Prepared dependencies
     */
    protected function prepareDependencies(array $dependencies, $beforeKey = 'before', $afterKey = 'after')
    {
        $preparedDependencies = [];
        foreach ($dependencies as $id => $dependency) {
            foreach ([ $beforeKey, $afterKey ] as $relation) {
                if (!isset($dependency[$relation]) || !is_array($dependency[$relation])) {
                    $dependency[$relation] = [];
                }
                // add all missing, but referenced identifiers to the $dependency list
                foreach ($dependency[$relation] as $dependingId) {
                    if (!isset($dependencies[$dependingId]) && !isset($preparedDependencies[$dependingId])) {
                        $preparedDependencies[$dependingId] = [
                            $beforeKey => [],
                            $afterKey => []
                        ];
                    }
                }
            }
            $preparedDependencies[$id] = $dependency;
        }
        return $preparedDependencies;
    }
}
