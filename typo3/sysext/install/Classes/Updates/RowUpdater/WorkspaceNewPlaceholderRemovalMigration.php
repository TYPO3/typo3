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

/**
 * TYPO3 v11 does not need a "versioned" / "placeholder" pair for newly created records in a version anymore.
 *
 * This upgrade wizards merges those records pairs into one record.
 *
 * The strategy is to keep the t3ver_state=1 record and to merge "payload" fields data like
 * "header / bodytext" and so on from the t3ver_state=-1 record over to the t3ver_state=1 records.
 * The t3ver_state=-1 record is then deleted (or marked as deleted if the table is soft-delete aware).
 *
 * For relations, this is a bit more tricky. When dealing with CSV and ForeignField relations,
 * existing relations are connected to the t3ver_state=1 record. This is fine. For MM relations,
 * they point to the t3ver_state=-1 record, though. The implementation thus finds and updates
 * those MM relations.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class WorkspaceNewPlaceholderRemovalMigration implements RowUpdaterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function getTitle(): string
    {
        return 'Scan for new versioned records of workspaces and migrate the placeholder and versioned records into one record.';
    }

    /**
     * @param string $tableName Table name to check
     * @return bool Return true if a table has workspace enabled
     */
    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        return BackendUtility::isTableWorkspaceEnabled($tableName);
    }

    public function updateTableRow(string $tableName, array $row): array
    {
        $versionState = (int)($row['t3ver_state'] ?? 0);
        if ($versionState === 1) {
            $versionedRecord = $this->fetchVersionedRecord($tableName, (int)$row['uid']);
            if ($versionedRecord === null) {
                return $row;
            }
            foreach ($versionedRecord as $fieldName => $value) {
                if (in_array($fieldName, ['uid', 'pid', 'deleted', 't3ver_state', 't3ver_oid'], true)) {
                    continue;
                }
                if ($this->isMMField($tableName, (string)$fieldName)) {
                    $this->transferMMValues($tableName, (string)$fieldName, (int)$versionedRecord['uid'], (int)$row['uid']);
                    continue;
                }
                $row[$fieldName] = $value;
            }
        } elseif ($versionState === -1) {
            // Delete this row, as it has no use anymore.
            // This is safe to do since the uid of the t3ver_state=1 record is always lower than the -1 one,
            // so the record has been handled already. Rows are always sorted by uid in the row updater.
            $row['deleted'] = 1;
        }
        return $row;
    }

    /**
     * Fetch the t3ver_state = -1 record for a given t3ver_state = 1 record.
     *
     * @param string $tableName
     * @param int $uid
     * @return array|null the versioned record or null if none was found.
     */
    protected function fetchVersionedRecord(string $tableName, int $uid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('t3ver_state', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        return is_array($row) ? $row : null;
    }

    protected function isMMField(string $tableName, string $fieldName): bool
    {
        $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? null;
        if (!is_array($fieldConfig)) {
            return false;
        }
        if (isset($fieldConfig['MM'])) {
            return true;
        }
        return false;
    }

    /**
     * Because MM does not contain workspace information, they were previously bound directly
     * to the versioned record, this information is now transferred to the new version t3ver_state=1
     * record.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param int $originalUid
     * @param int $newUid
     */
    protected function transferMMValues(string $tableName, string $fieldName, int $originalUid, int $newUid): void
    {
        $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? null;

        $mmTable = $fieldConfig['MM'];
        $matchMMFields = $fieldConfig['MM_match_fields'] ?? null;
        $matchMMFieldsMultiple = $fieldConfig['MM_oppositeUsage'] ?? null;
        $isOnRightSide = is_array($matchMMFieldsMultiple);
        $relationFieldName = $isOnRightSide ? 'uid_local' : 'uid_foreign';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTable);
        $queryBuilder->update($mmTable)
            ->set($relationFieldName, $newUid, true, \PDO::PARAM_INT)
            ->where(
                $queryBuilder->expr()->eq($relationFieldName, $queryBuilder->createNamedParameter($originalUid, \PDO::PARAM_INT))
            );
        if ($matchMMFields) {
            foreach ($matchMMFields as $matchMMFieldName => $matchMMFieldValue) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq($matchMMFieldName, $queryBuilder->createNamedParameter($matchMMFieldValue))
                );
            }
        }
        $queryBuilder->executeStatement();
    }
}
