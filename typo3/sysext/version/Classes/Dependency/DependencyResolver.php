<?php
namespace TYPO3\CMS\Version\Dependency;

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
 * Object to handle and determine dependent references of elements.
 */
class DependencyResolver
{
    /**
     * @var int
     */
    protected $workspace = 0;

    /**
     * @var \TYPO3\CMS\Version\Dependency\DependencyEntityFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @var array
     */
    protected $eventCallbacks = [];

    /**
     * @var bool
     */
    protected $outerMostParentsRequireReferences = false;

    /**
     * @var array
     */
    protected $outerMostParents;

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
     *
     * @return int
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * Sets a callback for a particular event.
     *
     * @param string $eventName
     * @param \TYPO3\CMS\Version\Dependency\EventCallback $callback
     * @return \TYPO3\CMS\Version\Dependency\DependencyResolver
     */
    public function setEventCallback($eventName, \TYPO3\CMS\Version\Dependency\EventCallback $callback)
    {
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
    public function executeEventCallback($eventName, $caller, array $callerArguments = [])
    {
        if (isset($this->eventCallbacks[$eventName])) {
            /** @var $callback \TYPO3\CMS\Version\Dependency\EventCallback */
            $callback = $this->eventCallbacks[$eventName];
            return $callback->execute($callerArguments, $caller, $eventName);
        }
        return null;
    }

    /**
     * Sets the condition that outermost parents required at least one child or parent reference.
     *
     * @param bool $outerMostParentsRequireReferences
     * @return \TYPO3\CMS\Version\Dependency\DependencyResolver
     */
    public function setOuterMostParentsRequireReferences($outerMostParentsRequireReferences)
    {
        $this->outerMostParentsRequireReferences = (bool)$outerMostParentsRequireReferences;
        return $this;
    }

    /**
     * Adds an element to be checked for dependent references.
     *
     * @param string $table
     * @param int $id
     * @param array $data
     * @return \TYPO3\CMS\Version\Dependency\ElementEntity
     */
    public function addElement($table, $id, array $data = [])
    {
        $element = $this->getFactory()->getElement($table, $id, $data, $this);
        $elementName = $element->__toString();
        $this->elements[$elementName] = $element;
        return $element;
    }

    /**
     * Gets the outermost parents that define complete dependent structure each.
     *
     * @return array|\TYPO3\CMS\Version\Dependency\ElementEntity[]
     */
    public function getOuterMostParents()
    {
        if (!isset($this->outerMostParents)) {
            $this->outerMostParents = [];
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
    protected function processOuterMostParent(\TYPO3\CMS\Version\Dependency\ElementEntity $element)
    {
        if ($this->outerMostParentsRequireReferences === false || $element->hasReferences()) {
            $outerMostParent = $element->getOuterMostParent();
            if ($outerMostParent !== false) {
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
     * @throws \RuntimeException
     * @param \TYPO3\CMS\Version\Dependency\ElementEntity $outerMostParent
     * @return array
     */
    public function getNestedElements(\TYPO3\CMS\Version\Dependency\ElementEntity $outerMostParent)
    {
        $outerMostParentName = $outerMostParent->__toString();
        if (!isset($this->outerMostParents[$outerMostParentName])) {
            throw new \RuntimeException('Element "' . $outerMostParentName . '" was not detected as outermost parent.', 1289318609);
        }
        $nestedStructure = array_merge([$outerMostParentName => $outerMostParent], $outerMostParent->getNestedChildren());
        return $nestedStructure;
    }

    /**
     * Gets the registered elements.
     *
     * @return array
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Gets an instance of the factory to keep track of element or reference entities.
     *
     * @return \TYPO3\CMS\Version\Dependency\DependencyEntityFactory
     */
    public function getFactory()
    {
        if (!isset($this->factory)) {
            $this->factory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Version\Dependency\DependencyEntityFactory::class);
        }
        return $this->factory;
    }
}
