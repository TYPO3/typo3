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
 * Collection for handling records from a single database-table.
 *
 * @template T
 * @extends CollectionInterface<T>
 */
interface RecordCollectionInterface extends CollectionInterface, NameableCollectionInterface
{
    /**
     * Setter for the name of the data-source table
     *
     * @param string $tableName
     */
    public function setItemTableName($tableName);

    /**
     * Setter for the name of the data-source table
     *
     * @return string
     */
    public function getItemTableName();
}
