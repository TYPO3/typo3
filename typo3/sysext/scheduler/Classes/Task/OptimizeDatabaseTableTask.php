<?php
namespace TYPO3\CMS\Scheduler\Task;

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
use Doctrine\DBAL\DBALException;
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

            if (strpos($connection->getServerVersion(), 'MySQL') === 0) {
                try {
                    $connection->exec('OPTIMIZE TABLE ' . $connection->quoteIdentifier($tableName));
                } catch (DBALException $e) {
                    throw new \RuntimeException(
                        TableGarbageCollectionTask::class . ' failed for: ' . $tableName . ': ' .
                        $e->getPrevious()->getMessage(),
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
}
