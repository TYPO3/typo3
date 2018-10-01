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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Merge data stored in sys_log that belongs to sys_history
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SeparateSysHistoryFromSysLogUpdate implements UpgradeWizardInterface
{
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
     */
    public function executeUpdate(): bool
    {
        // update "modify" statements (= decoupling)
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_history');
        $queryBuilder = $connection->createQueryBuilder();
        $rows = $queryBuilder
            ->select('sys_history.uid AS history_uid', 'sys_history.history_data', 'sys_log.*')
            ->from('sys_history')
            ->leftJoin(
                'sys_history',
                'sys_log',
                'sys_log',
                $queryBuilder->expr()->eq('sys_history.sys_log_uid', $queryBuilder->quoteIdentifier('sys_log.uid'))
            )
            ->execute()
            ->fetchAll();

        foreach ($rows as $row) {
            $logData = $row['log_data'] !== null ? unserialize($row['log_data'], ['allowed_classes' => false]) : [];
            $updateData = [
                'actiontype' => RecordHistoryStore::ACTION_MODIFY,
                'usertype' => 'BE',
                'userid' => $row['userid'],
                'sys_log_uid' => 0,
                'history_data' => json_encode($row['history_data'] !== null ? unserialize($row['history_data'], ['allowed_classes' => false]) : []),
                'originaluserid' => empty($logData['originalUser']) ? null : $logData['originalUser']
            ];
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
        }

        // Add insert/delete calls
        $logQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $result = $logQueryBuilder
            ->select('uid', 'userid', 'action', 'tstamp', 'log_data', 'tablename', 'recuid')
            ->from('sys_log')
            ->where(
                $logQueryBuilder->expr()->eq('type', $logQueryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                $logQueryBuilder->expr()->orX(
                    $logQueryBuilder->expr()->eq('action', $logQueryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $logQueryBuilder->expr()->eq('action', $logQueryBuilder->createNamedParameter(3, \PDO::PARAM_INT))
                )
            )
            ->orderBy('uid', 'DESC')
            ->execute();

        foreach ($result as $row) {
            $logData = unserialize($row['log_data']);
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
                    $store->addRecord($row['tablename'], $row['recuid'], $logData);
                    break;
                case 3:
                    // Delete
                    $store->deleteRecord($row['tablename'], $row['recuid']);
                    break;
            }
        }
        return true;
    }

    /**
     * Check if given field /column in a table exists
     *
     * @param string $table
     * @param string $fieldName
     * @return bool
     */
    protected function checkIfFieldInTableExists($table, $fieldName)
    {
        $tableColumns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->getSchemaManager()
            ->listTableColumns($table);
        return isset($tableColumns[$fieldName]);
    }
}
