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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Merge data stored in sys_log that belongs to sys_history
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SeparateSysHistoryFromSysLogUpdate implements UpgradeWizardInterface, RepeatableInterface
{

    /** @var int Number of records to process in a single query to reduce memory footprint */
    private const BATCH_SIZE = 100;

    /** @var int Phase that copies data from sys_log to sys_history */
    private const MOVE_DATA = 0;

    /** @var int Phase that adds history records for inserts and deletes */
    private const UPDATE_HISTORY = 1;

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'separateSysHistoryFromLog';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrates existing sys_log entries into sys_history';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'The history of changes of a record is now solely stored within sys_history.'
            . ' Previous data within sys_log needs to be migrated into sys_history now.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (true) or not (false)
     */
    public function updateNecessary(): bool
    {
        // sys_log field has been removed, no need to do something.
        if (!$this->checkIfFieldInTableExists('sys_history', 'sys_log_uid')) {
            return false;
        }

        // Check if there is data to migrate
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_history');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder->count('*')
            ->from('sys_history')
            ->where($queryBuilder->expr()->neq('sys_log_uid', 0))
            ->execute()
            ->fetchColumn(0);

        return $count > 0;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Moves data from sys_log into sys_history
     * where a reference is still there: sys_history.sys_log_uid > 0
     *
     * @return bool
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function executeUpdate(): bool
    {
        // If rows from the target table that is updated and the sys_registry table are on the
        // same connection, the update statement and sys_registry position update will be
        // handled in a transaction to have an atomic operation in case of errors during execution.
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_history');
        $connectionForSysRegistry = $connectionPool->getConnectionForTable('sys_registry');

        // In case the PHP ended for whatever reason, fetch the last position from registry
        // and only execute the phase(s) that has/have not been executed yet
        $startPositionAndPhase = $this->getStartPositionAndPhase();

        if ($startPositionAndPhase['phase'] === self::MOVE_DATA) {
            $startPositionAndPhase = $this->moveDataFromSysLogToSysHistory(
                $connection,
                $connectionForSysRegistry,
                $startPositionAndPhase
            );
        }

        if ($startPositionAndPhase['phase'] === self::UPDATE_HISTORY) {
            $this->keepHistoryForInsertAndDeleteActions(
                $connectionForSysRegistry,
                $startPositionAndPhase
            );
        }

        return true;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param \TYPO3\CMS\Core\Database\Connection $connectionForSysRegistry
     * @param array $startPositionAndPhase
     * @return array
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    protected function moveDataFromSysLogToSysHistory(
        Connection $connection,
        Connection $connectionForSysRegistry,
        array $startPositionAndPhase
    ): array {
        do {
            $processedRows = 0;

            // update "modify" statements (= decoupling)
            $queryBuilder = $connection->createQueryBuilder();
            $rows = $queryBuilder->select('sys_history.uid AS history_uid', 'sys_history.history_data', 'sys_log.*')
                ->from('sys_history')
                ->leftJoin(
                    'sys_history',
                    'sys_log',
                    'sys_log',
                    $queryBuilder->expr()->eq('sys_history.sys_log_uid', $queryBuilder->quoteIdentifier('sys_log.uid'))
                )
                ->where($queryBuilder->expr()->gt('sys_history.uid', $queryBuilder->createNamedParameter($startPositionAndPhase['uid'])))
                ->setMaxResults(self::BATCH_SIZE)
                ->orderBy('sys_history.uid', 'ASC')
                ->execute()
                ->fetchAll();

            foreach ($rows as $row) {
                $logData = $this->unserializeToArray((string)($row['log_data'] ?? ''));
                $historyData = $this->unserializeToArray((string)($row['history_data'] ?? ''));
                $updateData = [
                    'actiontype' => RecordHistoryStore::ACTION_MODIFY,
                    'usertype' => 'BE',
                    'userid' => $row['userid'],
                    'sys_log_uid' => 0,
                    'history_data' => json_encode($historyData),
                    'originaluserid' => empty($logData['originalUser']) ? null : $logData['originalUser']
                ];

                if ($connection === $connectionForSysRegistry) {
                    // sys_history and sys_registry tables are on the same connection, use a transaction
                    $connection->beginTransaction();
                    try {
                        $startPositionAndPhase = $this->updateTablesAndTrackProgress(
                            $connection,
                            $connection,
                            $updateData,
                            $logData,
                            $row
                        );
                        $connection->commit();
                    } catch (\Exception $up) {
                        $connection->rollBack();
                        throw ($up);
                    }
                } else {
                    // Different connections for sys_history and sys_registry -> execute two
                    // distinct queries and hope for the best.
                    $startPositionAndPhase = $this->updateTablesAndTrackProgress(
                        $connection,
                        $connectionForSysRegistry,
                        $updateData,
                        $logData,
                        $row
                    );
                }

                $processedRows++;
            }
            // repeat until a resultset smaller than the batch size was processed
        } while ($processedRows === self::BATCH_SIZE);

        // phase 0 is finished
        $registry = GeneralUtility::makeInstance(Registry::class);
        $startPositionAndPhase = [
            'phase' => self::UPDATE_HISTORY,
            'uid' => 0,
        ];
        $registry->set('installSeparateHistoryFromSysLog', 'phaseAndPosition', $startPositionAndPhase);

        return $startPositionAndPhase;
    }

    /**
     * Update sys_history and sys_log tables
     *
     * Also keep track of progress in sys_registry
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     * @param \TYPO3\CMS\Core\Database\Connection $connectionForSysRegistry
     * @param array $updateData
     * @param array $logData
     * @param array $row
     * @return array
     */
    protected function updateTablesAndTrackProgress(
        Connection $connection,
        Connection $connectionForSysRegistry,
        array $updateData,
        array $logData,
        array $row
    ): array {
        $connection->update(
            'sys_history',
            $updateData,
            ['uid' => (int)$row['history_uid']],
            ['uid' => Connection::PARAM_INT]
        );

        // Store information about history entry in sys_log table
        $logData['history'] = $row['history_uid'];
        $connection->update(
            'sys_log',
            ['log_data' => serialize($logData)],
            ['uid' => (int)$row['uid']],
            ['uid' => Connection::PARAM_INT]
        );
        $startPositionAndPhase = [
            'phase' => self::MOVE_DATA,
            'uid' => $row['history_uid'],
        ];
        $connectionForSysRegistry->update(
            'sys_registry',
            [
                'entry_value' => serialize($startPositionAndPhase)
            ],
            [
                'entry_namespace' => 'installSeparateHistoryFromSysLog',
                'entry_key' => 'phaseAndPosition',
            ]
        );

        return $startPositionAndPhase;
    }

    /**
     * Add Insert and Delete actions from sys_log to sys_history
     *
     * @param \TYPO3\CMS\Core\Database\Connection $connectionForSysRegistry
     * @param array $startPositionAndPhase
     */
    protected function keepHistoryForInsertAndDeleteActions(
        Connection $connectionForSysRegistry,
        array $startPositionAndPhase
    ) {
        do {
            $processedRows = 0;

            // Add insert/delete calls
            $logQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
            $result = $logQueryBuilder->select('uid', 'userid', 'action', 'tstamp', 'log_data', 'tablename', 'recuid')
                ->from('sys_log')
                ->where(
                    $logQueryBuilder->expr()->eq('type', $logQueryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $logQueryBuilder->expr()->orX(
                        $logQueryBuilder->expr()->eq('action', $logQueryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                        $logQueryBuilder->expr()->eq('action', $logQueryBuilder->createNamedParameter(3, \PDO::PARAM_INT))
                    )
                )
                ->andWhere(
                    $logQueryBuilder->expr()->gt('uid', $logQueryBuilder->createNamedParameter($startPositionAndPhase['uid']))
                )
                ->orderBy('uid', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->execute();

            foreach ($result as $row) {
                $logData = $this->unserializeToArray((string)($row['log_data'] ?? ''));
                $store = GeneralUtility::makeInstance(
                    RecordHistoryStore::class,
                    RecordHistoryStore::USER_BACKEND,
                    $row['userid'],
                    (empty($logData['originalUser']) ? null : $logData['originalUser']),
                    $row['tstamp']
                );

                switch ($row['action']) {
                    // Insert
                    case 1:
                        $store->addRecord($row['tablename'], (int)$row['recuid'], $logData);
                        break;
                    // Delete
                    case 3:
                        $store->deleteRecord($row['tablename'], (int)$row['recuid']);
                        break;
                }

                $startPositionAndPhase = [
                    'phase' => self::UPDATE_HISTORY,
                    'uid' => $row['uid'],
                ];
                $connectionForSysRegistry->update(
                    'sys_registry',
                    [
                        'entry_value' => serialize($startPositionAndPhase)
                    ],
                    [
                        'entry_namespace' => 'installSeparateHistoryFromSysLog',
                        'entry_key' => 'phaseAndPosition',
                    ]
                );

                $processedRows++;
            }
            // repeat until a result set smaller than the batch size was processed
        } while ($processedRows === self::BATCH_SIZE);
    }

    /**
     * Checks if given field /column in a table exists
     *
     * @param string $table
     * @param string $fieldName
     * @return bool
     */
    protected function checkIfFieldInTableExists($table, $fieldName): bool
    {
        $tableColumns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->getSchemaManager()
            ->listTableColumns($table);
        return isset($tableColumns[$fieldName]);
    }

    /**
     * Returns an array with phase / uid combination that specifies the start position the
     * update process should start with.
     *
     * @return array New start position
     */
    protected function getStartPositionAndPhase(): array
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $startPosition = $registry->get('installSeparateHistoryFromSysLog', 'phaseAndPosition', []);
        if (empty($startPosition)) {
            $startPosition = [
                'phase' => self::MOVE_DATA,
                'uid' => 0,
            ];
            $registry->set('installSeparateHistoryFromSysLog', 'phaseAndPosition', $startPosition);
        }

        return $startPosition;
    }

    protected function unserializeToArray(string $serialized): array
    {
        $unserialized = unserialize($serialized, ['allowed_classes' => false]);
        return is_array($unserialized) ? $unserialized : [];
    }
}
