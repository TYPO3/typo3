<?php

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Dependency;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;
use TYPO3\CMS\Workspaces\Dependency\ElementEntityProcessor;
use TYPO3\CMS\Workspaces\Dependency\EventCallback;
use TYPO3\CMS\Workspaces\Dependency\ReferenceEntity;
use TYPO3\CMS\Workspaces\Service\GridDataService;

/**
 * Service to collect dependent elements.
 */
class CollectionService implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected $dataHandler;

    /**
     * @var Dependency\ElementEntityProcessor|null
     */
    protected $elementEntityProcessor;

    /**
     * @var Dependency\DependencyResolver|null
     */
    protected $dependencyResolver;

    /**
     * @var array
     */
    protected $dataArray;

    /**
     * @var array
     */
    protected $nestedDataArray;

    /**
     * @return Dependency\DependencyResolver
     */
    public function getDependencyResolver()
    {
        if (!isset($this->dependencyResolver)) {
            $this->dependencyResolver = GeneralUtility::makeInstance(DependencyResolver::class);
            $this->dependencyResolver->setOuterMostParentsRequireReferences(true);
            $this->dependencyResolver->setWorkspace($this->getWorkspace());

            $this->dependencyResolver->setEventCallback(
                ElementEntity::EVENT_Construct,
                $this->getDependencyCallback('createNewDependentElementCallback')
            );

            $this->dependencyResolver->setEventCallback(
                ElementEntity::EVENT_CreateChildReference,
                $this->getDependencyCallback('createNewDependentElementChildReferenceCallback')
            );

            $this->dependencyResolver->setEventCallback(
                ElementEntity::EVENT_CreateParentReference,
                $this->getDependencyCallback('createNewDependentElementParentReferenceCallback')
            );
        }

        return $this->dependencyResolver;
    }

    /**
     * Gets a new callback to be used in the dependency resolver utility.
     *
     * @param string $method
     * @param array $targetArguments
     * @return Dependency\EventCallback
     */
    protected function getDependencyCallback($method, array $targetArguments = [])
    {
        return GeneralUtility::makeInstance(
            EventCallback::class,
            $this->getElementEntityProcessor(),
            $method,
            $targetArguments
        );
    }

    /**
     * Gets the element entity processor.
     *
     * @return Dependency\ElementEntityProcessor
     */
    protected function getElementEntityProcessor()
    {
        if (!isset($this->elementEntityProcessor)) {
            $this->elementEntityProcessor = GeneralUtility::makeInstance(ElementEntityProcessor::class);
            $this->elementEntityProcessor->setWorkspace($this->getWorkspace());
        }
        return $this->elementEntityProcessor;
    }

    /**
     * Gets the current workspace id.
     *
     * @return int
     */
    protected function getWorkspace()
    {
        return (int)$GLOBALS['BE_USER']->workspace;
    }

    /**
     * Processes the data array
     *
     * @param array $dataArray
     * @return array
     */
    public function process(array $dataArray)
    {
        $collection = 0;
        $this->dataArray = $dataArray;
        $this->nestedDataArray = [];

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

        unset($this->dataArray);
        unset($this->nestedDataArray);

        return $processedDataArray;
    }

    /**
     * Applies structures to instance data array and
     * ensures children are added below accordant parent
     *
     * @param array $dataArray
     * @return array
     */
    protected function finalize(array $dataArray)
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
     * @param Dependency\ElementEntity $parent
     * @param int $collection
     * @param string $nextParentIdentifier
     * @param int $collectionLevel
     */
    protected function resolveDataArrayChildDependencies(ElementEntity $parent, $collection, $nextParentIdentifier = '', $collectionLevel = 0)
    {
        $parentIdentifier = $parent->__toString();
        $parentIsSet = isset($this->dataArray[$parentIdentifier]);

        if ($parentIsSet) {
            $this->dataArray[$parentIdentifier][GridDataService::GridColumn_Collection] = $collection;
            $this->dataArray[$parentIdentifier][GridDataService::GridColumn_CollectionLevel] = $collectionLevel;
            $this->dataArray[$parentIdentifier][GridDataService::GridColumn_CollectionCurrent] = md5($parentIdentifier);
            $this->dataArray[$parentIdentifier][GridDataService::GridColumn_CollectionChildren] = $this->getCollectionChildrenCount($parent->getChildren());
            $nextParentIdentifier = $parentIdentifier;
            $collectionLevel++;
        }

        foreach ($parent->getChildren() as $child) {
            $this->resolveDataArrayChildDependencies(
                $child->getElement(),
                $collection,
                $nextParentIdentifier,
                $collectionLevel
            );

            $childIdentifier = $child->getElement()->__toString();
            if (!empty($nextParentIdentifier) && isset($this->dataArray[$childIdentifier])) {
                // Remove from dataArray, but collect to process later
                // and add it just next to the accordant parent element
                $this->dataArray[$childIdentifier][GridDataService::GridColumn_CollectionParent] = md5($nextParentIdentifier);
                $this->nestedDataArray[$nextParentIdentifier][] = $this->dataArray[$childIdentifier];
                unset($this->dataArray[$childIdentifier]);
            }
        }
    }

    /**
     * Return count of children, present in the data array
     *
     * @param ReferenceEntity[] $children
     * @return int
     */
    protected function getCollectionChildrenCount(array $children): int
    {
        return count(
            array_filter($children, function (ReferenceEntity $child) {
                return isset($this->dataArray[$child->getElement()->__toString()]);
            })
        );
    }
}
