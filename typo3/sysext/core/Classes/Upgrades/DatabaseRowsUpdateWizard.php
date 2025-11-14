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

namespace TYPO3\CMS\Core\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\RowUpdaterService;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterInterface;

/**
 * This is a generic updater to migrate content of TCA rows.
 *
 * Multiple classes implementing interface "RowUpdaterInterface" can be
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
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('databaseRowsUpdateWizard')]
class DatabaseRowsUpdateWizard implements UpgradeWizardInterface, RepeatableInterface
{
    public function __construct(
        private readonly RowUpdaterService $rowUpdaterService,
        private readonly ConnectionPool $connectionPool,
        private readonly Registry $registry,
    ) {}

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
        foreach ($rowUpdaterNotExecuted as $identifier) {
            $rowUpdater = $this->rowUpdaterService->getRowUpdater($identifier);
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
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function executeUpdate(): bool
    {
        // If rows from the target table that is updated and the sys_registry table are on the
        // same connection, the row update statement and sys_registry position update will be
        // handled in a transaction to have an atomic operation in case of errors during execution.
        $connectionForSysRegistry = $this->connectionPool->getConnectionForTable('sys_registry');

        /** @var array<string, RowUpdaterInterface> $rowUpdaterInstances */
        $rowUpdaterInstances = [];
        // Single row updater instances are created only once for this method giving
        // them a chance to set up local properties during hasPotentialUpdateForTable()
        // and using that in updateTableRow()
        foreach ($this->getRowUpdatersToExecute() as $identifier) {
            $rowUpdaterInstances[$identifier] = $this->rowUpdaterService->getRowUpdater($identifier);
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
        /** @var array<string, array<int, array{identifier: string, updater: RowUpdaterInterface}>> $tableToUpdaterList */
        $tableToUpdaterList = [];
        foreach ($listOfAllTables as $table) {
            foreach ($rowUpdaterInstances as $identifier => $updater) {
                if ($updater->hasPotentialUpdateForTable($table)) {
                    $tableToUpdaterList[$table] ??= [];
                    $tableToUpdaterList[$table][] = [
                        'identifier' => $identifier,
                        'updater' => $updater,
                    ];
                }
            }
        }

        // Iterate through all rows of all tables that have potential row updaters attached,
        // feed each single row to each updater and finally update each row in database if
        // a row updater changed a fields
        foreach ($tableToUpdaterList as $table => $updaters) {
            $connectionForTable = $this->connectionPool->getConnectionForTable($table);
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
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
                foreach ($updaters as $updaterInfo) {
                    $updater = $updaterInfo['updater'];
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
                        $this->registry->set('installUpdateRows', 'rowUpdatePosition', $startPosition);
                        $rowCountWithoutUpdate = 0;
                    }
                } else {
                    $rowCountWithoutUpdate = 0;
                    $startPosition = [
                        'table' => $table,
                        'uid' => $rowBefore['uid'],
                    ];
                    if ($connectionForSysRegistry === $connectionForTable) {
                        // Target table and sys_registry table are on the same connection, use a transaction
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
                        // Different connections for table and sys_registry.
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
        $this->registry->remove('installUpdateRows', 'rowUpdatePosition');
        // Mark row updaters that were executed as done
        foreach ($rowUpdaterInstances as $identifier => $_) {
            $this->setRowUpdaterExecuted($identifier);
        }

        return true;
    }

    /**
     * Return an array of class names that are not yet marked as done.
     *
     * @return string[] Row updater identifiers
     */
    protected function getRowUpdatersToExecute(): array
    {
        return $this->rowUpdaterService->getRowUpdatersToExecute();
    }

    /**
     * Mark a single updater as done
     */
    protected function setRowUpdaterExecuted(string $identifier)
    {
        $this->rowUpdaterService->setRowUpdaterExecuted($identifier);
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
        $startPosition = $this->registry->get('installUpdateRows', 'rowUpdatePosition', []);
        if (empty($startPosition)) {
            $startPosition = [
                'table' => $firstTable,
                'uid' => 0,
            ];
            $this->registry->set('installUpdateRows', 'rowUpdatePosition', $startPosition);
        }
        return $startPosition;
    }

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
                'entry_value' => Connection::PARAM_LOB,
                'entry_namespace' => Connection::PARAM_STR,
                'entry_key' => Connection::PARAM_STR,
            ]
        );
    }
}
