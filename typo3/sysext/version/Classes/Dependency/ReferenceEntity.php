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
 * Object to hold reference information of a database field and one accordant element.
 */
class ReferenceEntity
{
    /**
     * @var \TYPO3\CMS\Version\Dependency\ElementEntity
     */
    protected $element;

    /**
     * @var string
     */
    protected $field;

    /**
     * Creates this object.
     *
     * @param \TYPO3\CMS\Version\Dependency\ElementEntity $element
     * @param string $field
     */
    public function __construct(\TYPO3\CMS\Version\Dependency\ElementEntity $element, $field)
    {
        $this->element = $element;
        $this->field = $field;
    }

    /**
     * Gets the elements.
     *
     * @return \TYPO3\CMS\Version\Dependency\ElementEntity
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
