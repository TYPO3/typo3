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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional BE fields for optimize database table task.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class OptimizeDatabaseTableAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * @var string
     */
    protected $languageFile = 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf';

    /**
     * Add a multi select box with all available database tables.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        // Initialize selected fields
        if (empty($taskInfo['scheduler_optimizeDatabaseTables_selectedTables'])) {
            $taskInfo['scheduler_optimizeDatabaseTables_selectedTables'] = [];
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                // In case of new task, select no tables by default
                $taskInfo['scheduler_optimizeDatabaseTables_selectedTables'] = [];
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                // In case of editing the task, set to currently selected value
                $taskInfo['scheduler_optimizeDatabaseTables_selectedTables'] = $task->selectedTables;
            }
        }
        $fieldName = 'tx_scheduler[scheduler_optimizeDatabaseTables_selectedTables][]';
        $fieldId = 'scheduler_optimizeDatabaseTables_selectedTables';
        $fieldOptions = $this->getDatabaseTableOptions($taskInfo['scheduler_optimizeDatabaseTables_selectedTables']);
        $fieldHtml = '<select class="form-control" name="' . $fieldName
            . '" id="' . $fieldId
            . '" class="from-control" size="10" multiple="multiple">'
            . $fieldOptions
            . '</select>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => $this->languageFile . ':label.optimizeDatabaseTables.selectTables',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
        ];

        return $additionalFields;
    }

    /**
     * Checks that all selected backends exist in available backend list
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validData = true;
        $availableTables = $this->getOptimizableTables();
        if (is_array($submittedData['scheduler_optimizeDatabaseTables_selectedTables'])) {
            $invalidTables = array_diff(
                $submittedData['scheduler_optimizeDatabaseTables_selectedTables'],
                $availableTables
            );
            if (!empty($invalidTables)) {
                $this->addMessage(
                    $GLOBALS['LANG']->sL($this->languageFile . ':msg.selectionOfNonExistingDatabaseTables'),
                    FlashMessage::ERROR
                );
                $validData = false;
            }
        } else {
            $this->addMessage(
                $GLOBALS['LANG']->sL($this->languageFile . ':msg.noDatabaseTablesSelected'),
                FlashMessage::ERROR
            );
            $validData = false;
        }

        return $validData;
    }

    /**
     * Save selected backends in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->selectedTables = $submittedData['scheduler_optimizeDatabaseTables_selectedTables'];
    }

    /**
     * Build select options of available backends and set currently selected backends
     *
     * @param array $selectedTables Selected backends
     * @return string HTML of selectbox options
     */
    protected function getDatabaseTableOptions(array $selectedTables)
    {
        $options = [];
        $availableTables = $this->getOptimizableTables();
        foreach ($availableTables as $tableName) {
            $selected = in_array($tableName, $selectedTables, true) ? ' selected="selected"' : '';
            $options[] = '<option value="' . $tableName . '"' . $selected . '>' . $tableName . '</option>';
        }

        return implode('', $options);
    }

    /**
     * Get all tables that are capable of optimization
     *
     * @return array Names of table that can be optimized.
     */
    protected function getOptimizableTables()
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
                    function ($value) use ($connectionName) {
                        return $value === $connectionName;
                    }
                ));
                $tables = $this->getOptimizableTablesForConnection($connection, $tablesOnConnection);
                $optimizableTables = array_merge($optimizableTables, $tables);
            }
        }

        sort($optimizableTables);

        return $optimizableTables;
    }

    /**
     * Retrieve all optimizable tables for a connection, optionally restricted to the subset
     * of table names in the $tableNames array.
     *
     * @param Connection $connection
     * @param array $tableNames
     * @return array
     */
    protected function getOptimizableTablesForConnection(Connection $connection, array $tableNames = []): array
    {
        // Return empty list if the database platform is not MySQL
        if (strpos($connection->getServerVersion(), 'MySQL') !== 0) {
            return [];
        }

        // Retrieve all tables from the MySQL informaation schema that have an engine type
        // that supports the OPTIMIZE TABLE command.
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('TABLE_NAME AS Table', 'ENGINE AS Engine')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq(
                    'TABLE_TYPE',
                    $queryBuilder->createNamedParameter('BASE TABLE', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->in(
                    'ENGINE',
                    $queryBuilder->createNamedParameter(['InnoDB', 'MyISAM', 'ARCHIVE'], Connection::PARAM_STR_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    'TABLE_SCHEMA',
                    $queryBuilder->createNamedParameter($connection->getDatabase(), \PDO::PARAM_STR)
                )
            );

        if (!empty($tableNames)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    'TABLE_NAME',
                    $queryBuilder->createNamedParameter($tableNames, \PDO::PARAM_STR)
                )
            );
        }

        $tables = $queryBuilder->execute()->fetchAll();

        return array_column($tables, 'Table');
    }
}
