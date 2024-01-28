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

/**
 * Object to hold reference information of a database field and one accordant element.
 *
 * @internal
 */
class ReferenceEntity
{
    protected ElementEntity $element;
    protected string $field;

    public function __construct(ElementEntity $element, string $field)
    {
        $this->element = $element;
        $this->field = $field;
    }

    /**
     * Gets the elements.
     */
    public function getElement(): ElementEntity
    {
        return $this->element;
    }

    /**
     * Gets the field.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Converts this object for string representation.
     */
    public function __toString(): string
    {
        return $this->element . '.' . $this->field;
    }
}
