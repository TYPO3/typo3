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

namespace TYPO3\CMS\Scheduler\Task;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Perform OPTIMIZE TABLE SQL statements
 *
 * This task reorganizes the physical storage of table data and associated index data,
 * to reduce storage space and improve I/O efficiency when accessing the table. The
 * exact changes made to each table depend on the storage engine used by that table.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class OptimizeDatabaseTableTask extends AbstractTask
{
    /**
     * Database tables that should be cleaned up,
     * set by additional field provider.
     *
     * @var array Selected tables to optimize
     */
    public $selectedTables = [];

    /**
     * Execute table optimization, called by scheduler.
     *
     * @return bool
     */
    public function execute()
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($this->selectedTables as $tableName) {
            $connection = $connectionPool->getConnectionForTable($tableName);
            $platform = $connection->getDatabasePlatform();

            if ($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform) {
                try {
                    // `OPTIMIZE TABLE` returns a result set and must be executed using `executeQuery()`,
                    // otherwise following database queries would fail with a database exception because of a
                    // not-consumed query buffer with `pdo_mysql` driver and the full result set is retrieved
                    // with `fetchAllAssociative()` and discarded as handling is not intended here.
                    $connection->executeQuery('OPTIMIZE TABLE ' . $connection->quoteIdentifier($tableName))->fetchAllAssociative();
                } catch (DBALException $e) {
                    throw new \RuntimeException(
                        TableGarbageCollectionTask::class . ' failed for: ' . $tableName . ': ' .
                        $e->getMessage(),
                        1441390263
                    );
                }
            }
        }

        return true;
    }

    /**
     * Output the selected tables
     *
     * @return string
     */
    public function getAdditionalInformation()
    {
        return implode(', ', $this->selectedTables);
    }

    public function getTaskParameters(): array
    {
        return [
            'selected_tables' => implode(',', $this->selectedTables),
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $selectedTables = $parameters['selected_tables'] ?? $parameters['tables'] ?? [];
        if (!is_array($selectedTables)) {
            $selectedTables = GeneralUtility::trimExplode(',', $selectedTables, true);
        }
        $this->selectedTables = $selectedTables;
    }

    /**
     * TCA itemsProcFunc
     * Get all tables that are capable of optimization
     */
    public function getOptimizableTables(array &$config): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        // Retrieve all optimizable tables for the default connection
        $optimizableTables = $this->getOptimizableTablesForConnection($defaultConnection);

        // Retrieve additional optimizable tables that have been remapped to a different connection
        $tableMap = $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'] ?? [];
        if ($tableMap) {
            // Remove all remapped tables from the list of optimizable tables
            // These tables will be rechecked and possibly re-added to the list
            // of optimizable tables. This ensures that no orphaned table from
            // the default connection gets mistakenly labeled as optimizable.
            $optimizableTables = array_diff($optimizableTables, array_keys($tableMap));

            // Walk each connection and check all tables that have been
            // remapped to it for optimization support.
            $connectionNames = array_keys(array_flip($tableMap));
            foreach ($connectionNames as $connectionName) {
                $connection = $connectionPool->getConnectionByName($connectionName);
                $tablesOnConnection = array_keys(array_filter(
                    $tableMap,
                    static function ($value) use ($connectionName) {
                        return $value === $connectionName;
                    }
                ));
                $tables = $this->getOptimizableTablesForConnection($connection, $tablesOnConnection);
                $optimizableTables = array_merge($optimizableTables, $tables);
            }
        }

        sort($optimizableTables);
        foreach ($optimizableTables as $tableName) {
            $config['items'][] = [
                'label' => $tableName,
                'value' => $tableName,
            ];
        }
        return $optimizableTables;
    }

    /**
     * Retrieve all optimizable tables for a connection, optionally restricted to the subset
     * of table names in the $tableNames array.
     */
    protected function getOptimizableTablesForConnection(Connection $connection, array $tableNames = []): array
    {
        // Return empty list if the database platform is not MySQL/MariaDB
        $platform = $connection->getDatabasePlatform();
        if (!($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform)) {
            return [];
        }

        // Retrieve all tables from the MySQL information schema that have an engine type
        // that supports the OPTIMIZE TABLE command.
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('TABLE_NAME AS Table', 'ENGINE AS Engine')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq(
                    'TABLE_TYPE',
                    $queryBuilder->createNamedParameter('BASE TABLE')
                ),
                $queryBuilder->expr()->in(
                    'ENGINE',
                    $queryBuilder->createNamedParameter(['InnoDB', 'MyISAM', 'ARCHIVE'], Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'TABLE_SCHEMA',
                    $queryBuilder->createNamedParameter($connection->getDatabase())
                )
            );

        if (!empty($tableNames)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'TABLE_NAME',
                    $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY)
                )
            );
        }

        $tables = $queryBuilder->executeQuery()->fetchAllAssociative();

        return array_column($tables, 'Table');
    }
}
