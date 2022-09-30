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

namespace TYPO3\CMS\Backend\RecordList;

/**
 * Interface for classes which hook into \TYPO3\CMS\Backend\RecordList\DatabaseRecordList
 * and do additional getTable processing
 *
 * @deprecated not in use since TYPO3 v12.0. will be removed in TYPO3 v13.0 and kept for backwards-compatibility
 * for extensions using the hook and the new PSR-14 event "ModifyDatabaseQueryForRecordListingEvent" which
 * should be used instead.
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
     * @param DatabaseRecordList $parentObject
     */
    public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject);
}
