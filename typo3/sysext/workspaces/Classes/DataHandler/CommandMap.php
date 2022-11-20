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
 */
class CommandMap
{
    public const SCOPE_WorkspacesSwap = 'SCOPE_WorkspacesSwap';
    public const SCOPE_WorkspacesSetStage = 'SCOPE_WorkspacesSetStage';
    public const SCOPE_WorkspacesClear = 'SCOPE_WorkspacesClear';
    public const KEY_GetElementPropertiesCallback = 'KEY_GetElementPropertiesCallback';
    public const KEY_GetCommonPropertiesCallback = 'KEY_GetCommonPropertiesCallback';
    public const KEY_ElementConstructCallback = 'KEY_EventConstructCallback';
    public const KEY_ElementCreateChildReferenceCallback = 'KEY_ElementCreateChildReferenceCallback';
    public const KEY_ElementCreateParentReferenceCallback = 'KEY_ElementCreateParentReferenceCallback';
    public const KEY_UpdateGetIdCallback = 'KEY_UpdateGetIdCallback';
    public const KEY_TransformDependentElementsToUseLiveId = 'KEY_TransformDependentElementsToUseLiveId';

    /**
     * @var array
     */
    protected $commandMap = [];

    protected int $workspace;

    /**
     * @var array
     */
    protected $scopes;

    /**
     * @var ElementEntityProcessor|null
     */
    protected $elementEntityProcessor;

    /**
     * Creates this object.
     *
     * @param int $workspace
     */
    public function __construct(array $commandMap, $workspace)
    {
        $this->set($commandMap);
        $this->setWorkspace($workspace);
        $this->constructScopes();
    }

    /**
     * Gets the command map.
     *
     * @return array
     */
    public function get()
    {
        return $this->commandMap;
    }

    /**
     * Sets the command map.
     *
     * @return CommandMap
     */
    public function set(array $commandMap)
    {
        $this->commandMap = $commandMap;
        return $this;
    }

    /**
     * Sets the current workspace.
     *
     * @param int $workspace
     */
    public function setWorkspace($workspace)
    {
        $this->workspace = (int)$workspace;
    }

    /**
     * Gets the current workspace.
     */
    public function getWorkspace(): int
    {
        return $this->workspace;
    }

    /**
     * Gets the element entity processor.
     *
     * @return ElementEntityProcessor
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
     * Processes the command map.
     *
     * @return CommandMap
     */
    public function process()
    {
        $this->resolveWorkspacesSwapDependencies();
        $this->resolveWorkspacesSetStageDependencies();
        $this->resolveWorkspacesClearDependencies();
        return $this;
    }

