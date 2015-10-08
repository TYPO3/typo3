<?php
namespace TYPO3\CMS\Core\Collection;

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
 * Interface for collection classes which es enabled to be modified
 */
interface EditableCollectionInterface
{
    /**
     * Adds on entry to the collection
     *
     * @param mixed $data
     * @return void
     */
    public function add($data);

    /**
     * Adds a set of entries to the collection
     *
     * @param CollectionInterface $other
     * @return void
     */
    public function addAll(CollectionInterface $other);

    /**
     * Remove the given entry from collection
     *
     * Note: not the given "index"
     *
     * @param mixed $data
     * @return void
     */
    public function remove($data);

    /**
     * Removes all entries from the collection
     *
     * collection will be empty afterwards
     *
     * @return void
     */
    public function removeAll();
}
