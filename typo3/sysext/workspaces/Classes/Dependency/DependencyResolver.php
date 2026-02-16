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

namespace TYPO3\CMS\Workspaces\Dependency;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Object to handle and determine dependent references of elements.
 *
 * @internal
 */
class DependencyResolver
{
    protected int $workspace = 0;
    protected ?DependencyEntityFactory $factory;
    protected array $elements = [];
    protected ?array $outerMostParents;
    protected ?EventDispatcherInterface $eventDispatcher = null;
    protected ?DependencyCollectionAction $action = null;

    public function setWorkspace(int $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function getWorkspace(): int
    {
        return $this->workspace;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setAction(DependencyCollectionAction $action): void
    {
        $this->action = $action;
    }

    public function getAction(): ?DependencyCollectionAction
    {
        return $this->action;
    }

    /**
     * Adds an element to be checked for dependent references.
     */
    public function addElement(string $table, int $id, array $data = []): ElementEntity
    {
        $element = $this->getFactory()->getElement($table, $id, $data, $this);
        $elementName = $element->__toString();
        $this->elements[$elementName] = $element;
        return $element;
    }

    /**
     * Gets the outermost parents that define complete dependent structure each.
     *
     * @return ElementEntity[]
     */
    public function getOuterMostParents(): array
    {
        if (!isset($this->outerMostParents)) {
            $this->outerMostParents = [];
            /** @var ElementEntity $element */
            foreach ($this->elements as $element) {
                $this->processOuterMostParent($element);
            }
        }
        return $this->outerMostParents;
    }

    /**
     * Processes and registers the outermost parents accordant to the registered elements.
     */
    protected function processOuterMostParent(ElementEntity $element): void
    {
        if ($element->hasReferences()) {
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
     */
    public function getNestedElements(ElementEntity $outerMostParent): array
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
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * Gets an instance of the factory to keep track of element or reference entities.
     */
    public function getFactory(): DependencyEntityFactory
    {
        if (!isset($this->factory)) {
            $this->factory = GeneralUtility::makeInstance(DependencyEntityFactory::class);
        }
        return $this->factory;
    }
}