    /**
     * Resolves workspaces related dependencies for swapping/publishing of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not published with this request, are removed from the command map. Otherwise
     * this would produce hanging record sets and lost references.
     */
    protected function resolveWorkspacesSwapDependencies()
    {
        $scope = self::SCOPE_WorkspacesSwap;
        $dependency = $this->getDependencyUtility($scope);
        $arguments = [$dependency];
        foreach ($this->commandMap as $table => $liveIdCollection) {
            foreach ($liveIdCollection as $liveId => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'version' && isset($properties['action']) && in_array($properties['action'], ['publish', 'swap'], true)) {
                        if (isset($properties['swapWith']) && MathUtility::canBeInterpretedAsInteger($properties['swapWith'])) {
                            $this->addWorkspacesSwapElements(...array_merge($arguments, [$table, $liveId, $properties]));
                        }
                    }
                }
            }
        }
        $this->applyWorkspacesDependencies($dependency, $scope);
    }

    /**
     * Adds workspaces elements for swapping/publishing.
     *
     * @param string $table
     * @param int $liveId
     */
    protected function addWorkspacesSwapElements(DependencyResolver $dependency, $table, $liveId, array $properties)
    {
        $dependency->addElement($table, $properties['swapWith'], ['liveId' => $liveId, 'properties' => $properties]);
    }

    /**
     * Invokes all items for staging with a callback method.
     *
     * @param string $callbackMethod
     * @param array $arguments Optional leading arguments for the callback method
     */
    protected function invokeWorkspacesSetStageItems($callbackMethod, array $arguments = [])
    {
        // Traverses the cmd[] array and fetches the accordant actions:
        foreach ($this->commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionIdList => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'version' && isset($properties['action']) && $properties['action'] === 'setStage') {
                        if (isset($properties['stageId']) && MathUtility::canBeInterpretedAsInteger($properties['stageId'])) {
                            $this->$callbackMethod(...array_merge($arguments, [$table, $versionIdList, $properties]));
                        }
                    }
                }
            }
        }
    }

    /**
     * Resolves workspaces related dependencies for staging of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not staged with this request, are removed from the command map.
     */
    protected function resolveWorkspacesSetStageDependencies()
    {
        $scope = self::SCOPE_WorkspacesSetStage;
        $dependency = $this->getDependencyUtility($scope);
        $this->invokeWorkspacesSetStageItems('explodeSetStage');
        $this->invokeWorkspacesSetStageItems('addWorkspacesSetStageElements', [$dependency]);
        $this->applyWorkspacesDependencies($dependency, $scope);
    }

    /**
     * Adds workspaces elements for staging.
     *
     * @param string $table
     * @param int $versionId
     */
    protected function addWorkspacesSetStageElements(DependencyResolver $dependency, $table, $versionId, array $properties)
    {
        $dependency->addElement($table, $versionId, ['versionId' => $versionId, 'properties' => $properties]);
    }

    /**
     * Resolves workspaces related dependencies for clearing/flushing of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not cleared/flushed with this request, are removed from the command map.
     */
    protected function resolveWorkspacesClearDependencies()
    {
        $scope = self::SCOPE_WorkspacesClear;
        $dependency = $this->getDependencyUtility($scope);
        // Traverses the cmd[] array and fetches the accordant actions:
        foreach ($this->commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionId => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'version' && isset($properties['action']) && ($properties['action'] === 'clearWSID' || $properties['action'] === 'flush')) {
                        $dependency->addElement($table, $versionId, ['versionId' => $versionId, 'properties' => $properties]);
                    }
                }
            }
        }
        $this->applyWorkspacesDependencies($dependency, $scope);
    }

    /**
     * Explodes id-lists in the command map for staging actions.
     *
     * @throws \RuntimeException
     * @param string $table
     * @param string $versionIdList
     */
    protected function explodeSetStage($table, $versionIdList, array $properties)
    {
        $extractedCommandMap = [];
        $versionIds = GeneralUtility::trimExplode(',', $versionIdList, true);
        if (count($versionIds) > 1) {
            foreach ($versionIds as $versionId) {
                if (isset($this->commandMap[$table][$versionId]['version'])) {
                    throw new \RuntimeException('Command map for [' . $table . '][' . $versionId . '][version] was already set.', 1289391048);
                }
                $extractedCommandMap[$table][$versionId]['version'] = $properties;
                $this->remove($table, $versionId, 'version');
            }
            $this->mergeToBottom($extractedCommandMap);
        }
    }

    /**
     * Applies the workspaces dependencies and removes incomplete structures or automatically
     * completes them
     *
     * @param string $scope
     */
    protected function applyWorkspacesDependencies(DependencyResolver $dependency, $scope)
    {
        $transformDependentElementsToUseLiveId = $this->getScopeData($scope, self::KEY_TransformDependentElementsToUseLiveId);
        $elementsToBeVersioned = $dependency->getElements();
        // Use the uid of the live record instead of the workspace record:
        if ($transformDependentElementsToUseLiveId) {
            $elementsToBeVersioned = $this->getElementEntityProcessor()->transformDependentElementsToUseLiveId($elementsToBeVersioned);
        }
        $outerMostParents = $dependency->getOuterMostParents();
        /** @var ElementEntity $outerMostParent */
        foreach ($outerMostParents as $outerMostParent) {
            $dependentElements = $dependency->getNestedElements($outerMostParent);
            if ($transformDependentElementsToUseLiveId) {
                $dependentElements = $this->getElementEntityProcessor()->transformDependentElementsToUseLiveId($dependentElements);
            }
            // Gets the difference (intersection) between elements that were submitted by the user
            // and the evaluation of all dependent records that should be used for this action instead:
            $intersectingElements = array_intersect_key($dependentElements, $elementsToBeVersioned);
            if (!empty($intersectingElements)) {
                $this->update(current($intersectingElements), $dependentElements, $scope);
            }
        }
    }

    /**
     * Updates the command map accordant to valid structures and takes care of the correct order.
     *
     * @param string $scope
     */
    protected function update(ElementEntity $intersectingElement, array $elements, $scope)
    {
        $orderedCommandMap = [];
        $commonProperties = [];
        if ($this->getScopeData($scope, self::KEY_GetCommonPropertiesCallback)) {
            $commonProperties = $this->processCallback($this->getScopeData($scope, self::KEY_GetCommonPropertiesCallback), [$intersectingElement]);
        }
        /** @var ElementEntity $element */
        foreach ($elements as $element) {
            $table = $element->getTable();
            $id = $this->processCallback($this->getScopeData($scope, self::KEY_UpdateGetIdCallback), [$element]);
            $this->remove($table, $id, 'version');
            if ($element->isInvalid()) {
                continue;
            }
            $orderedCommandMap[$table][$id]['version'] = $commonProperties;
            if ($this->getScopeData($scope, self::KEY_GetElementPropertiesCallback)) {
                $orderedCommandMap[$table][$id]['version'] = array_merge($commonProperties, $this->processCallback($this->getScopeData($scope, self::KEY_GetElementPropertiesCallback), [$element]));
            }
        }
        // Ensure that ordered command map is on top of the command map:
        $this->mergeToTop($orderedCommandMap);
    }

    /**
     * Merges command map elements to the top of the current command map..
     */
    protected function mergeToTop(array $commandMap)
    {
        ArrayUtility::mergeRecursiveWithOverrule($commandMap, $this->commandMap);
        $this->commandMap = $commandMap;
    }

    /**
     * Merges command map elements to the bottom of the current command map.
     */
    protected function mergeToBottom(array $commandMap)
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->commandMap, $commandMap);
    }

    /**
     * Removes an element from the command map.
     *
     * @param string $table
     * @param string $id
     * @param string $command (optional)
     */
    protected function remove($table, $id, $command = null)
    {
        if (is_string($command)) {
            unset($this->commandMap[$table][$id][$command]);
        } else {
            unset($this->commandMap[$table][$id]);
        }
    }

    /**
     * Callback to get the liveId of a dependent element.
     *
     * @return int
     */
    protected function getElementLiveIdCallback(ElementEntity $element)
    {
        return $element->getDataValue('liveId');
    }

    /**
     * Callback to get the real id of a dependent element.
     *
     * @return int
     */
    protected function getElementIdCallback(ElementEntity $element)
    {
        return $element->getId();
    }

    /**
     * Callback to get the specific properties of a dependent element for swapping/publishing.
     *
     * @return array
     */
    protected function getElementSwapPropertiesCallback(ElementEntity $element)
    {
        return [
            'swapWith' => $element->getId(),
        ];
    }

    /**
     * Callback to get common properties of dependent elements for clearing.
     *
     * @return array
     */
    protected function getCommonClearPropertiesCallback(ElementEntity $element)
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
     *
     * @return array
     */
    protected function getCommonSwapPropertiesCallback(ElementEntity $element)
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
     * Callback to get the specific properties of a dependent element for staging.
     *
     * @return array
     */
    protected function getElementSetStagePropertiesCallback(ElementEntity $element)
    {
        return $this->getCommonSetStagePropertiesCallback($element);
    }

    /**
     * Callback to get common properties of dependent elements for staging.
     *
     * @return array
     */
    protected function getCommonSetStagePropertiesCallback(ElementEntity $element)
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

    /**
     * Gets an instance of the dependency resolver utility.
     *
     * @param string $scope Scope identifier
     */
    protected function getDependencyUtility(string $scope): DependencyResolver
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($this->getWorkspace());
        $dependency->setOuterMostParentsRequireReferences(true);
        if ($this->getScopeData($scope, self::KEY_ElementConstructCallback)) {
            $dependency->setEventCallback(ElementEntity::EVENT_Construct, $this->getDependencyCallback($this->getScopeData($scope, self::KEY_ElementConstructCallback)));
        }
        if ($this->getScopeData($scope, self::KEY_ElementCreateChildReferenceCallback)) {
            $dependency->setEventCallback(ElementEntity::EVENT_CreateChildReference, $this->getDependencyCallback($this->getScopeData($scope, self::KEY_ElementCreateChildReferenceCallback)));
        }
        if ($this->getScopeData($scope, self::KEY_ElementCreateParentReferenceCallback)) {
            $dependency->setEventCallback(ElementEntity::EVENT_CreateParentReference, $this->getDependencyCallback($this->getScopeData($scope, self::KEY_ElementCreateParentReferenceCallback)));
        }
        return $dependency;
    }

    /**
     * Constructs the scope settings.
     * Currently the scopes for swapping/publishing and staging are available.
     */
    protected function constructScopes()
    {
        $this->scopes = [
            // settings for publishing and swapping:
            self::SCOPE_WorkspacesSwap => [
                // callback functions used to modify the commandMap
                // + element properties are specific for each element
                // + common properties are the same for all elements
                self::KEY_GetElementPropertiesCallback => 'getElementSwapPropertiesCallback',
                self::KEY_GetCommonPropertiesCallback => 'getCommonSwapPropertiesCallback',
                // callback function used, when a new element to be checked is added
                self::KEY_ElementConstructCallback => 'createNewDependentElementCallback',
                // callback function used to determine whether an element is a valid child or parent reference (e.g. IRRE)
                self::KEY_ElementCreateChildReferenceCallback => 'createNewDependentElementChildReferenceCallback',
                self::KEY_ElementCreateParentReferenceCallback => 'createNewDependentElementParentReferenceCallback',
                // callback function used to fetch the correct record uid on modifying the commandMap
                self::KEY_UpdateGetIdCallback => 'getElementLiveIdCallback',
                // setting whether to use the uid of the live record instead of the workspace record
                self::KEY_TransformDependentElementsToUseLiveId => true,
            ],
            // settings for modifying the stage:
            self::SCOPE_WorkspacesSetStage => [
                // callback functions used to modify the commandMap
                // + element properties are specific for each element
                // + common properties are the same for all elements
                self::KEY_GetElementPropertiesCallback => 'getElementSetStagePropertiesCallback',
                self::KEY_GetCommonPropertiesCallback => 'getCommonSetStagePropertiesCallback',
                // callback function used, when a new element to be checked is added
                self::KEY_ElementConstructCallback => null,
                // callback function used to determine whether an element is a valid child or parent reference (e.g. IRRE)
                self::KEY_ElementCreateChildReferenceCallback => 'createNewDependentElementChildReferenceCallback',
                self::KEY_ElementCreateParentReferenceCallback => 'createNewDependentElementParentReferenceCallback',
                // callback function used to fetch the correct record uid on modifying the commandMap
                self::KEY_UpdateGetIdCallback => 'getElementIdCallback',
                // setting whether to use the uid of the live record instead of the workspace record
                self::KEY_TransformDependentElementsToUseLiveId => false,
            ],
            // settings for clearing and flushing:
            self::SCOPE_WorkspacesClear => [
                // callback functions used to modify the commandMap
                // + element properties are specific for each element
                // + common properties are the same for all elements
                self::KEY_GetElementPropertiesCallback => null,
                self::KEY_GetCommonPropertiesCallback => 'getCommonClearPropertiesCallback',
                // callback function used, when a new element to be checked is added
                self::KEY_ElementConstructCallback => null,
                // callback function used to determine whether an element is a valid child or parent reference (e.g. IRRE)
                self::KEY_ElementCreateChildReferenceCallback => 'createClearDependentElementChildReferenceCallback',
                self::KEY_ElementCreateParentReferenceCallback => 'createClearDependentElementParentReferenceCallback',
                // callback function used to fetch the correct record uid on modifying the commandMap
                self::KEY_UpdateGetIdCallback => 'getElementIdCallback',
                // setting whether to use the uid of the live record instead of the workspace record
                self::KEY_TransformDependentElementsToUseLiveId => false,
            ],
        ];
    }

    /**
     * Gets data for a particular scope.
     *
     * @throws \RuntimeException
     * @param string $scope Scope identifier
     * @param string $key
     * @return string
     */
    protected function getScopeData($scope, $key)
    {
        if (!isset($this->scopes[$scope])) {
            throw new \RuntimeException('Scope "' . $scope . '" is not defined.', 1289342187);
        }
        return $this->scopes[$scope][$key];
    }

    /**
     * Gets a new callback to be used in the dependency resolver utility.
     *
     * @param string $method
     * @return EventCallback
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
     * Processes a local callback inside this object.
     *
     * @param string $method
     * @return mixed
     */
    protected function processCallback($method, array $callbackArguments)
    {
        return $this->$method(...$callbackArguments);
    }
}
