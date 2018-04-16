<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\RowUpdater\ImageCropUpdater;
use TYPO3\CMS\Install\Updates\RowUpdater\L10nModeUpdater;
use TYPO3\CMS\Install\Updates\RowUpdater\RowUpdaterInterface;
use TYPO3\CMS\Install\Updates\RowUpdater\RteLinkSyntaxUpdater;

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
 */
class DatabaseRowsUpdateWizard extends AbstractUpdate
{
    /**
     * @var string Title of this updater
     */
    protected $title = 'Execute database migrations on single rows';

    /**
     * @var array Single classes that may update rows
     */
    protected $rowUpdater = [
        L10nModeUpdater::class,
        ImageCropUpdater::class,
        RteLinkSyntaxUpdater::class,
    ];

    /**
     * Checks if an update is needed by looking up in registry if all
     * registered update row classes are marked as done or not.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = false;
        $rowUpdaterNotExecuted = $this->getRowUpdatersToExecute();
        if (!empty($rowUpdaterNotExecuted)) {
            $updateNeeded = true;
        }
        if (!$updateNeeded) {
            return false;
        }

        $description = 'Some row updaters have not been executed:';
        foreach ($rowUpdaterNotExecuted as $rowUpdateClassName) {
            $rowUpdater = GeneralUtility::makeInstance($rowUpdateClassName);
            if (!$rowUpdater instanceof RowUpdaterInterface) {
                throw new \RuntimeException(
                    'Row updater must implement RowUpdaterInterface',
                    1484066647
                );
            }
            $description .= '<br />' . htmlspecialchars($rowUpdater->getTitle());
        }

        return $updateNeeded;
    }

    /**
     * Performs the configuration update.
     *
     * @param array &$databaseQueries Queries done in this update - not filled for this updater
     * @param string &$customMessage Custom message
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
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
        $listOfAllTables = array_keys($GLOBALS['TCA']);

        // In case the PHP ended for whatever reason, fetch the last position from registry
        // and throw away all tables before that start point.
        sort($listOfAllTables);
        reset($listOfAllTables);
        $firstTable = current($listOfAllTables);
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
                    if (!is_array($tableToUpdaterList[$table])) {
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
            $statement = $queryBuilder->execute();
            $rowCountWithoutUpdate = 0;
            while ($row = $rowBefore = $statement->fetch()) {
                foreach ($updaters as $updater) {
                    $row = $updater->updateTableRow($table, $row);
                }
                $updatedFields = array_diff_assoc($row, $rowBefore);
                if (empty($updatedFields)) {
                    // Updaters changed no field of that row
                    $rowCountWithoutUpdate ++;
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
                    if ($connectionForSysRegistry === $connectionForTable) {
                        // Target table and sys_registry table are on the same connection, use a transaction
                        $connectionForTable->beginTransaction();
                        try {
                            $connectionForTable->update(
                                $table,
                                $updatedFields,
                                [
                                    'uid' => $rowBefore['uid'],
                                ]
                            );
                            $connectionForTable->update(
                                'sys_registry',
                                [
                                    'entry_value' => serialize($startPosition),
                                ],
                                [
                                    'entry_namespace' => 'installUpdateRows',
                                    'entry_key' => 'rowUpdatePosition',
                                ]
                            );
                            $connectionForTable->commit();
                        } catch (\Exception $up) {
                            $connectionForTable->rollBack();
                            throw $up;
                        }
                    } else {
                        // Different connections for table and sys_registry -> execute two
                        // distinct queries and hope for the best.
                        $connectionForTable->update(
                            $table,
                            $updatedFields,
                            [
                                'uid' => $rowBefore['uid'],
                            ]
                        );
                        $connectionForSysRegistry->update(
                            'sys_registry',
                            [
                                'entry_value' => serialize($startPosition),
                            ],
                            [
                                'entry_namespace' => 'installUpdateRows',
                                'entry_key' => 'rowUpdatePosition',
                            ]
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
}
