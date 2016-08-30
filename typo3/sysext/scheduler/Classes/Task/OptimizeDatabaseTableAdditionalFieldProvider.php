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
 * Additional BE fields for optimize database table task.
 */
class OptimizeDatabaseTableAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Add a multi select box with all available database tables.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|NULL $task When editing, reference to the current task. NULL when adding.
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        // Initialize selected fields
        if (empty($taskInfo['scheduler_optimizeDatabaseTables_selectedTables'])) {
            $taskInfo['scheduler_optimizeDatabaseTables_selectedTables'] = [];
            if ($parentObject->CMD === 'add') {
                // In case of new task, select no tables by default
                $taskInfo['scheduler_optimizeDatabaseTables_selectedTables'] = [];
            } elseif ($parentObject->CMD === 'edit') {
                // In case of editing the task, set to currently selected value
                $taskInfo['scheduler_optimizeDatabaseTables_selectedTables'] = $task->selectedTables;
            }
        }
        $fieldName = 'tx_scheduler[scheduler_optimizeDatabaseTables_selectedTables][]';
        $fieldId = 'scheduler_optimizeDatabaseTables_selectedTables';
        $fieldOptions = $this->getDatabaseTableOptions($taskInfo['scheduler_optimizeDatabaseTables_selectedTables']);
        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '" class="from-control" size="10" multiple="multiple">' . $fieldOptions . '</select>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.optimizeDatabaseTables.selectTables',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $additionalFields;
    }

    /**
     * Checks that all selected backends exist in available backend list
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = true;
        $availableTables = array_keys($this->getDatabaseTables());
        if (is_array($submittedData['scheduler_optimizeDatabaseTables_selectedTables'])) {
            $invalidTables = array_diff($submittedData['scheduler_optimizeDatabaseTables_selectedTables'], $availableTables);
            if (!empty($invalidTables)) {
                $parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.selectionOfNonExistingDatabaseTables'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
                $validData = false;
            }
        } else {
            $parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noDatabaseTablesSelected'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
            $validData = false;
        }
        return $validData;
    }

    /**
     * Save selected backends in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
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
        $availableTables = $this->getDatabaseTables();
        foreach ($availableTables as $tableName => $tableInformation) {
            $selected = in_array($tableName, $selectedTables, true) ? ' selected="selected"' : '';
            $options[] = '<option value="' . $tableName . '"' . $selected . '>' . $tableName . '</option>';
        }
        return implode('', $options);
    }

    /**
     * Get all registered caching framework backends
     *
     * @return array Registered backends
     */
    protected function getDatabaseTables()
    {
        $tables =  $this->getDatabaseConnection()->admin_get_tables();
        $tables = array_filter(
            $tables,
            function ($table) {
                return !empty($table['Engine']) && in_array($table['Engine'], ['MyISAM', 'InnoDB', 'ARCHIVE']);
            }
        );
        return $tables;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
