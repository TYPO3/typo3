<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Oliver Hader <oliver@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Handles the t3lib_TCEmain command map and is only used in combination with t3lib_TCEmain.
 */
class tx_version_tcemain_CommandMap {
	const SCOPE_WorkspacesSwap = 'SCOPE_WorkspacesSwap';
	const SCOPE_WorkspacesSetStage = 'SCOPE_WorkspacesSetStage';

	const KEY_ScopeErrorMessage = 'KEY_ScopeErrorMessage';
	const KEY_ScopeErrorCode = 'KEY_ScopeErrorCode';
	const KEY_GetElementPropertiesCallback = 'KEY_GetElementPropertiesCallback';
	const KEY_GetCommonPropertiesCallback = 'KEY_GetCommonPropertiesCallback';
	const KEY_ElementConstructCallback = 'KEY_EventConstructCallback';
	const KEY_ElementCreateChildReferenceCallback = 'KEY_ElementCreateChildReferenceCallback';
	const KEY_ElementCreateParentReferenceCallback = 'KEY_ElementCreateParentReferenceCallback';
	const KEY_PurgeWithErrorMessageGetIdCallback = 'KEY_PurgeWithErrorMessageGetIdCallback';
	const KEY_UpdateGetIdCallback = 'KEY_UpdateGetIdCallback';
	const KEY_TransformDependentElementsToUseLiveId = 'KEY_TransformDependentElementsToUseLiveId';

	/**
	 * @var tx_version_tcemain
	 */
	protected $parent;

	/**
	 * @var t3lib_TCEmain
	 */
	protected $tceMain;

	/**
	 * @var array
	 */
	protected $commandMap = array();

	/**
	 * @var string
	 */
	protected $workspacesSwapMode;

	/**
	 * @var string
	 */
	protected $workspacesChangeStageMode;

	/**
	 * @var boolean
	 */
	protected $workspacesConsiderReferences;

	/**
	 * @var array
	 */
	protected $scopes;

	/**
	 * Creates this object.
	 *
	 * @param t3lib_TCEmain $parent
	 * @param array $commandMap
	 */
	public function __construct(tx_version_tcemain $parent, t3lib_TCEmain $tceMain, array $commandMap) {
		$this->setParent($parent);
		$this->setTceMain($tceMain);
		$this->set($commandMap);

		$this->setWorkspacesSwapMode($this->getTceMain()->BE_USER->getTSConfigVal('options.workspaces.swapMode'));
		$this->setWorkspacesChangeStageMode($this->getTceMain()->BE_USER->getTSConfigVal('options.workspaces.changeStageMode'));
		$this->setWorkspacesConsiderReferences($this->getTceMain()->BE_USER->getTSConfigVal('options.workspaces.considerReferences'));

		$this->constructScopes();
	}

	/**
	 * Gets the command map.
	 *
	 * @return array
	 */
	public function get() {
		return $this->commandMap;
	}

	/**
	 * Sets the command map.
	 *
	 * @param array $commandMap
	 * @return tx_version_tcemain_CommandMap
	 */
	public function set(array $commandMap) {
		$this->commandMap = $commandMap;
		return $this;
	}

