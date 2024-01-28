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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Object to create and keep track of element or reference entities.
 *
 * @internal
 */
class DependencyEntityFactory
{
    protected array $elements = [];
    protected array $references = [];

    /**
     * Gets and registers a new element.
     */
    public function getElement(string $table, int $id, array $data, DependencyResolver $dependency): ElementEntity
    {
        $element = GeneralUtility::makeInstance(ElementEntity::class, $table, $id, $data, $dependency);
        $elementName = $element->__toString();
        if (!isset($this->elements[$elementName])) {
            $this->elements[$elementName] = $element;
        }
        return $this->elements[$elementName];
    }

    /**
     * Gets and registers a new reference.
     */
    public function getReference(ElementEntity $element, string $field): ReferenceEntity
    {
        $referenceName = $element->__toString() . '.' . $field;
        if (!isset($this->references[$referenceName][$field])) {
            $this->references[$referenceName][$field] = GeneralUtility::makeInstance(ReferenceEntity::class, $element, $field);
        }
        return $this->references[$referenceName][$field];
    }

    /**
     * Gets and registers a new reference.
     *
     * @see getElement
     * @see getReference
     */
    public function getReferencedElement(string $table, int $id, string $field, array $data, DependencyResolver $dependency): ReferenceEntity
    {
        return $this->getReference($this->getElement($table, $id, $data, $dependency), $field);
    }
}
