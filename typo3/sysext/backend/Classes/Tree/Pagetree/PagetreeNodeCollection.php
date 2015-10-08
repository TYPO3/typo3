<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

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
 * Tree Node Collection
 */
class PagetreeNodeCollection extends \TYPO3\CMS\Backend\Tree\TreeNodeCollection
{
    /**
     * Returns the collection in an array representation for e.g. serialization
     *
     * @return array
     */
    public function toArray()
    {
        $arrayRepresentation = parent::toArray();
        unset($arrayRepresentation['serializeClassName']);
        return $arrayRepresentation;
    }
}
