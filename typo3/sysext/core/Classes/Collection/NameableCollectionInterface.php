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

namespace TYPO3\CMS\Core\Collection;

/**
 * Marker interface for a collection class with title and description
 *
 * Collections might be used internally as well as being shown
 * with the nameable interface a title and a description are added
 * to a collection, allowing every collection implementing Nameable
 * being displayed by the same logic.
 */
interface NameableCollectionInterface
{
    /**
     * Setter for the title
     *
     * @param string $title
     */
    public function setTitle($title);

    /**
     * Setter for the description
     *
     * @param string $description
     */
    public function setDescription($description);

    /**
     * Getter for the title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Getter for the description
     */
    public function getDescription();
}
