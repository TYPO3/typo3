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

namespace TYPO3\CMS\Workspaces\Service\Dependency;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;
use TYPO3\CMS\Workspaces\Dependency\ReferenceEntity;

/**
 * Service to collect dependent elements.
 *
 * @internal
 */
class CollectionService implements SingletonInterface
{
    protected ?DependencyResolver $dependencyResolver = null;
    protected array $dataArray;
    protected array $nestedDataArray;

    /**
     * Contexts elements have already been resolved in, see
     * resolveDataArrayChildDependencies() for details.
     *
     * @var array<string, true>
     */
    protected array $visitedContexts;

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function getDependencyResolver(): DependencyResolver
    {
        if (!isset($this->dependencyResolver)) {
            $this->dependencyResolver = GeneralUtility::makeInstance(DependencyResolver::class);
            $this->dependencyResolver->setWorkspace($this->getBackendUser()->workspace);
            $this->dependencyResolver->setEventDispatcher($this->eventDispatcher);
            $this->dependencyResolver->setAction(DependencyCollectionAction::Display);
        }
        return $this->dependencyResolver;
    }

    /**
     * Processes the data array
     */
    public function process(array $dataArray): array
    {
        $collection = 0;
        $this->dataArray = $dataArray;
        $this->nestedDataArray = [];
        $this->visitedContexts = [];

        $outerMostParents = $this->getDependencyResolver()->getOuterMostParents();

        if (empty($outerMostParents)) {
            return $this->dataArray;
        }

        // For each outer most parent, get all nested child elements:
        foreach ($outerMostParents as $outerMostParent) {
            $this->resolveDataArrayChildDependencies(
                $outerMostParent,
                ++$collection
            );
        }

        $processedDataArray = $this->finalize($this->dataArray);

        $this->dataArray = [];
        $this->nestedDataArray = [];
        $this->visitedContexts = [];

        return $processedDataArray;
    }

    /**
     * Applies structures to instance data array and
     * ensures children are added below accordant parent
     */
    protected function finalize(array $dataArray): array
    {
        $processedDataArray = [];
        foreach ($dataArray as $dataElement) {
            $dataElementIdentifier = $dataElement['id'];
            $processedDataArray[] = $dataElement;
            // Insert children (if any)
            if (!empty($this->nestedDataArray[$dataElementIdentifier])) {
                $processedDataArray = array_merge(
                    $processedDataArray,
                    $this->finalize($this->nestedDataArray[$dataElementIdentifier])
                );
                unset($this->nestedDataArray[$dataElementIdentifier]);
            }
        }

        return $processedDataArray;
    }

    /**
     * Resolves nested child dependencies.
     *
     * @param array<string, true> $entryPath Elements of the branch that is currently traversed
     */
    protected function resolveDataArrayChildDependencies(ElementEntity $parent, int $collection, string $nextParentIdentifier = '', int $collectionLevel = 0, array $entryPath = []): void
    {
        $parentIdentifier = $parent->__toString();
        // Keep track of the current branch to avoid endless recursion in case
        // relations form a cycle, for example A -> B -> A. Child edges pointing
        // back to this branch are skipped below.
        $entryPath[$parentIdentifier] = true;
        // Resolving an element again in the very same context cannot add anything the
        // first pass did not already do. Skipping those keeps densely cross referenced
        // structures from being walked along every possible path through the graph.
        $contextIdentifier = $parentIdentifier . '/' . $nextParentIdentifier . '/' . $collection . '/' . $collectionLevel;
        if (isset($this->visitedContexts[$contextIdentifier])) {
            return;
        }
        $this->visitedContexts[$contextIdentifier] = true;

        $parentIsSet = isset($this->dataArray[$parentIdentifier]);

        if ($parentIsSet) {
            $this->dataArray[$parentIdentifier]['Workspaces_Collection'] = $collection;
            $this->dataArray[$parentIdentifier]['Workspaces_CollectionLevel'] = $collectionLevel;
            $this->dataArray[$parentIdentifier]['Workspaces_CollectionCurrent'] = md5($parentIdentifier);
            $this->dataArray[$parentIdentifier]['Workspaces_CollectionChildren'] = $this->getCollectionChildrenCount($parent->getChildren(), $entryPath);
            $nextParentIdentifier = $parentIdentifier;
            $collectionLevel++;
        }

        foreach ($parent->getChildren() as $child) {
            $childElement = $child->getElement();
            $childIdentifier = $childElement->__toString();
            // Skip child edges that would point back to an ancestor in the
            // current branch. The same element can still be processed in
            // another branch.
            if (isset($entryPath[$childIdentifier])) {
                continue;
            }
            $this->resolveDataArrayChildDependencies(
                $childElement,
                $collection,
                $nextParentIdentifier,
                $collectionLevel,
                $entryPath
            );
            if (!empty($nextParentIdentifier) && isset($this->dataArray[$childIdentifier])) {
                // Remove from dataArray, but collect to process later
                // and add it just next to the accordant parent element
                $this->dataArray[$childIdentifier]['Workspaces_CollectionParent'] = md5($nextParentIdentifier);
                $this->nestedDataArray[$nextParentIdentifier][] = $this->dataArray[$childIdentifier];
                unset($this->dataArray[$childIdentifier]);
            }
        }
    }

    /**
     * Return count of children, present in the data array
     *
     * @param ReferenceEntity[] $children
     * @param array<string, true> $entryPath Elements of the branch that is currently traversed
     */
    protected function getCollectionChildrenCount(array $children, array $entryPath): int
    {
        return count(
            array_filter($children, function (ReferenceEntity $child) use ($entryPath) {
                $childIdentifier = $child->getElement()->__toString();
                // Circular child edges are skipped by the resolver and should
                // not contribute to the displayed child count.
                return !isset($entryPath[$childIdentifier]) && isset($this->dataArray[$childIdentifier]);
            })
        );
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
