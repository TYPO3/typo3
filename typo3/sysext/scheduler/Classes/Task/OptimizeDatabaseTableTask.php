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
            'tables' => $this->selectedTables,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $this->selectedTables = $parameters['tables'] ?? [];
    }
}
