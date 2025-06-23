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

namespace TYPO3\CMS\Workspaces\DataHandler;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;
use TYPO3\CMS\Workspaces\Dependency\ElementEntityProcessor;
use TYPO3\CMS\Workspaces\Dependency\EventCallback;

/**
 * Handles the \TYPO3\CMS\Core\DataHandling\DataHandler command map and is
 * only used in combination with \TYPO3\CMS\Core\DataHandling\DataHandler
 *
 * @internal
 */
readonly class CommandMap
{
    public function __construct(
        protected ElementEntityProcessor $elementEntityProcessor,
    ) {}

    /**
     * Processes the command map.
     */
    public function process(array $commandMap, int $workspace): array
    {
        $commandMap = $this->resolveWorkspacesPublishDependencies($commandMap, $workspace);
        $commandMap = $this->resolveWorkspacesSetStageDependencies($commandMap, $workspace);
        return $this->resolveWorkspacesDiscardDependencies($commandMap, $workspace);
    }

    /**
     * Resolves workspaces related dependencies for swapping/publishing of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not published with this request, are removed from the command map. Otherwise
     * this would produce hanging record sets and lost references.
     */
    protected function resolveWorkspacesPublishDependencies(array $commandMap, int $workspace): array
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($workspace);
        $dependency->setEventCallback(
            ElementEntity::EVENT_Construct,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createNewDependentElementCallback', ['workspace' => $workspace])
        );
        $dependency->setEventCallback(
            ElementEntity::EVENT_CreateChildReference,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createNewDependentElementChildReferenceCallback')
        );
        $dependency->setEventCallback(
            ElementEntity::EVENT_CreateParentReference,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createNewDependentElementParentReferenceCallback')
        );
        foreach ($commandMap as $table => $liveIdCollection) {
            foreach ($liveIdCollection as $liveId => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'version' && isset($properties['action']) && in_array($properties['action'], ['publish', 'swap'], true)) {
                        if (isset($properties['swapWith']) && MathUtility::canBeInterpretedAsInteger($properties['swapWith'])) {
                            $dependency->addElement($table, (int)$properties['swapWith'], ['liveId' => $liveId, 'properties' => $properties]);
                        }
                    }
                }
            }
        }
        $elementsToBeVersioned = $dependency->getElements();
        // Use the uid of the live record instead of the workspace record:
        $elementsToBeVersioned = $this->transformDependentElementsToUseLiveId($elementsToBeVersioned);
        $outerMostParents = $dependency->getOuterMostParents();
        foreach ($outerMostParents as $outerMostParent) {
            $dependentElements = $dependency->getNestedElements($outerMostParent);
            $dependentElements = $this->transformDependentElementsToUseLiveId($dependentElements);
            // Gets the difference (intersection) between elements that were submitted by the user
            // and the evaluation of all dependent records that should be used for this action instead:
            $intersectingElements = array_intersect_key($dependentElements, $elementsToBeVersioned);
            if (!empty($intersectingElements)) {
                $intersectingElement = current($intersectingElements);
                $orderedCommandMap = [];
                $commonProperties = $this->getCommonSwapProperties($intersectingElement);
                /** @var ElementEntity $element */
                foreach ($dependentElements as $element) {
                    $table = $element->getTable();
                    $id = $element->getDataValue('liveId');
                    unset($commandMap[$table][$id]['version']);
                    if ($element->isInvalid()) {
                        continue;
                    }
                    $orderedCommandMap[$table][$id]['version'] = array_merge($commonProperties, ['swapWith' => $element->getId()]);
                }
                // Ensure that ordered command map is on top of the command map:
                ArrayUtility::mergeRecursiveWithOverrule($orderedCommandMap, $commandMap);
                $commandMap = $orderedCommandMap;
            }
        }
        return $commandMap;
    }

    /**
     * Resolves workspaces related dependencies for staging of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not staged with this request, are removed from the command map.
     */
    protected function resolveWorkspacesSetStageDependencies(array $commandMap, int $workspace): array
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($workspace);
        $dependency->setEventCallback(
            ElementEntity::EVENT_CreateChildReference,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createNewDependentElementChildReferenceCallback')
        );
        $dependency->setEventCallback(
            ElementEntity::EVENT_CreateParentReference,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createNewDependentElementParentReferenceCallback')
        );
        foreach ($commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionIdList => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'version' && isset($properties['action']) && $properties['action'] === 'setStage') {
                        if (isset($properties['stageId']) && MathUtility::canBeInterpretedAsInteger($properties['stageId'])) {
                            $commandMap = $this->explodeSetStage($commandMap, $table, $versionIdList, $properties);
                        }
                    }
                }
            }
        }
        foreach ($commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionIdList => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'version' && isset($properties['action']) && $properties['action'] === 'setStage') {
                        if (isset($properties['stageId']) && MathUtility::canBeInterpretedAsInteger($properties['stageId'])) {
                            $dependency->addElement($table, $versionIdList, ['versionId' => $versionIdList, 'properties' => $properties]);
                        }
                    }
                }
            }
        }
        $elementsToBeVersioned = $dependency->getElements();
        $outerMostParents = $dependency->getOuterMostParents();
        foreach ($outerMostParents as $outerMostParent) {
            $dependentElements = $dependency->getNestedElements($outerMostParent);
            // Gets the difference (intersection) between elements that were submitted by the user
            // and the evaluation of all dependent records that should be used for this action instead:
            $intersectingElements = array_intersect_key($dependentElements, $elementsToBeVersioned);
            if (!empty($intersectingElements)) {
                $intersectingElement = current($intersectingElements);
                $orderedCommandMap = [];
                $commonProperties = $this->getSetStageProperties($intersectingElement);
                /** @var ElementEntity $element */
                foreach ($dependentElements as $element) {
                    $table = $element->getTable();
                    $id = $element->getId();
                    unset($commandMap[$table][$id]['version']);
                    if ($element->isInvalid()) {
                        continue;
                    }
                    $orderedCommandMap[$table][$id]['version'] = array_merge($commonProperties, $this->getSetStageProperties($element));
                }
                // Ensure that ordered command map is on top of the command map:
                ArrayUtility::mergeRecursiveWithOverrule($orderedCommandMap, $commandMap);
                $commandMap = $orderedCommandMap;
            }
        }
        return $commandMap;
    }

    /**
     * Resolves workspaces related dependencies for clearing/flushing of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not cleared/flushed with this request, are removed from the command map.
     */
    protected function resolveWorkspacesDiscardDependencies(array $commandMap, int $workspace): array
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($workspace);
        $dependency->setEventCallback(
            ElementEntity::EVENT_CreateChildReference,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createClearDependentElementChildReferenceCallback')
        );
        $dependency->setEventCallback(
            ElementEntity::EVENT_CreateParentReference,
            GeneralUtility::makeInstance(EventCallback::class, $this->elementEntityProcessor, 'createClearDependentElementParentReferenceCallback')
        );
        // Traverses the cmd[] array and fetches the accordant actions:
        foreach ($commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionId => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'discard') {
                        $dependency->addElement($table, $versionId, ['versionId' => $versionId, 'properties' => $properties]);
                    }
                    // @todo: this cane be removed once testing framework is not using 'cleaWSID' and 'flush' anymore
                    if ($command === 'version' && isset($properties['action']) && ($properties['action'] === 'clearWSID' || $properties['action'] === 'flush')) {
                        $dependency->addElement($table, $versionId, ['versionId' => $versionId, 'properties' => $properties]);
                    }
                }
            }
        }
        $elementsToBeVersioned = $dependency->getElements();
        $outerMostParents = $dependency->getOuterMostParents();
        foreach ($outerMostParents as $outerMostParent) {
            $dependentElements = $dependency->getNestedElements($outerMostParent);
            // Gets the difference (intersection) between elements that were submitted by the user
            // and the evaluation of all dependent records that should be used for this action instead:
            $intersectingElements = array_intersect_key($dependentElements, $elementsToBeVersioned);
            if (!empty($intersectingElements)) {
                $intersectingElement = current($intersectingElements);
                $orderedCommandMap = [];
                $commonProperties = $this->getCommonClearProperties($intersectingElement);
                /** @var ElementEntity $element */
                foreach ($dependentElements as $element) {
                    $table = $element->getTable();
                    $id = $element->getId();
                    unset($commandMap[$table][$id]['version']);
                    if ($element->isInvalid()) {
                        continue;
                    }
                    $orderedCommandMap[$table][$id]['version'] = $commonProperties;
                }
                // Ensure that ordered command map is on top of the command map:
                ArrayUtility::mergeRecursiveWithOverrule($orderedCommandMap, $commandMap);
                $commandMap = $orderedCommandMap;
            }
        }
        return $commandMap;
    }

    /**
     * Explodes id-lists in the command map for staging actions.
     */
    protected function explodeSetStage(array $commandMap, string $table, string|int $versionIdList, array $properties): array
    {
        $extractedCommandMap = [];
        $versionIds = GeneralUtility::trimExplode(',', (string)$versionIdList, true);
        if (count($versionIds) > 1) {
            foreach ($versionIds as $versionId) {
                if (isset($commandMap[$table][$versionId]['version'])) {
                    throw new \RuntimeException('Command map for [' . $table . '][' . $versionId . '][version] was already set.', 1289391048);
                }
                $extractedCommandMap[$table][$versionId]['version'] = $properties;
            }
            // Merge command map elements to the bottom of the current command map.
            ArrayUtility::mergeRecursiveWithOverrule($commandMap, $extractedCommandMap);
        }
        return $commandMap;
    }

    /**
     * Transforms dependent elements to use the liveId as array key.
     *
     * @param ElementEntity[] $elements
     */
    protected function transformDependentElementsToUseLiveId(array $elements): array
    {
        $transformedElements = [];
        foreach ($elements as $element) {
            $elementName = $element->getTable() . ':' . $element->getDataValue('liveId');
            $transformedElements[$elementName] = $element;
        }
        return $transformedElements;
    }

    /**
     * Callback to get common properties of dependent elements for clearing.
     */
    protected function getCommonClearProperties(ElementEntity $element): array
    {
        $commonSwapProperties = [];
        $elementProperties = $element->getDataValue('properties');
        if (isset($elementProperties['action'])) {
            $commonSwapProperties['action'] = $elementProperties['action'];
        }
        return $commonSwapProperties;
    }

    /**
     * Callback to get common properties of dependent elements for swapping/publishing.
     */
    protected function getCommonSwapProperties(ElementEntity $element): array
    {
        $commonSwapProperties = [];
        $elementProperties = $element->getDataValue('properties');
        if (isset($elementProperties['action'])) {
            $commonSwapProperties['action'] = $elementProperties['action'];
        }
        if (isset($elementProperties['comment'])) {
            $commonSwapProperties['comment'] = $elementProperties['comment'];
        }
        if (isset($elementProperties['notificationAlternativeRecipients'])) {
            $commonSwapProperties['notificationAlternativeRecipients'] = $elementProperties['notificationAlternativeRecipients'];
        }
        return $commonSwapProperties;
    }

    /**
     * Callback to get common properties of dependent elements for staging.
     */
    protected function getSetStageProperties(ElementEntity $element): array
    {
        $commonSetStageProperties = [];
        $elementProperties = $element->getDataValue('properties');
        if (isset($elementProperties['stageId'])) {
            $commonSetStageProperties['stageId'] = $elementProperties['stageId'];
        }
        if (isset($elementProperties['comment'])) {
            $commonSetStageProperties['comment'] = $elementProperties['comment'];
        }
        if (isset($elementProperties['action'])) {
            $commonSetStageProperties['action'] = $elementProperties['action'];
        }
        if (isset($elementProperties['notificationAlternativeRecipients'])) {
            $commonSetStageProperties['notificationAlternativeRecipients'] = $elementProperties['notificationAlternativeRecipients'];
        }
        return $commonSetStageProperties;
    }
}