	/**
	 * Gets the parent object.
	 *
	 * @return tx_version_tcemain
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Sets the parent object.
	 *
	 * @param tx_version_tcemain $parent
	 * @return tx_version_tcemain_CommandMap
	 */
	public function setParent(tx_version_tcemain $parent) {
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Gets the parent object.
	 *
	 * @return t3lib_TCEmain
	 */
	public function getTceMain() {
		return $this->tceMain;
	}

	/**
	 * Sets the parent object.
	 *
	 * @param t3lib_TCEmain $parent
	 * @return tx_version_tcemain_CommandMap
	 */
	public function setTceMain(t3lib_TCEmain $tceMain) {
		$this->tceMain = $tceMain;
		return $this;
	}

	/**
	 * Sets the workspaces swap mode
	 * (see options.workspaces.swapMode).
	 *
	 * @param string $workspacesSwapMode
	 * @return tx_version_tcemain_CommandMap
	 */
	public function setWorkspacesSwapMode($workspacesSwapMode) {
		$this->workspacesSwapMode = (string)$workspacesSwapMode;
		return $this;
	}

	/**
	 * Sets the workspaces change stage mode
	 * see options.workspaces.changeStageMode)
	 *
	 * @param string $workspacesChangeStageMode
	 * @return tx_version_tcemain_CommandMap
	 */
	public function setWorkspacesChangeStageMode($workspacesChangeStageMode) {
		$this->workspacesChangeStageMode = (string)$workspacesChangeStageMode;
		return $this;
	}

	/**
	 * Sets the workspace behaviour to automatically consider references
	 * (see options.workspaces.considerReferences)
	 *
	 * @param boolean $workspacesConsiderReferences
	 * @return tx_version_tcemain_CommandMap
	 */
	public function setWorkspacesConsiderReferences($workspacesConsiderReferences) {
		$this->workspacesConsiderReferences = (bool)$workspacesConsiderReferences;
		return $this;
	}

	/**
	 * Processes the command map.
	 *
	 * @return tx_version_tcemain_CommandMap
	 */
	public function process() {
		$this->resolveWorkspacesSwapDependencies();
		$this->resolveWorkspacesSetStageDependencies();
		return $this;
	}

	/**
	 * Resolves workspaces related dependencies for swapping/publishing of the command map.
	 * Workspaces records that have children or (relative) parents which are versionized
	 * but not published with this request, are removed from the command map. Otherwise
	 * this would produce hanging record sets and lost references.
	 *
	 * @return void
	 */
	protected function resolveWorkspacesSwapDependencies() {
		$scope = self::SCOPE_WorkspacesSwap;
		$dependency = $this->getDependencyUtility($scope);

		foreach ($this->commandMap as $table => $liveIdCollection) {
			foreach ($liveIdCollection as $liveId => $commandCollection) {
				foreach ($commandCollection as $command => $properties) {
					if ($command === 'version' && isset($properties['action']) && $properties['action'] === 'swap') {
						if (isset($properties['swapWith']) && t3lib_div::testInt($properties['swapWith'])) {
							$this->addWorkspacesSwapElements($dependency, $table, $liveId, $properties);
						}
					}
				}
			}
		}

		$this->applyWorkspacesDependencies($dependency, $scope);
	}

	/**
	 * Adds workspaces elements for swapping/publishing and takes care of the swapMode.
	 *
	 * @param t3lib_utility_Dependency $dependency
	 * @param string $table
	 * @param iteger $liveId
	 * @param array $properties
	 * @return void
	 */
	protected function addWorkspacesSwapElements(t3lib_utility_Dependency $dependency, $table, $liveId, array $properties) {
		$elementList = array();

		// Fetch accordant elements if the swapMode is 'any' or 'pages':
		if ($this->workspacesSwapMode === 'any' || $this->workspacesSwapMode === 'pages' && $table === 'pages') {
			$elementList = $this->getParent()->findPageElementsForVersionSwap($table, $liveId, $properties['swapWith']);
		}

		foreach ($elementList as $elementTable => $elementIdArray) {
			foreach ($elementIdArray as $elementIds) {
				$dependency->addElement(
					$elementTable, $elementIds[1],
					array('liveId' => $elementIds[0], 'properties' => array_merge($properties, array('swapWith' => $elementIds[1])))
				);
			}
		}

		if (count($elementList) === 0) {
			$dependency->addElement(
				$table, $properties['swapWith'], array('liveId' => $liveId, 'properties' => $properties)
			);
		}
	}

	/**
	 * Resolves workspaces related dependencies for staging of the command map.
	 * Workspaces records that have children or (relative) parents which are versionized
	 * but not staged with this request, are removed from the command map.
	 *
	 * @return void
	 */
	protected function resolveWorkspacesSetStageDependencies() {
		$scope = self::SCOPE_WorkspacesSetStage;
		$dependency = $this->getDependencyUtility($scope);

		foreach ($this->commandMap as $table => $liveIdCollection) {
			foreach ($liveIdCollection as $liveIdList => $commandCollection) {
				foreach ($commandCollection as $command => $properties) {
					if ($command === 'version' && isset($properties['action']) && $properties['action'] === 'setStage') {
						if (isset($properties['stageId']) && t3lib_div::testInt($properties['stageId'])) {
							$this->addWorkspacesSetStageElements($dependency, $table, $liveIdList, $properties);
							$this->explodeSetStage($table, $liveIdList, $properties);
						}
					}
				}
			}
		}

		$this->applyWorkspacesDependencies($dependency, $scope);
	}

	/**
	 * Adds workspaces elements for staging and takes care of the changeStageMode.
	 *
	 * @param t3lib_utility_Dependency $dependency
	 * @param string $table
	 * @param string $liveIdList
	 * @param array $properties
	 * @return void
	 */
	protected function addWorkspacesSetStageElements(t3lib_utility_Dependency $dependency, $table, $liveIdList, array $properties) {
		$liveIds = t3lib_div::trimExplode(',', $liveIdList, TRUE);
		$elementList = array($table => $liveIds);

		if (t3lib_div::inList('any,pages', $this->workspacesChangeStageMode)) {
			if (count($liveIds) === 1) {
				$workspaceRecord = t3lib_BEfunc::getRecord($table, $liveIds[0], 't3ver_wsid');
				$workspaceId = $workspaceRecord['t3ver_wsid'];
			} else {
				$workspaceId = $this->tceMain()->BE_USER->workspace;
			}

			if ($table === 'pages') {
				// Find all elements from the same ws to change stage
				$this->getParent()->findRealPageIds($liveIds);
				$this->getParent()->findPageElementsForVersionStageChange($liveIds, $workspaceId, $elementList);
			} elseif ($this->workspacesChangeStageMode === 'any') {
				// Find page to change stage:
				$pageIdList = array();
				$this->getParent()->findPageIdsForVersionStateChange($table, $liveIds, $workspaceId, $pageIdList, $elementList);
				// Find other elements from the same ws to change stage:
				$this->getParent()->findPageElementsForVersionStageChange($pageIdList, $workspaceId, $elementList);
			}
		}

		foreach ($elementList as $elementTable => $elementIds) {
			foreach($elementIds as $elementId) {
				$dependency->addElement(
					$elementTable, $elementId,
					array('properties' => $properties)
				);
			}
		}
	}

	/**
	 * Explodes id-lists in the command map for staging actions.
	 *
	 * @throws RuntimeException
	 * @param string $table
	 * @param string $liveIdList
	 * @param array $properties
	 * @return void
	 */
	protected function explodeSetStage($table, $liveIdList, array $properties) {
		$extractedCommandMap = array();
		$liveIds = t3lib_div::trimExplode(',', $liveIdList, TRUE);

		if (count($liveIds) > 1) {
			foreach ($liveIds as $liveId) {
				if (isset($this->commandMap[$table][$liveId]['version'])) {
					throw new RuntimeException('Command map for [' . $table . '][' . $liveId . '][version] was already set.', 1289391048);
				}

				$extractedCommandMap[$table][$liveId]['version'] = $properties;
			}

			$this->remove($table, $liveIdList, 'version');
			$this->mergeToBottom($extractedCommandMap);
		}
	}

	/**
	 * Applies the workspaces dependencies and removes incomplete structures or automatically
	 * completes them, depending on the options.workspaces.considerReferences setting
	 *
	 * @param t3lib_utility_Dependency $dependency
	 * @param string $scope
	 * @return void
	 */
	protected function applyWorkspacesDependencies(t3lib_utility_Dependency $dependency, $scope) {
		$transformDependentElementsToUseLiveId = $this->getScopeData($scope, self::KEY_TransformDependentElementsToUseLiveId);

		$elementsToBeVersionized = $dependency->getElements();
		if ($transformDependentElementsToUseLiveId) {
			$elementsToBeVersionized = $this->transformDependentElementsToUseLiveId($elementsToBeVersionized);
		}

		$outerMostParents = $dependency->getOuterMostParents();
		/** @var $outerMostParent t3lib_utility_Dependency_Element */
		foreach ($outerMostParents as $outerMostParent) {
			$dependentElements = $dependency->getNestedElements($outerMostParent);
			if ($transformDependentElementsToUseLiveId) {
				$dependentElements = $this->transformDependentElementsToUseLiveId($dependentElements);
			}

			$intersectingElements = array_intersect_key($dependentElements, $elementsToBeVersionized);

			if (count($intersectingElements) > 0) {
				// If at least one element intersects but not all, throw away all elements of the depdendent structure:
				if (count($intersectingElements) !== count($dependentElements) && $this->workspacesConsiderReferences === FALSE) {
					$this->purgeWithErrorMessage($intersectingElements, $scope);
				// If everything is fine or references shall be considered automatically:
				} else {
					$this->update(current($intersectingElements), $dependentElements, $scope);
				}
			}
		}
	}

	/**
	 * Purges incomplete structures from the command map and triggers an error message.
	 *
	 * @param array $elements
	 * @param string $scope
	 * @return void
	 */
	protected function purgeWithErrorMessage(array $elements, $scope) {
		/** @var $dependentElement t3lib_utility_Dependency_Element */
		foreach ($elements as $element) {
			$table = $element->getTable();
			$id = $this->processCallback(
				$this->getScopeData($scope, self::KEY_PurgeWithErrorMessageGetIdCallback),
				array($element)
			);

			$this->remove($table, $id, 'version');
			$this->getTceMain()->log(
				$table, $id,
				5, 0, 1,
				$this->getScopeData($scope, self::KEY_ScopeErrorMessage),
				$this->getScopeData($scope, self::KEY_ScopeErrorCode),
				array(
					t3lib_BEfunc::getRecordTitle($table, t3lib_BEfunc::getRecord($table, $id)),
					$table, $id
				)
			);
		}
	}

	/**
	 * Updates the command map accordant to valid structures and takes care of the correct order.
	 *
	 * @param t3lib_utility_Dependency_Element $intersectingElement
	 * @param array $elements
	 * @param string $scope
	 * @return void
	 */
	protected function update(t3lib_utility_Dependency_Element $intersectingElement, array $elements, $scope) {
		$orderedCommandMap = array();

		$commonProperties = $this->processCallback(
			$this->getScopeData($scope, self::KEY_GetCommonPropertiesCallback),
			array($intersectingElement)
		);

		/** @var $dependentElement t3lib_utility_Dependency_Element */
		foreach ($elements as $element) {
			$table = $element->getTable();
			$id = $this->processCallback(
				$this->getScopeData($scope, self::KEY_UpdateGetIdCallback),
				array($element)
			);

			$this->remove($table, $id, 'version');
			$orderedCommandMap[$table][$id]['version'] = array_merge(
				$commonProperties,
				$this->processCallback(
					$this->getScopeData($scope, self::KEY_GetElementPropertiesCallback),
					array($element)
				)
			);
		}

		// Ensure that ordered command map is on top of the command map:
		$this->mergeToTop($orderedCommandMap);
	}

	/**
	 * Merges command map elements to the top of the current command map..
	 *
	 * @param array $commandMap
	 * @return void
	 */
	protected function mergeToTop(array $commandMap) {
		$this->commandMap = t3lib_div::array_merge_recursive_overrule($commandMap, $this->commandMap);
	}

	/**
	 * Merges command map elements to the bottom of the current command map.
	 *
	 * @param array $commandMap
	 * @return void
	 */
	protected function mergeToBottom(array $commandMap) {
		$this->commandMap = t3lib_div::array_merge_recursive_overrule($this->commandMap, $commandMap);
	}

	/**
	 * Removes an element from the command map.
	 *
	 * @param string $table
	 * @param string $id
	 * @param string $command (optional)
	 * @return void
	 */
	protected function remove($table, $id, $command = NULL) {
		if (is_string($command)) {
			unset($this->commandMap[$table][$id][$command]);
		} else {
			unset($this->commandMap[$table][$id]);
		}
	}

	/**
	 * Callback to get the liveId of an dependent element.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @return integer
	 */
	protected function getElementLiveIdCallback(t3lib_utility_Dependency_Element $element) {
		return $element->getDataValue('liveId');
	}

	/**
	 * Callback to get the real id of an dependent element.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @return integer
	 */
	protected function getElementIdCallback(t3lib_utility_Dependency_Element $element) {
		return $element->getId();
	}

	/**
	 * Callback to get the specific properties of a dependent element for swapping/publishing.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @return array
	 */
	protected function getElementSwapPropertiesCallback(t3lib_utility_Dependency_Element $element) {
		return array(
			'swapWith' => $element->getId(),
		);
	}

	/**
	 * Callback to get common properties of dependent elements for swapping/publishing.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @return array
	 */
	protected function getCommonSwapPropertiesCallback(t3lib_utility_Dependency_Element $element) {
		$commonSwapProperties = array();

		$elementProperties = $element->getDataValue('properties');
		if (isset($elementProperties['action'])) {
			$commonSwapProperties['action'] = $elementProperties['action'];
		}
		if (isset($elementProperties['swapIntoWS'])) {
			$commonSwapProperties['swapIntoWS'] = $elementProperties['swapIntoWS'];
		}

		return $commonSwapProperties;
	}

	/**
	 * Callback to get the specific properties of a dependent element for staging.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @return array
	 */
	protected function getElementSetStagePropertiesCallback(t3lib_utility_Dependency_Element $element) {
		return $this->getCommonSetStagePropertiesCallback($element);
	}

	/**
	 * Callback to get common properties of dependent elements for staging.
	 *
	 * @param t3lib_utility_Dependency_Element $element
	 * @return array
	 */
	protected function getCommonSetStagePropertiesCallback(t3lib_utility_Dependency_Element $element) {
		$commonSetStageProperties = array();

		$elementProperties = $element->getDataValue('properties');
		if (isset($elementProperties['stageId'])) {
			$commonSetStageProperties['stageId'] = $elementProperties['stageId'];
		}
		if (isset($elementProperties['comment'])) {
			$commonSetStageProperties['comment'] = $elementProperties['comment'];
		}

		return $commonSetStageProperties;
	}


	/**
	 * Gets an instance of the depency resolver utility.
	 *
	 * @return t3lib_utility_Dependency
	 */
	protected function getDependencyUtility($scope) {

		/** @var $dependency t3lib_utility_Dependency */
		$dependency = t3lib_div::makeInstance('t3lib_utility_Dependency');
		$dependency->setOuterMostParentsRequireReferences(TRUE);

		if ($this->getScopeData($scope, self::KEY_ElementConstructCallback)) {
			$dependency->setEventCallback(
				t3lib_utility_Dependency_Element::EVENT_Construct,
				$this->getDependencyCallback($this->getScopeData($scope, self::KEY_ElementConstructCallback))
			);
		}
		if ($this->getScopeData($scope, self::KEY_ElementCreateChildReferenceCallback)) {
			$dependency->setEventCallback(
				t3lib_utility_Dependency_Element::EVENT_CreateChildReference,
				$this->getDependencyCallback($this->getScopeData($scope, self::KEY_ElementCreateChildReferenceCallback))
			);
		}
		if ($this->getScopeData($scope, self::KEY_ElementCreateParentReferenceCallback)) {
			$dependency->setEventCallback(
				t3lib_utility_Dependency_Element::EVENT_CreateParentReference,
				$this->getDependencyCallback($this->getScopeData($scope, self::KEY_ElementCreateParentReferenceCallback))
			);
		}

		return $dependency;
	}

	/**
	 * Callback to determine whether a new child reference shall be considered in the dependency resolver utility.
	 *
	 * @param array $callerArguments
	 * @param array $targetArgument
	 * @param t3lib_utility_Dependency_Element $caller
	 * @param string $eventName
	 * @return string Skip response (if required)
	 */
	public function createNewDependentElementChildReferenceCallback(array $callerArguments, array $targetArgument, t3lib_utility_Dependency_Element $caller, $eventName) {
		/** @var $reference t3lib_utility_Dependency_Reference */
		$reference = $callerArguments['reference'];

		$fieldCOnfiguration = t3lib_BEfunc::getTcaFieldConfiguration($caller->getTable(), $reference->getField());

		if (!$fieldCOnfiguration || !t3lib_div::inList('field,list', $this->getTceMain()->getInlineFieldType($fieldCOnfiguration))) {
			return t3lib_utility_Dependency_Element::RESPONSE_Skip;
		}
	}

	/**
	 * Callback to determine whether a new parent reference shall be considered in the dependency resolver utility.
	 *
	 * @param array $callerArguments
	 * @param array $targetArgument
	 * @param t3lib_utility_Dependency_Element $caller
	 * @param string $eventName
	 * @return string Skip response (if required)
	 */
	public function createNewDependentElementParentReferenceCallback(array $callerArguments, array $targetArgument, t3lib_utility_Dependency_Element $caller, $eventName) {
		/** @var $reference t3lib_utility_Dependency_Reference */
		$reference = $callerArguments['reference'];

		$fieldCOnfiguration = t3lib_BEfunc::getTcaFieldConfiguration($reference->getElement()->getTable(), $reference->getField());

		if (!$fieldCOnfiguration || !t3lib_div::inList('field,list', $this->getTceMain()->getInlineFieldType($fieldCOnfiguration))) {
			return t3lib_utility_Dependency_Element::RESPONSE_Skip;
		}
	}

	/**
	 * Callback to add additional data to new elements created in the dependency resolver utility.
	 *
	 * @param t3lib_utility_Dependency_Element $caller
	 * @param array $callerArguments
	 * @param array $targetArgument
	 * @return void
	 */
	public function createNewDependentElementCallback(array $callerArguments, array $targetArgument, t3lib_utility_Dependency_Element $caller) {
		if ($caller->hasDataValue('liveId') === FALSE) {
			$liveId = t3lib_BEfunc::getLiveVersionIdOfRecord($caller->getTable(), $caller->getId());
			if (is_null($liveId) === FALSE) {
				$caller->setDataValue('liveId', $liveId);
			}
		}
	}

	/**
	 * Transforms dependent elements to use the liveId as array key.
	 *
	 * @param array $elements Depedent elements, each of type t3lib_utility_Dependency_Element
	 * @return array
	 */
	protected function transformDependentElementsToUseLiveId(array $elements) {
		$transformedElements = array();

		/** @var $element t3lib_utility_Dependency_Element */
		foreach ($elements as $element) {
			$elementName = t3lib_utility_Dependency_Element::getIdentifier(
				$element->getTable(), $element->getDataValue('liveId')
			);
			$transformedElements[$elementName] = $element;
		}

		return $transformedElements;
	}

	/**
	 * Constructs the scope settings.
	 * Currently the scopes for swapping/publishing and staging are available.
	 *
	 * @return void
	 */
	protected function constructScopes() {
		$this->scopes = array(
			self::SCOPE_WorkspacesSwap => array(
				self::KEY_ScopeErrorMessage => 'Record "%s" (%s:%s) cannot be swapped or published independently, because it is related to other new or modified records.',
				self::KEY_ScopeErrorCode => 1288283630,
				self::KEY_GetElementPropertiesCallback => 'getElementSwapPropertiesCallback',
				self::KEY_GetCommonPropertiesCallback => 'getCommonSwapPropertiesCallback',
				self::KEY_ElementConstructCallback => 'createNewDependentElementCallback',
				self::KEY_ElementCreateChildReferenceCallback => 'createNewDependentElementChildReferenceCallback',
				self::KEY_ElementCreateParentReferenceCallback => 'createNewDependentElementParentReferenceCallback',
				self::KEY_PurgeWithErrorMessageGetIdCallback => 'getElementLiveIdCallback',
				self::KEY_UpdateGetIdCallback => 'getElementLiveIdCallback',
				self::KEY_TransformDependentElementsToUseLiveId => TRUE,
			),
			self::SCOPE_WorkspacesSetStage => array(
				self::KEY_ScopeErrorMessage => 'Record "%s" (%s:%s) cannot be sent to another stage independently, because it is related to other new or modified records.',
				self::KEY_ScopeErrorCode => 1289342524,
				self::KEY_GetElementPropertiesCallback => 'getElementSetStagePropertiesCallback',
				self::KEY_GetCommonPropertiesCallback => 'getCommonSetStagePropertiesCallback',
				self::KEY_ElementConstructCallback => NULL,
				self::KEY_ElementCreateChildReferenceCallback => 'createNewDependentElementChildReferenceCallback',
				self::KEY_ElementCreateParentReferenceCallback => 'createNewDependentElementParentReferenceCallback',
				self::KEY_PurgeWithErrorMessageGetIdCallback => 'getElementIdCallback',
				self::KEY_UpdateGetIdCallback => 'getElementIdCallback',
				self::KEY_TransformDependentElementsToUseLiveId => FALSE,
			),
		);
	}

	/**
	 * Gets data for a particular scope.
	 *
	 * @throws RuntimeException
	 * @param string $scope
	 * @param string $key
	 * @return string
	 */
	protected function getScopeData($scope, $key) {
		if (!isset($this->scopes[$scope])) {
			throw new RuntimeException('Scope "' . $scope . '" is not defined.', 1289342187);
		}

		return $this->scopes[$scope][$key];
	}

	/**
	 * Gets a new callback to be used in the dependency resolver utility.
	 *
	 * @param string $callbackMethod
	 * @param array $targetArguments
	 * @return t3lib_utility_Dependency_Callback
	 */
	protected function getDependencyCallback($method, array $targetArguments = array()) {
		return t3lib_div::makeInstance('t3lib_utility_Dependency_Callback', $this, $method, $targetArguments);
	}

	/**
	 * Processes a local callback inside this object.
	 *
	 * @param string $method
	 * @param array $callbackArguments
	 * @return mixed
	 */
	protected function processCallback($method, array $callbackArguments) {
		return call_user_func_array(array($this, $method), $callbackArguments);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/class.tx_version_tcemain_commandmap.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/class.tx_version_tcemain_commandmap.php']);
}
?>