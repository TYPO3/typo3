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
 * Remove old entries from tables.
 *
 * This task deletes rows from tables older than the given number of days.
 *
 * Available tables must be registered in
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']
 * See ext_localconf.php of scheduler extension for an example
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class TableGarbageCollectionTask extends AbstractTask
{
    /**
     * @var bool True if all tables should be cleaned up
     */
    public $allTables = false;

    /**
     * @var int Number of days
     */
    public $numberOfDays = 180;

    /**
     * @var string Table to clean up
     */
    public $table = '';

    /**
     * Execute garbage collection, called by scheduler.
     *
     * @throws \RuntimeException If configured table was not cleaned up
     * @return bool TRUE if task run was successful
     */
    public function execute()
    {
        $tableConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][self::class]['options']['tables'];
        $tableHandled = false;
        foreach ($tableConfigurations as $tableName => $configuration) {
            if ($this->allTables || $tableName === $this->table) {
                $this->handleTable($tableName, $configuration);
                $tableHandled = true;
            }
        }
        if (!$tableHandled) {
            throw new \RuntimeException(self::class . ' misconfiguration: ' . $this->table . ' does not exist in configuration', 1308354399);
        }
        return true;
    }

    /**
     * Execute clean up of a specific table
     *
     * @throws \RuntimeException If table configuration is broken
     * @param string $table The table to handle
     * @param array $configuration Clean up configuration
     * @return bool TRUE if cleanup was successful
     */
    protected function handleTable($table, array $configuration)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->delete($table);
        if (!empty($configuration['expireField'])) {
            $field = $configuration['expireField'];
            $dateLimit = $GLOBALS['EXEC_TIME'];
            // If expire field value is 0, do not delete
            // Expire field = 0 means no expiration
            $queryBuilder->where(
                $queryBuilder->expr()->lte($field, $queryBuilder->createNamedParameter($dateLimit, \PDO::PARAM_INT)),
                $queryBuilder->expr()->gt($field, $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );
        } elseif (!empty($configuration['dateField'])) {
            if (!$this->allTables) {
                $deleteTimestamp = strtotime('-' . $this->numberOfDays . 'days');
            } else {
                if (!isset($configuration['expirePeriod'])) {
                    throw new \RuntimeException(self::class . ' misconfiguration: No expirePeriod defined for table ' . $table, 1308355095);
                }
                $deleteTimestamp = strtotime('-' . $configuration['expirePeriod'] . 'days');
            }
            $queryBuilder->where(
                $queryBuilder->expr()->lt(
                    $configuration['dateField'],
                    $queryBuilder->createNamedParameter($deleteTimestamp, \PDO::PARAM_INT)
                )
            );
        } else {
            throw new \RuntimeException(self::class . ' misconfiguration: Either expireField or dateField must be defined for table ' . $table, 1308355268);
        }

        try {
            $queryBuilder->execute();
        } catch (DBALException $e) {
            throw new \RuntimeException(self::class . ' failed for table ' . $this->table . ' with error: ' . $e->getMessage(), 1308255491);
        }
        return true;
    }

    /**
     * This method returns the selected table as additional information
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        if ($this->allTables) {
            $message = $GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.additionalInformationAllTables');
        } else {
            $message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.additionalInformationTable'), $this->table);
        }
        return $message;
    }
}
