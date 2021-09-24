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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Removes all records of type MOVE_PLACEHOLDER (t3ver_state = 3) from the system.
 * Also makes sure that the important values (that is: pid and sorting) are migrated
 * into the connected MOVE_POINTER (t3ver_state=4).
 *
 * move placeholder (t3ver_state=3) contains t3ver_move_id = UID of live version
 * move pointer (t3ver_state=4) contains t3ver_oid = UID of live version
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class WorkspaceMovePlaceholderRemovalMigration implements RowUpdaterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function getTitle(): string
    {
        return 'Scan for move placeholders of workspaces and remove them from the database.';
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
     * Update "pid" field and delete the move placeholder record completely
     *
     * @param string $tableName Table name
     * @param array $row Given row data
     * @return array Modified row data
     */
    public function updateTableRow(string $tableName, array $row): array
    {
        // We only want the information from the move placeholder
        if ((int)$row['t3ver_state'] !== 3) {
            return $row;
        }

        // Since t3ver_state = 3 and t3ver_state = 4 are not connected, the only way to do this is via the live record
        $liveUid = (int)($row['t3ver_move_id'] ?? 0);
        $workspaceId = (int)($row['t3ver_wsid'] ?? 0);

        // Update the move pointer with the pid & sorting values of the move placeholder
        if ($liveUid > 0) {
            $updatedFieldsForMovePointer = [
                'pid' => (int)$row['pid'],
            ];
            $sortByFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?? null;
            if ($sortByFieldName) {
                $updatedFieldsForMovePointer[$sortByFieldName] = (int)$row[$sortByFieldName];
            }

            // Update the move pointer record
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($tableName)
                ->update(
                    $tableName,
                    $updatedFieldsForMovePointer,
                    [
                        't3ver_oid' => $liveUid,
                        't3ver_state' => VersionState::MOVE_POINTER,
                        't3ver_wsid' => $workspaceId,
                    ]
                );
        }

        // The "deleted" key marks the information that this record should be deleted
        // (with soft-delete or hard-delete) in the RowUpdater main class.
        $row['deleted'] = 1;
        return $row;
    }
}
