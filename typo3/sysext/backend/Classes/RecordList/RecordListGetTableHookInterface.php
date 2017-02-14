<?php
namespace TYPO3\CMS\Backend\RecordList;

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
 * Interface for classes which hook into \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
 * and do additional getTable processing
 */
interface RecordListGetTableHookInterface
{
    /**
     * modifies the DB list query
     *
     * @param string $table The current database table
     * @param int $pageId The record's page ID
     * @param string $additionalWhereClause An additional WHERE clause
     * @param string $selectedFieldsList Comma separated list of selected fields
     * @param \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $parentObject Parent \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList object
     */
    public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject);
}
