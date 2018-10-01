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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional BE fields for ip address anonymization task.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class IpAnonymizationAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];
        $additionalFields['task_ipAnonymization_table'] = $this->getTableAdditionalField($taskInfo, $task, $schedulerModule);
        $additionalFields['task_ipAnonymization_numberOfDays'] = $this->getNumberOfDaysAdditionalField($taskInfo, $task, $schedulerModule);
        $additionalFields['task_ipAnonymization_mask'] = $this->getMaskAdditionalField($taskInfo, $task, $schedulerModule);
        return $additionalFields;
    }

    /**
     * Add a select field of available tables.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getTableAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();
        $tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][IpAnonymizationTask::class]['options']['tables'];
        $options = [];
        // Add an empty option on top if an existing task is configured
        // with a table that can not be found in configuration anymore
        if (!array_key_exists($task->table, $tableConfiguration) && $currentSchedulerModuleAction->equals(Action::EDIT)) {
            $options[] = '<option value="" selected="selected"></option>';
        }
        foreach ($tableConfiguration as $tableName => $configuration) {
            if ($currentSchedulerModuleAction->equals(Action::ADD) && empty($options)) {
                // Select first table by default if adding a new task
                $options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
            } elseif ($task->table === $tableName) {
                // Select currently selected table
                $options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
            } else {
                $options[] = '<option value="' . $tableName . '">' . $tableName . '</option>';
            }
        }
        $fieldName = 'tx_scheduler[scheduler_ipAnonymization_table]';
        $fieldId = 'task_ipAnonymization_table';
        $fieldHtml = [];
        // Add table drop down html
        $fieldHtml[] = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">' . implode(LF, $options) . '</select>';
        $fieldConfiguration = [
            'code' => implode(LF, $fieldHtml),
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.table',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * Add an input field to get the number of days.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getNumberOfDaysAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $fieldId = 'scheduler_ipAnonymization_numberOfDays';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = $task->numberOfDays ?? 180;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . (int)$taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.numberOfDays',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * Add an input field to get the mask.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getMaskAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $fieldId = 'scheduler_ipAnonymization_mask';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = $task->mask ?? 2;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';

        $fieldHtml = '';
        foreach ([1, 2] as $mask) {
            $selected = (int)$taskInfo[$fieldId] === $mask ? ' selected' : '';
            $fieldHtml .= '<option value="' . $mask . '"' . $selected . '>'
                . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.mask.' . $mask))
                . '</option>';
        }

        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" ' . 'id="' . $fieldId . '">' . $fieldHtml . '</select>';

        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.mask',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
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
        $validData = $this->validateTableAdditionalField($submittedData, $schedulerModule);
        $validData &= $this->validateNumberOfDaysAdditionalField($submittedData, $schedulerModule);
        return $validData;
    }

    /**
     * Checks given table for existence in configuration array
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool True if table exists in configuration, false otherwise
     */
    public function validateTableAdditionalField(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validData = false;
        $tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][IpAnonymizationTask::class]['options']['tables'];
        if (!isset($submittedData['scheduler_ipAnonymization_table'])) {
            $validData = true;
        } elseif (array_key_exists($submittedData['scheduler_ipAnonymization_table'], $tableConfiguration)) {
            $validData = true;
        }
        return $validData;
    }

    /**
     * Checks if given number of days is a positive integer
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateNumberOfDaysAdditionalField(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validData = false;
        if (!isset($submittedData['scheduler_ipAnonymization_numberOfDays'])) {
            $validData = true;
        } elseif ((int)$submittedData['scheduler_ipAnonymization_numberOfDays'] >= 0) {
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->table = $submittedData['scheduler_ipAnonymization_table'];
        $task->mask = (int)$submittedData['scheduler_ipAnonymization_mask'];
        $task->numberOfDays = (int)$submittedData['scheduler_ipAnonymization_numberOfDays'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
