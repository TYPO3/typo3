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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for DataHandler to gather requests for reference index updates
 * and perform them in one go after other operations have been done.
 * This is used to suppress multiple reference index update calls for the same
 * workspace/table/uid combination within one DataHandler main call.
 *
 * @internal should only be used by the TYPO3 Core
 */
class ReferenceIndexUpdater
{
    /**
     * [ workspaceId => [ tableName => [ uid ] ] ]
     *
     * @var array<int, array<string, array<int, int>>>
     */
    protected $updateRegistry = [];

    /**
     * [ workspaceId => [ tableName => [
     *      'uid' => uid,
     *      'targetWorkspace' => $targetWorkspace
     * ] ] ]
     *
     * @var array<int, array<string, array<int, array<string, string|int>>>>
     */
    protected $updateRegistryToItem = [];

    /**
     * [ workspaceId => [ tableName => [ uid ] ] ]
     *
     * @var array<int, array<string, array<int, int>>>
     */
    protected $dropRegistry = [];

    /**
     * Register a workspace/table/uid row for update
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int $workspace Workspace the record lives in
     */
    public function registerForUpdate(string $table, int $uid, int $workspace): void
    {
        if ($workspace && !BackendUtility::isTableWorkspaceEnabled($table)) {
            // If a user is in some workspace and changes relations of not workspace aware
            // records, the reference index update needs to be performed as if the user
            // is in live workspace. This is detected here and the update is registered for live.
            $workspace = 0;
        }
        if (!isset($this->updateRegistry[$workspace][$table])) {
            $this->updateRegistry[$workspace][$table] = [];
        }
        if (!in_array($uid, $this->updateRegistry[$workspace][$table], true)) {
            $this->updateRegistry[$workspace][$table][] = $uid;
        }
    }

    /**
     * Find reference index rows pointing to given table/uid combination and register them for update. Important in
     * delete and publish scenarios where a child is deleted to make sure any references to this child are dropped, too.
     * In publish scenarios reference index may exist for a non-live workspace, but should be updated for live workspace.
     * The optional $targetWorkspace argument is used for this.
     *
     * @param string $table Table name, used as ref_table
     * @param int $uid Record uid, used as ref_uid
     * @param int $workspace The workspace given record lives in
     * @param int|null $targetWorkspace The target workspace the record has been swapped to
     */
    public function registerUpdateForReferencesToItem(string $table, int $uid, int $workspace, int $targetWorkspace = null): void
    {
        if ($workspace && !BackendUtility::isTableWorkspaceEnabled($table)) {
            // If a user is in some workspace and changes relations of not workspace aware
            // records, the reference index update needs to be performed as if the user
            // is in live workspace. This is detected here and the update is registered for live.
            $workspace = 0;
        }
        if ($targetWorkspace === null) {
            $targetWorkspace = $workspace;
        }
        if (!isset($this->updateRegistryToItem[$workspace][$table])) {
            $this->updateRegistryToItem[$workspace][$table] = [];
        }
        $recordAndTargetWorkspace = [
            'uid' => $uid,
            'targetWorkspace' => $targetWorkspace,
        ];
        if (!in_array($recordAndTargetWorkspace, $this->updateRegistryToItem[$workspace][$table], true)) {
            $this->updateRegistryToItem[$workspace][$table][] = $recordAndTargetWorkspace;
        }
    }

    /**
     * Delete rows from sys_refindex a table / uid combination is involved in:
     * Either on left side (tablename + recuid) OR right side (ref_table + ref_uid).
     * Useful in scenarios like workspace-discard where parents or children are hard deleted: The
     * expensive updateRefIndex() does not need to be called since we can just drop straight ahead.
     *
     * @param string $table Table name, used as tablename and ref_table
     * @param int $uid Record uid, used as recuid and ref_uid
     * @param int $workspace Workspace the record lives in
     */
    public function registerForDrop(string $table, int $uid, int $workspace): void
    {
        if ($workspace && !BackendUtility::isTableWorkspaceEnabled($table)) {
            // If a user is in some workspace and changes relations of not workspace aware
            // records, the reference index update needs to be performed as if the user
            // is in live workspace. This is detected here and the update is registered for live.
            $workspace = 0;
        }
        if (!isset($this->dropRegistry[$workspace][$table])) {
            $this->dropRegistry[$workspace][$table] = [];
        }
        if (!in_array($uid, $this->dropRegistry[$workspace][$table], true)) {
            $this->dropRegistry[$workspace][$table][] = $uid;
        }
    }

    /**
     * Perform the reference index update operations
     */
    public function update(): void
    {
        // Register updates to an item for update
        foreach ($this->updateRegistryToItem as $workspace => $tableArray) {
            foreach ($tableArray as $table => $recordArray) {
                foreach ($recordArray as $item) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
                    $statement = $queryBuilder
                        ->select('tablename', 'recuid')
                        ->from('sys_refindex')
                        ->where(
                            $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($table)),
                            $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($item['uid'], \PDO::PARAM_INT)),
                            $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($workspace, \PDO::PARAM_INT))
                        )
                        ->executeQuery();
                    while ($row = $statement->fetchAssociative()) {
                        $this->registerForUpdate($row['tablename'], (int)$row['recuid'], (int)$item['targetWorkspace']);
                    }
                }
            }
        }
        $this->updateRegistryToItem = [];

        // Drop rows from reference index if requested. Note this is performed *after* update-to-item, to
        // find rows pointing to a record and register updates before rows are dropped. Needed if a record
        // changes the workspace during publish: In this case all records pointing to the record in a workspace
        // need to be registered for update for live workspace and after that the workspace rows can be dropped.
        foreach ($this->dropRegistry as $workspace => $tableArray) {
            foreach ($tableArray as $table => $uidArray) {
                foreach ($uidArray as $uid) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
                    $queryBuilder->delete('sys_refindex')
                        ->where(
                            $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($workspace, \PDO::PARAM_INT)),
                            $queryBuilder->expr()->orX(
                                $queryBuilder->expr()->andX(
                                    $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table)),
                                    $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                                ),
                                $queryBuilder->expr()->andX(
                                    $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($table)),
                                    $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                                )
                            )
                        )
                        ->executeStatement();
                }
            }
        }
        $this->dropRegistry = [];

        // Perform reference index updates
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        foreach ($this->updateRegistry as $workspace => $tableArray) {
            $referenceIndex->setWorkspaceId($workspace);
            foreach ($tableArray as $table => $uidArray) {
                foreach ($uidArray as $uid) {
                    $referenceIndex->updateRefIndexTable($table, $uid);
                }
            }
        }
        $this->updateRegistry = [];
    }
}
