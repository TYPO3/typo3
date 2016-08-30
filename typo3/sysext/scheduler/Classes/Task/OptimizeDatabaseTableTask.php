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

/**
 * Perform OPTIMIZE TABLE SQL statements
 *
 * This task reorganizes the physical storage of table data and associated index data,
 * to reduce storage space and improve I/O efficiency when accessing the table. The
 * exact changes made to each table depend on the storage engine used by that table.
 */
class OptimizeDatabaseTableTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
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
        foreach ($this->selectedTables as $tableName) {
            $result = $this->getDatabaseConnection()->admin_query('OPTIMIZE TABLE ' . $tableName . ';');
            if ($result === false) {
                throw new \RuntimeException(
                    \TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class . ' failed for: ' . $tableName . ': ' .
                    $this->getDatabaseConnection()->sql_error(),
                    1441390263
                );
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

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
