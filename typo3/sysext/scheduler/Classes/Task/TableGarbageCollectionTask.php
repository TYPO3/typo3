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

namespace TYPO3\CMS\Scheduler\Task;

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Remove old entries from tables.
 *
 * This task deletes rows from tables older than the given number of days.
 *
 * Available tables must be registered in
 * $GLOBALS['TCA']['tx_scheduler_task']['types'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['taskOptions']['tables']
 *
 * See scheduler_table_garbage_collection_task.php of scheduler extension for an example.
 *
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
    public $numberOfDays = 0;

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
        $tableConfigurations = $this->getTableConfiguration();
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
    protected function handleTable(string $table, array $configuration): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->delete($table);
        if (!empty($configuration['expireField'])) {
            $field = $configuration['expireField'];
            $dateLimit = $GLOBALS['EXEC_TIME'];
            // If expire field value is 0, do not delete
            // Expire field = 0 means no expiration
            $queryBuilder->where(
                $queryBuilder->expr()->lte($field, $queryBuilder->createNamedParameter($dateLimit, Connection::PARAM_INT)),
                $queryBuilder->expr()->gt($field, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
        } elseif (!empty($configuration['dateField'])) {
            if (!$this->allTables) {
                $numberOfDays = $this->numberOfDays;
                if (isset($configuration['expirePeriod']) && $numberOfDays <= 0) {
                    $numberOfDays = (int)$configuration['expirePeriod'];
                }
                $deleteTimestamp = strtotime('-' . $numberOfDays . 'days');
            } else {
                if (!isset($configuration['expirePeriod'])) {
                    throw new \RuntimeException(self::class . ' misconfiguration: No expirePeriod defined for table ' . $table, 1308355095);
                }
                $deleteTimestamp = strtotime('-' . $configuration['expirePeriod'] . 'days');
            }
            $queryBuilder->where(
                $queryBuilder->expr()->lt(
                    $configuration['dateField'],
                    $queryBuilder->createNamedParameter($deleteTimestamp, Connection::PARAM_INT)
                )
            );
        } else {
            throw new \RuntimeException(self::class . ' misconfiguration: Either expireField or dateField must be defined for table ' . $table, 1308355268);
        }

        try {
            $queryBuilder->executeStatement();
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
            $message = $this->getLanguageService()?->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.additionalInformationAllTables');
        } else {
            $message = sprintf($this->getLanguageService()?->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.additionalInformationTable'), $this->table);
        }
        return $message;
    }

    public function getTaskParameters(): array
    {
        return [
            'all_tables' => $this->allTables,
            'number_of_days' => $this->numberOfDays,
            'selected_tables' => $this->table,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $this->allTables = (bool)($parameters['allTables'] ?? $parameters['all_tables'] ?? false);
        $this->numberOfDays = (int)($parameters['numberOfDays'] ?? $parameters['number_of_days'] ?? 0);
        $this->table = (string)($parameters['table'] ?? $parameters['selected_tables'] ?? '');
    }

    public function getCleanableTables(array &$config): void
    {
        foreach ($this->getTableConfiguration() as $tableName => $tableConfiguration) {
            $config['items'][] = [
                'label' => $tableName . (($tableConfiguration['expirePeriod'] ?? false) ? ' [expirePeriod: ' . $tableConfiguration['expirePeriod'] . ']' : '') . (($tableConfiguration['dateField'] ?? false) ? ' [dateField: ' . $tableConfiguration['dateField'] . ']' : ''),
                'value' => $tableName,
            ];
        }
    }

    public function getTableConfiguration(): array
    {
        $tableConfiguration = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('tx_scheduler_task.' . self::class)->getRawConfiguration()['taskOptions']['tables'] ?? [];

        $tableConfigurationFromConfVars = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][self::class]['options']['tables'] ?? [];
        if (!empty($tableConfigurationFromConfVars)) {
            trigger_error('Usage of $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'scheduler\'][\'tasks\'][' . self::class . '][\'options\'][\'tables\'] to define table options is deprecated and will stop working in TYPO3 v15. Use $tca[\'tx_scheduler_task\'][\'types\'][' . self::class . '][\'taskOptions\'][\'tables\'] instead.', E_USER_DEPRECATED);
            if (is_array($tableConfigurationFromConfVars)) {
                $tableConfiguration = array_replace_recursive($tableConfiguration, $tableConfigurationFromConfVars);
            }
        }

        return $tableConfiguration;
    }
}
