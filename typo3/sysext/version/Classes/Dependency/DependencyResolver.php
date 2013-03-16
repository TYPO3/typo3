<?php
namespace TYPO3\CMS\Version\Dependency;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Hader <oliver@typo3.org>
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
 * Object to handle and determine dependent references of elements.
 */
class DependencyResolver {

	/**
	 * @var \TYPO3\CMS\Version\Dependency\DependencyEntityFactory
	 */
	protected $factory;

	/**
	 * @var array
	 */
	protected $elements = array();

	/**
	 * @var array
	 */
	protected $eventCallbacks = array();

	/**
	 * @var boolean
	 */
	protected $outerMostParentsRequireReferences = FALSE;

	/**
	 * @var array
	 */
	protected $outerMostParents;

	/**
	 * Sets a callback for a particular event.
	 *
	 * @param string $eventName
	 * @param \TYPO3\CMS\Version\Dependency\EventCallback $callback
	 * @return \TYPO3\CMS\Version\Dependency\DependencyResolver
	 */
	public function setEventCallback($eventName, \TYPO3\CMS\Version\Dependency\EventCallback $callback) {
		$this->eventCallbacks[$eventName] = $callback;
		return $this;
	}

	/**
	 * Executes a registered callback (if any) for a particular event.
	 *
	 * @param string $eventName
	 * @param object $caller
	 * @param array $callerArguments
	 * @return mixed
	 */
	public function executeEventCallback($eventName, $caller, array $callerArguments = array()) {
		if (isset($this->eventCallbacks[$eventName])) {
			/** @var $callback \TYPO3\CMS\Version\Dependency\EventCallback */
			$callback = $this->eventCallbacks[$eventName];
			return $callback->execute($callerArguments, $caller, $eventName);
		}
	}

	/**
	 * Sets the condition that outermost parents required at least one child or parent reference.
	 *
	 * @param boolean $outerMostParentsRequireReferences
	 * @return \TYPO3\CMS\Version\Dependency\DependencyResolver
	 */
	public function setOuterMostParentsRequireReferences($outerMostParentsRequireReferences) {
		$this->outerMostParentsRequireReferences = (bool) $outerMostParentsRequireReferences;
		return $this;
	}

	/**
	 * Adds an element to be checked for dependent references.
	 *
	 * @param string $table
	 * @param integer $id
	 * @param array $data
	 * @return \TYPO3\CMS\Version\Dependency\ElementEntity
	 */
	public function addElement($table, $id, array $data = array()) {
		$element = $this->getFactory()->getElement($table, $id, $data, $this);
		$elementName = $element->__toString();
		$this->elements[$elementName] = $element;
		return $element;
	}

	/**
	 * Gets the outermost parents that define complete dependent structure each.
	 *
	 * @return array
	 */
	public function getOuterMostParents() {
		if (!isset($this->outerMostParents)) {
			$this->outerMostParents = array();
			/** @var $element \TYPO3\CMS\Version\Dependency\ElementEntity */
			foreach ($this->elements as $element) {
				$this->processOuterMostParent($element);
			}
		}
		return $this->outerMostParents;
	}

	/**
	 * Processes and registers the outermost parents accordant to the registered elements.
	 *
	 * @param \TYPO3\CMS\Version\Dependency\ElementEntity $element
	 * @return void
	 */
	protected function processOuterMostParent(\TYPO3\CMS\Version\Dependency\ElementEntity $element) {
		if ($this->outerMostParentsRequireReferences === FALSE || $element->hasReferences()) {
			$outerMostParent = $element->getOuterMostParent();
			if ($outerMostParent !== FALSE) {
				$outerMostParentName = $outerMostParent->__toString();
				if (!isset($this->outerMostParents[$outerMostParentName])) {
					$this->outerMostParents[$outerMostParentName] = $outerMostParent;
				}
			}
		}
	}

	/**
	 * Gets all nested elements (including the parent) of a particular outermost parent element.
	 *
	 * @throws RuntimeException
	 * @param \TYPO3\CMS\Version\Dependency\ElementEntity $outerMostParent
	 * @return array
	 */
	public function getNestedElements(\TYPO3\CMS\Version\Dependency\ElementEntity $outerMostParent) {
		$outerMostParentName = $outerMostParent->__toString();
		if (!isset($this->outerMostParents[$outerMostParentName])) {
			throw new \RuntimeException('Element "' . $outerMostParentName . '" was detected as outermost parent.', 1289318609);
		}
		$nestedStructure = array_merge(array($outerMostParentName => $outerMostParent), $outerMostParent->getNestedChildren());
		return $nestedStructure;
	}

	/**
	 * Gets the registered elements.
	 *
	 * @return array
	 */
	public function getElements() {
		return $this->elements;
	}

	/**
	 * Gets an instance of the factory to keep track of element or reference entities.
	 *
	 * @return \TYPO3\CMS\Version\Dependency\DependencyEntityFactory
	 */
	public function getFactory() {
		if (!isset($this->factory)) {
			$this->factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Dependency\\DependencyEntityFactory');
		}
		return $this->factory;
	}

}


?>