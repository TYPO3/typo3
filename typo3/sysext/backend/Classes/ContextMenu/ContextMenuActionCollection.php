<?php
namespace TYPO3\CMS\Backend\ContextMenu;

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
 * Context Menu Action Collection
 */
class ContextMenuActionCollection extends \ArrayObject
{
    /**
     * Returns the collection in an array representation for e.g. serialization
     *
     * @return array
     */
    public function toArray()
    {
        $iterator = $this->getIterator();
        $arrayRepresentation = [];
        while ($iterator->valid()) {
            $arrayRepresentation[] = $iterator->current()->toArray();
            $iterator->next();
        }
        return $arrayRepresentation;
    }
}
