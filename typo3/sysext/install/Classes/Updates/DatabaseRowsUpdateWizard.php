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

namespace TYPO3\CMS\Install\Updates;

use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\RowUpdater\L18nDiffsourceToJsonMigration;
use TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface;
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceMovePlaceholderRemovalMigration;
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceNewPlaceholderRemovalMigration;
use TYPO3\CMS\Install\Updates\RowUpdater\WorkspaceVersionRecordsMigration;

/**
 * This is a generic updater to migrate content of TCA rows.
 *
 * Multiple classes implementing interface "RowUpdateInterface" can be
 * registered here, each for a specific update purpose.
 *
 * The updater fetches each row of all TCA registered tables and
 * visits the client classes who may modify the row content.
 *
 * The updater remembers for each class if it run through, so the updater
 * will be shown again if a new updater class is registered that has not
 * been run yet.
 *
 * A start position pointer is stored in the registry that is updated during
 * the run process, so if for instance the PHP process runs into a timeout,
 * the job can restart at the position it stopped.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class DatabaseRowsUpdateWizard implements UpgradeWizardInterface, RepeatableInterface
{
    /**
     * @var array Single classes that may update rows
     */
    protected $rowUpdater = [
        WorkspaceVersionRecordsMigration::class,
        L18nDiffsourceToJsonMigration::class,
        WorkspaceMovePlaceholderRemovalMigration::class,
        WorkspaceNewPlaceholderRemovalMigration::class,
    ];

    /**
     * @internal
     * @return string[]
     */
    public function getAvailableRowUpdater(): array
    {
        return $this->rowUpdater;
    }

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'databaseRowsUpdateWizard';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Execute database migrations on single rows';
    }

    /**
     * @return string Longer description of this updater
     * @throws \RuntimeException
     */
    public function getDescription(): string
    {
        $rowUpdaterNotExecuted = $this->getRowUpdatersToExecute();
        $description = 'Row updaters that have not been executed:';
        foreach ($rowUpdaterNotExecuted as $rowUpdateClassName) {
            $rowUpdater = GeneralUtility::makeInstance($rowUpdateClassName);
            if (!$rowUpdater instanceof RowUpdaterInterface) {
                throw new \RuntimeException(
                    'Row updater must implement RowUpdaterInterface',
                    1484066647
                );
            }
            $description .= LF . $rowUpdater->getTitle();
        }
        return $description;
    }

    /**
     * @return bool True if at least one row updater is not marked done
     */
    public function updateNecessary(): bool
    {
        return !empty($this->getRowUpdatersToExecute());
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Performs the configuration update.
     *
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function executeUpdate(): bool
    {
        $registry = GeneralUtility::makeInstance(Registry::class);

        // If rows from the target table that is updated and the sys_registry table are on the
        // same connection, the row update statement and sys_registry position update will be
        // handled in a transaction to have an atomic operation in case of errors during execution.
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionForSysRegistry = $connectionPool->getConnectionForTable('sys_registry');

        /** @var RowUpdaterInterface[] $rowUpdaterInstances */
        $rowUpdaterInstances = [];
        // Single row updater instances are created only once for this method giving
        // them a chance to set up local properties during hasPotentialUpdateForTable()
        // and using that in updateTableRow()
        foreach ($this->getRowUpdatersToExecute() as $rowUpdater) {
            $rowUpdaterInstance = GeneralUtility::makeInstance($rowUpdater);
            if (!$rowUpdaterInstance instanceof RowUpdaterInterface) {
                throw new \RuntimeException(
                    'Row updater must implement RowUpdaterInterface',
                    1484071612
                );
            }
            $rowUpdaterInstances[] = $rowUpdaterInstance;
        }

        // Scope of the row updater is to update all rows that have TCA,
        // our list of tables is just the list of loaded TCA tables.
        /** @var string[] $listOfAllTables */
        $listOfAllTables = array_keys($GLOBALS['TCA']);

        // In case the PHP ended for whatever reason, fetch the last position from registry
        // and throw away all tables before that start point.
        sort($listOfAllTables);
        reset($listOfAllTables);
        $firstTable = current($listOfAllTables) ?: '';
        $startPosition = $this->getStartPosition($firstTable);
        foreach ($listOfAllTables as $key => $table) {
            if ($table === $startPosition['table']) {
                break;
            }
            unset($listOfAllTables[$key]);
        }

        // Ask each row updater if it potentially has field updates for rows of a table
        $tableToUpdaterList = [];
        foreach ($listOfAllTables as $table) {
            foreach ($rowUpdaterInstances as $updater) {
                if ($updater->hasPotentialUpdateForTable($table)) {
                    if (!isset($tableToUpdaterList[$table]) || !is_array($tableToUpdaterList[$table])) {
                        $tableToUpdaterList[$table] = [];
                    }
                    $tableToUpdaterList[$table][] = $updater;
                }
            }
        }

        // Iterate through all rows of all tables that have potential row updaters attached,
        // feed each single row to each updater and finally update each row in database if
        // a row updater changed a fields
        foreach ($tableToUpdaterList as $table => $updaters) {
            /** @var RowUpdaterInterface[] $updaters */
            $connectionForTable = $connectionPool->getConnectionForTable($table);
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->select('*')
                ->from($table)
                ->orderBy('uid');
            if ($table === $startPosition['table']) {
                $queryBuilder->where(
                    $queryBuilder->expr()->gt('uid', $queryBuilder->createNamedParameter($startPosition['uid']))
                );
            }
            $statement = $queryBuilder->executeQuery();
            $rowCountWithoutUpdate = 0;
            while ($row = $statement->fetchAssociative()) {
                $rowBefore = $row;
                foreach ($updaters as $updater) {
                    $row = $updater->updateTableRow($table, $row);
                }
                $updatedFields = array_diff_assoc($row, $rowBefore);
                if (empty($updatedFields)) {
                    // Updaters changed no field of that row
                    $rowCountWithoutUpdate++;
                    if ($rowCountWithoutUpdate >= 200) {
                        // Update startPosition if there were many rows without data change
                        $startPosition = [
                            'table' => $table,
                            'uid' => $row['uid'],
                        ];
                        $registry->set('installUpdateRows', 'rowUpdatePosition', $startPosition);
                        $rowCountWithoutUpdate = 0;
                    }
                } else {
                    $rowCountWithoutUpdate = 0;
                    $startPosition = [
                        'table' => $table,
                        'uid' => $rowBefore['uid'],
                    ];
                    if ($connectionForSysRegistry === $connectionForTable
                        && !($connectionForSysRegistry->getDatabasePlatform() instanceof SQLServerPlatform)
                    ) {
                        // Target table and sys_registry table are on the same connection and not mssql, use a transaction
                        $connectionForTable->beginTransaction();
                        try {
                            $this->updateOrDeleteRow(
                                $connectionForTable,
                                $connectionForTable,
                                $table,
                                (int)$rowBefore['uid'],
                                $updatedFields,
                                $startPosition
                            );
                            $connectionForTable->commit();
                        } catch (\Exception $up) {
                            $connectionForTable->rollBack();
                            throw $up;
                        }
                    } else {
                        // Either different connections for table and sys_registry, or mssql.
                        // SqlServer can not run a transaction for a table if the same table is queried
                        // currently - our above ->fetchAssociative() main loop.
                        // So, execute two distinct queries and hope for the best.
                        $this->updateOrDeleteRow(
                            $connectionForTable,
                            $connectionForSysRegistry,
                            $table,
                            (int)$rowBefore['uid'],
                            $updatedFields,
                            $startPosition
                        );
                    }
                }
            }
        }

        // Ready with updates, remove position information from sys_registry
        $registry->remove('installUpdateRows', 'rowUpdatePosition');
        // Mark row updaters that were executed as done
        foreach ($rowUpdaterInstances as $updater) {
            $this->setRowUpdaterExecuted($updater);
        }

        return true;
    }

    /**
     * Return an array of class names that are not yet marked as done.
     *
     * @return array Class names
     */
    protected function getRowUpdatersToExecute(): array
    {
        $doneRowUpdater = GeneralUtility::makeInstance(Registry::class)->get('installUpdateRows', 'rowUpdatersDone', []);
        return array_diff($this->rowUpdater, $doneRowUpdater);
    }

    /**
     * Mark a single updater as done
     *
     * @param RowUpdaterInterface $updater
     */
    protected function setRowUpdaterExecuted(RowUpdaterInterface $updater)
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $doneRowUpdater = $registry->get('installUpdateRows', 'rowUpdatersDone', []);
        $doneRowUpdater[] = get_class($updater);
        $registry->set('installUpdateRows', 'rowUpdatersDone', $doneRowUpdater);
    }

    /**
     * Return an array with table / uid combination that specifies the start position the
     * update row process should start with.
     *
     * @param string $firstTable Table name of the first TCA in case the start position needs to be initialized
     * @return array New start position
     */
    protected function getStartPosition(string $firstTable): array
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $startPosition = $registry->get('installUpdateRows', 'rowUpdatePosition', []);
        if (empty($startPosition)) {
            $startPosition = [
                'table' => $firstTable,
                'uid' => 0,
            ];
            $registry->set('installUpdateRows', 'rowUpdatePosition', $startPosition);
        }
        return $startPosition;
    }

    /**
     * @param Connection $connectionForTable
     * @param string $table
     * @param array $updatedFields
     * @param int $uid
     * @param Connection $connectionForSysRegistry
     * @param array $startPosition
     */
    protected function updateOrDeleteRow(Connection $connectionForTable, Connection $connectionForSysRegistry, string $table, int $uid, array $updatedFields, array $startPosition): void
    {
        $deleteField = $GLOBALS['TCA'][$table]['ctrl']['delete'] ?? null;
        if ($deleteField === null && isset($updatedFields['deleted']) && $updatedFields['deleted'] === 1) {
            $connectionForTable->delete(
                $table,
                [
                    'uid' => $uid,
                ]
            );
        } else {
            $connectionForTable->update(
                $table,
                $updatedFields,
                [
                    'uid' => $uid,
                ]
            );
        }
        $connectionForSysRegistry->update(
            'sys_registry',
            [
                'entry_value' => serialize($startPosition),
            ],
            [
                'entry_namespace' => 'installUpdateRows',
                'entry_key' => 'rowUpdatePosition',
            ],
            [
                // Needs to be declared LOB, so MSSQL can handle the conversion from string (nvarchar) to blob (varbinary)
                'entry_value' => \PDO::PARAM_LOB,
                'entry_namespace' => \PDO::PARAM_STR,
                'entry_key' => \PDO::PARAM_STR,
            ]
        );
    }
}
