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

/**
 * Object to hold reference information of a database field and one accordant element.
 */
class ReferenceEntity
{
    /**
     * @var ElementEntity
     */
    protected $element;

    /**
     * @var string
     */
    protected $field;

    /**
     * Creates this object.
     *
     * @param string $field
     */
    public function __construct(ElementEntity $element, $field)
    {
        $this->element = $element;
        $this->field = $field;
    }

    /**
     * Gets the elements.
     *
     * @return ElementEntity
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Gets the field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Converts this object for string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->element . '.' . $this->field;
    }
}
