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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;

/**
 * Handles the \TYPO3\CMS\Core\DataHandling\DataHandler command map and is
 * only used in combination with \TYPO3\CMS\Core\DataHandling\DataHandler
 *
 * @internal
 */
readonly class CommandMap
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
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
     * Resolves workspaces related dependencies for publishing of the command map.
     * Workspaces records that have children or (relative) parents which are versionized
     * but not published with this request, are removed from the command map. Otherwise
     * this would produce hanging record sets and lost references.
     *
     * Dependencies are resolved on versioned record uids: ElementEntity, DependencyResolver
     * and the sys_refindex lookups in between all address a record by the uid of its
     * workspace version, and the live uid is derived data (ElementEntity data key "liveId").
     * The "publish" command is keyed by that very versioned uid, the legacy "version"
     * command is keyed by the live uid and carries the versioned uid in "swapWith" - so
     * both end up as the same element and only the command map key differs.
     */
    protected function resolveWorkspacesPublishDependencies(array $commandMap, int $workspace): array
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($workspace);
        $dependency->setEventDispatcher($this->eventDispatcher);
        $dependency->setAction(DependencyCollectionAction::Publish);
        foreach ($commandMap as $table => $idCollection) {
            foreach ($idCollection as $id => $commandCollection) {
                foreach ($commandCollection as $command => $properties) {
                    if ($command === 'publish') {
                        $dependency->addElement($table, (int)$id, ['properties' => $properties]);
                        continue;
                    }
                    // @todo: Can be removed in TYPO3 v16.0 once the testing framework
                    //        does not use 'version' with 'swap' / 'publish' anymore.
                    if ($command === 'version' && in_array($properties['action'] ?? '', ['publish', 'swap'], true)) {
                        if (isset($properties['swapWith']) && MathUtility::canBeInterpretedAsInteger($properties['swapWith'])) {
                            $dependency->addElement($table, (int)$properties['swapWith'], ['liveId' => $id, 'properties' => $properties]);
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
                $commonProperties = $this->getCommonPublishProperties($intersectingElement);
                /** @var ElementEntity $element */
                foreach ($dependentElements as $element) {
                    $table = $element->getTable();
                    $versionId = $element->getId();
                    unset($commandMap[$table][$versionId]['publish']);
                    // Drop a legacy 'version' command for the same element, which sits
                    // below the live uid instead.
                    unset($commandMap[$table][$element->getDataValue('liveId')]['version']);
                    if ($element->isInvalid()) {
                        continue;
                    }
                    $orderedCommandMap[$table][$versionId]['publish'] = $commonProperties;
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
     *
     * The "change_stage" command and the legacy "version" command with action "setStage"
     * are both keyed by the versioned uid. Every element keeps the command it was requested
     * with, and dependent elements pulled in by the resolver take the command of the element
     * that was requested, so processCmdmap hooks always see the shape the caller sent.
     */
    protected function resolveWorkspacesSetStageDependencies(array $commandMap, int $workspace): array
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($workspace);
        $dependency->setEventDispatcher($this->eventDispatcher);
        $dependency->setAction(DependencyCollectionAction::StageChange);
        foreach ($commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionId => $commandCollection) {
                $hasChangeStage = array_key_exists('change_stage', $commandCollection);
                if ($hasChangeStage && ($commandCollection['version']['action'] ?? null) === 'setStage') {
                    // Both commands address the same record, which must change its stage only once.
                    // "change_stage" wins even if invalid, it is then rejected by the hook.
                    unset($commandMap[$table][$versionId]['version']);
                }
                if ($this->isChangeStageCommand($commandCollection['change_stage'] ?? null)) {
                    $dependency->addElement($table, (int)$versionId, [
                        'command' => 'change_stage',
                        'properties' => $commandCollection['change_stage'],
                    ]);
                } elseif (!$hasChangeStage && $this->isLegacySetStageCommand($commandCollection['version'] ?? null)) {
                    $properties = $commandCollection['version'];
                    $properties['stage'] = $properties['stageId'];
                    $dependency->addElement($table, (int)$versionId, [
                        'command' => 'version',
                        'properties' => $properties,
                    ]);
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
                $commonProperties = $this->getCommonStageProperties($intersectingElement);
                /** @var ElementEntity $element */
                foreach ($dependentElements as $element) {
                    $table = $element->getTable();
                    $versionId = $element->getId();
                    unset($commandMap[$table][$versionId]['change_stage']);
                    if ($this->isLegacySetStageCommand($commandMap[$table][$versionId]['version'] ?? null)) {
                        unset($commandMap[$table][$versionId]['version']);
                    }
                    if ($element->isInvalid()) {
                        continue;
                    }
                    $properties = array_merge($commonProperties, $this->getCommonStageProperties($element));
                    if (($element->getDataValue('command') ?? $intersectingElement->getDataValue('command')) === 'version') {
                        $orderedCommandMap[$table][$versionId]['version'] = $this->toLegacySetStageProperties($properties);
                    } else {
                        $orderedCommandMap[$table][$versionId]['change_stage'] = $properties;
                    }
                }
                // Ensure that ordered command map is on top of the command map:
                ArrayUtility::mergeRecursiveWithOverrule($orderedCommandMap, $commandMap);
                $commandMap = $orderedCommandMap;
            }
        }
        return $commandMap;
    }

    /**
     * Resolves workspaces related dependencies for discarding of the command map.
     * Workspaces records that are children or (relative) parents of a discarded record
     * and are delete placeholders, but not discarded with this request, are added to the
     * command map.
     *
     * Every element keeps the command it was requested with, and dependent elements pulled
     * in by the resolver take the command of the element that was requested, so the native
     * "discard" command is never turned into an empty "version" command.
     */
    protected function resolveWorkspacesDiscardDependencies(array $commandMap, int $workspace): array
    {
        $dependency = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependency->setWorkspace($workspace);
        $dependency->setEventDispatcher($this->eventDispatcher);
        $dependency->setAction(DependencyCollectionAction::Discard);
        foreach ($commandMap as $table => $versionIdCollection) {
            foreach ($versionIdCollection as $versionId => $commandCollection) {
                if (is_array($commandCollection) && array_key_exists('discard', $commandCollection)) {
                    if ($this->isLegacyDiscardCommand($commandCollection['version'] ?? null)) {
                        // Both commands address the same record, which must be discarded only once
                        unset($commandMap[$table][$versionId]['version']);
                    }
                    $dependency->addElement($table, (int)$versionId, [
                        'command' => 'discard',
                        'properties' => $commandCollection['discard'],
                    ]);
                } elseif ($this->isLegacyDiscardCommand($commandCollection['version'] ?? null)) {
                    // @todo: can be removed once testing framework is not using 'clearWSID' and 'flush' anymore
                    $dependency->addElement($table, (int)$versionId, [
                        'command' => 'version',
                        'properties' => $commandCollection['version'],
                    ]);
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
                /** @var ElementEntity $element */
                foreach ($dependentElements as $element) {
                    $table = $element->getTable();
                    $id = $element->getId();
                    unset($commandMap[$table][$id]['discard']);
                    if ($this->isLegacyDiscardCommand($commandMap[$table][$id]['version'] ?? null)) {
                        unset($commandMap[$table][$id]['version']);
                    }
                    if ($element->isInvalid()) {
                        continue;
                    }
                    $requestedElement = $element->hasDataValue('command') ? $element : $intersectingElement;
                    if ($requestedElement->getDataValue('command') === 'version') {
                        $orderedCommandMap[$table][$id]['version'] = $this->getCommonClearProperties($requestedElement);
                    } else {
                        $orderedCommandMap[$table][$id]['discard'] = $requestedElement->getDataValue('properties');
                    }
                }
                // Ensure that ordered command map is on top of the command map:
                ArrayUtility::mergeRecursiveWithOverrule($orderedCommandMap, $commandMap);
                $commandMap = $orderedCommandMap;
            }
        }
        return $commandMap;
    }

    protected function isLegacyDiscardCommand(mixed $properties): bool
    {
        return is_array($properties) && in_array($properties['action'] ?? null, ['clearWSID', 'flush'], true);
    }

    /**
     * Gets common properties of dependent elements for clearing.
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
     * Gets common properties of dependent elements for publishing.
     */
    protected function getCommonPublishProperties(ElementEntity $element): array
    {
        $commonPublishProperties = [];
        $elementProperties = $element->getDataValue('properties');
        if (isset($elementProperties['comment'])) {
            $commonPublishProperties['comment'] = $elementProperties['comment'];
        }
        if (isset($elementProperties['notificationAlternativeRecipients'])) {
            $commonPublishProperties['notificationAlternativeRecipients'] = $elementProperties['notificationAlternativeRecipients'];
        }
        return $commonPublishProperties;
    }

    protected function isChangeStageCommand(mixed $properties): bool
    {
        return is_array($properties) && MathUtility::canBeInterpretedAsInteger($properties['stage'] ?? null);
    }

    protected function isLegacySetStageCommand(mixed $properties): bool
    {
        return is_array($properties)
            && ($properties['action'] ?? null) === 'setStage'
            && MathUtility::canBeInterpretedAsInteger($properties['stageId'] ?? null);
    }

    /**
     * Gets common properties of dependent elements for staging, in the shape of
     * the "change_stage" command.
     */
    protected function getCommonStageProperties(ElementEntity $element): array
    {
        $elementProperties = $element->getDataValue('properties') ?? [];
        return array_intersect_key($elementProperties, array_flip(['stage', 'comment', 'notificationAlternativeRecipients']));
    }

    protected function toLegacySetStageProperties(array $properties): array
    {
        $legacyProperties = ['action' => 'setStage', 'stageId' => $properties['stage']];
        unset($properties['stage']);
        return array_merge($legacyProperties, $properties);
    }
}
