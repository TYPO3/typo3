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
 * Interface for collection class being sortable
 *
 * This interface allows you to either define a callback implementing
 * your own sorting method and explicitly move an item from one position
 * to another.
 *
 * This assumes that entries are sortable and therefore an index can be assigned
 */
interface SortableCollectionInterface
{
    /**
     * Sorts collection via given callBackFunction
     *
     * The comparison function given as must return an integer less than, equal to, or greater than
     * zero if the first argument is considered to be respectively less than, equal to, or greater than the second.
     *
     * @param $callbackFunction
     * @see http://www.php.net/manual/en/function.usort.php
     */
    public function usort($callbackFunction);

    /**
     * Moves the item within the collection
     *
     * The item at $currentPosition will be moved to
     * $newPosition. Omitting $newPosition will move to top.
     *
     * @param int $currentPosition
     * @param int $newPosition
     */
    public function moveItemAt($currentPosition, $newPosition = 0);
}
