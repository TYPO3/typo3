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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional BE fields for sys log table garbage collection task.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class TableGarbageCollectionAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * @var array Default number of days by table
     */
    protected $defaultNumberOfDays = [];

    /**
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param TableGarbageCollectionTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $this->initDefaultNumberOfDays();
        $additionalFields = [];
        $additionalFields['task_tableGarbageCollection_allTables'] = $this->getAllTablesAdditionalField($taskInfo, $task, $schedulerModule);
        $additionalFields['task_tableGarbageCollection_table'] = $this->getTableAdditionalField($taskInfo, $task, $schedulerModule);
        $additionalFields['task_tableGarbageCollection_numberOfDays'] = $this->getNumberOfDaysAdditionalField($taskInfo, $task, $schedulerModule);
        return $additionalFields;
    }

    /**
     * Initialize the default number of days for all configured tables
     */
    protected function initDefaultNumberOfDays()
    {
        $tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables'];
        foreach ($tableConfiguration as $tableName => $configuration) {
            if (isset($configuration['expirePeriod'])) {
                $this->defaultNumberOfDays[$tableName] = $configuration['expirePeriod'];
            }
        }
    }

    /**
     * Add a select field of available tables.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param TableGarbageCollectionTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getAllTablesAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        if ($currentSchedulerModuleAction->equals(Action::EDIT)) {
            $checked = $task->allTables === true ? 'checked="checked" ' : '';
        } else {
            $checked = '';
        }

        $fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_allTables]';
        $fieldId = 'task_tableGarbageCollection_allTables';
        $fieldHtml = '<div class="form-check"><input class="form-check-input" type="checkbox" ' . $checked . ' name="' . $fieldName . '" id="' . $fieldId . '"></div>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.allTables',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
        ];

        return $fieldConfiguration;
    }

    /**
     * Add a select field of available tables.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param TableGarbageCollectionTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getTableAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();
        $tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables'];
        $options = [];
        // Add an empty option on top if an existing task is configured
        // with a table that can not be found in configuration anymore
        if ($task && !array_key_exists($task->table, $tableConfiguration) && $currentSchedulerModuleAction->equals(Action::EDIT)) {
            $options[] = '<option value="" selected="selected"></option>';
        }
        foreach ($tableConfiguration as $tableName => $configuration) {
            if ($currentSchedulerModuleAction->equals(Action::ADD) && empty($options)) {
                // Select first table by default if adding a new task
                $options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
            } elseif ($task && $task->table === $tableName) {
                // Select currently selected table
                $options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
            } else {
                $options[] = '<option value="' . $tableName . '">' . $tableName . '</option>';
            }
        }
        $disabled = ($task && $task->allTables === true) ? ' disabled="disabled"' : '';
        $fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_table]';
        $fieldId = 'task_tableGarbageCollection_table';
        $fieldHtml = [];
        // Add table drop down html
        $fieldHtml[] = '<select class="form-select" name="' . $fieldName . '"' . $disabled . ' id="' . $fieldId . '">' . implode(LF, $options) . '</select>';
        // Add js array for default 'number of days' values
        $fieldHtml[] = '<script>/*<![CDATA[*/<!--';
        $fieldHtml[] = 'var defaultNumberOfDays = ' . json_encode($this->defaultNumberOfDays) . ';';
        $fieldHtml[] = '// -->/*]]>*/</script>';
        $fieldConfiguration = [
            'code' => implode(LF, $fieldHtml),
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.table',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
        ];
        return $fieldConfiguration;
    }

    /**
     * Add an input field to get the number of days.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param TableGarbageCollectionTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getNumberOfDaysAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();
        // Initialize selected fields
        $disabled = '';
        if (empty($taskInfo['scheduler_tableGarbageCollection_numberOfDays'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                // In case of new task, set to 180 days
                $taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = 180;
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                // In case of editing the task, set to currently selected value
                $taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = $task->numberOfDays;
                if ($task->numberOfDays === 0 && !isset($this->defaultNumberOfDays[$task->table])) {
                    $disabled = ' disabled="disabled"';
                }
            }
        }
        if ($task && $task->allTables === true) {
            $disabled = ' disabled="disabled"';
        }
        $fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_numberOfDays]';
        $fieldId = 'task_tableGarbageCollection_numberOfDays';
        $fieldHtml = '<input class="form-control" type="text" name="' . $fieldName . '" id="' . $fieldId . '"' . $disabled . ' value="' . (int)$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] . '" size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.numberOfDays',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
        ];
        return $fieldConfiguration;
    }

    /**
     * Validate additional fields
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        return $this->validateAllTablesAdditionalField($submittedData)
            && $this->validateTableAdditionalField($submittedData)
            && $this->validateNumberOfDaysAdditionalField($submittedData);
    }

    /**
     * Checks if all table field is correct
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if data is valid
     */
    public function validateAllTablesAdditionalField(array &$submittedData)
    {
        $validData = false;
        if (!isset($submittedData['scheduler_tableGarbageCollection_allTables'])) {
            $validData = true;
        } elseif ($submittedData['scheduler_tableGarbageCollection_allTables'] === 'on') {
            $validData = true;
        }
        return $validData;
    }

    /**
     * Checks given table for existence in configuration array
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if table exists in configuration, false otherwise
     */
    public function validateTableAdditionalField(array &$submittedData)
    {
        $validData = false;
        $tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables'];
        if (!isset($submittedData['scheduler_tableGarbageCollection_table'])) {
            $validData = true;
        } elseif (array_key_exists($submittedData['scheduler_tableGarbageCollection_table'], $tableConfiguration)) {
            $validData = true;
        }
        return $validData;
    }

    /**
     * Checks if given number of days is a positive integer
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateNumberOfDaysAdditionalField(array &$submittedData)
    {
        $validData = false;
        if (!isset($submittedData['scheduler_tableGarbageCollection_numberOfDays'])) {
            $validData = true;
        } elseif ((int)$submittedData['scheduler_tableGarbageCollection_numberOfDays'] >= 0) {
            $validData = true;
        } else {
            // Issue error message
            $this->addMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidNumberOfDays'), FlashMessage::ERROR);
        }
        return $validData;
    }

    /**
     * Save additional field in task
     *
     * @param array $submittedData Contains data submitted by the user
     * @param TableGarbageCollectionTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->allTables = $submittedData['scheduler_tableGarbageCollection_allTables'] === 'on';
        $task->table = $submittedData['scheduler_tableGarbageCollection_table'];
        $task->numberOfDays = (int)$submittedData['scheduler_tableGarbageCollection_numberOfDays'];
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
