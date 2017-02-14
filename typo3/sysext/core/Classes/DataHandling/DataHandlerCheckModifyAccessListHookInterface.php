<?php
namespace TYPO3\CMS\Core\DataHandling;

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
 * Interface for hook in \TYPO3\CMS\Core\DataHandling\DataHandler::checkModifyAccessList
 */
interface DataHandlerCheckModifyAccessListHookInterface
{
    /**
     * Hook that determines whether a user has access to modify a table.
     *
     * @param bool &$accessAllowed Whether the user has access to modify a table
     * @param string $table The name of the table to be modified
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parent The calling parent object
     */
    public function checkModifyAccessList(&$accessAllowed, $table, \TYPO3\CMS\Core\DataHandling\DataHandler $parent);
}
