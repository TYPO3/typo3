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

namespace TYPO3\CMS\Workspaces\Dependency;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Object to create and keep track of element or reference entities.
 */
class DependencyEntityFactory
{
    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @var array
     */
    protected $references = [];

    /**
     * Gets and registers a new element.
     *
     * @param string $table
     * @param int $id
     * @param array $data (optional)
     * @return ElementEntity
     */
    public function getElement($table, $id, array $data, DependencyResolver $dependency)
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
     *
     * @param string $field
     * @return ReferenceEntity
     */
    public function getReference(ElementEntity $element, $field)
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
     * @param string $table
     * @param int $id
     * @param string $field
     * @param array $data (optional)
     * @return ReferenceEntity
     * @see getElement
     * @see getReference
     */
    public function getReferencedElement($table, $id, $field, array $data, DependencyResolver $dependency)
    {
        return $this->getReference($this->getElement($table, $id, $data, $dependency), $field);
    }
}
