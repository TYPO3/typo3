<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\Updates\RowUpdater;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Migrate all records that have "pid=-1" to their proper equivalents.
 * t3_wsid=0 AND pid=-1 ---> discarded records or archived records. Since we have no connection to the original anymore, we remove them (hard delete)
 * t3_wsid>0 AND pid=-1 AND t3ver_oid>0 -> find the live version and take the PID from the live version, and replace the PID
 * Since the move pointer (t3ver_state=3) is not affected, as it contains the future live PID, there is no need to touch these records.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class WorkspaceVersionRecordsMigration implements RowUpdaterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function getTitle(): string
    {
        return 'Scan for versioned records and fix their pid, or if no connection to a workspace is given, remove them completely to avoid having them shown on the live website.';
    }

    /**
     * @param string $tableName Table name to check
     * @return bool Return true if a table has workspace enabled
     */
    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        return BackendUtility::isTableWorkspaceEnabled($tableName);
    }

    /**
     * Update "pid" field or delete record completely
     *
     * @param string $tableName Table name
     * @param array $row Given row data
     * @return array Modified row data
     */
    public function updateTableRow(string $tableName, array $row): array
    {
        // We only modify records with "pid=-1"
        if ((int)$row['pid'] !== -1) {
            return $row;
        }
        // pid=-1 and live workspace => this may be very old "previous live" records that should be discarded
        if ((int)$row['t3ver_wsid'] === 0) {
            $deleteField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? 'deleted';
            $row[$deleteField] = 1;
            // continue processing versions
        }
        // regular versions and placeholders (t3ver_state one of -1, 0, 2, 4 - but not 3) having t3ver_oid set
        if ((int)$row['t3ver_oid'] > 0 && (int)$row['t3ver_state'] !== 3) {
            // We have a live version, let's connect that one
            $liveRecord = $this->fetchPageId($tableName, (int)$row['t3ver_oid']);
            if (is_array($liveRecord)) {
                $row['pid'] = (int)$liveRecord['pid'];
                return $row;
            }
        }
        // move placeholder (t3ver_state=3) pointing to live version in t3ver_move_id
        if ((int)$row['t3ver_state'] === 3 && (int)$row['t3ver_move_id'] > 0) {
            // We have a live version, let's connect that one
            $liveRecord = $this->fetchPageId($tableName, (int)$row['t3ver_move_id']);
            if (is_array($liveRecord)) {
                $row['pid'] = (int)$liveRecord['pid'];
                return $row;
            }
        }
        // No live version available
        return $row;
    }

    protected function fetchPageId(string $tableName, int $id): ?array
    {
        return BackendUtility::getRecord($tableName, $id, 'pid');
    }
}
