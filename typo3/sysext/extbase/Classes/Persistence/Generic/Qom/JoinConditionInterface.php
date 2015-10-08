<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

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
 * Filters the set of node-tuples formed from a join.
 */
interface JoinConditionInterface
{
    /**
     * Gets the name of the first selector.
     *
     * @return string the selector name; non-null
     */
    public function getSelector1Name();
}
