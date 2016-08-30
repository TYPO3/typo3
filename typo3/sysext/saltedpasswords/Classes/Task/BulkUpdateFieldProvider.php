<?php
namespace TYPO3\CMS\Saltedpasswords\Task;

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
 * Additional field for salted passwords bulk update task
 */
class BulkUpdateFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Default value whether the task deactivates itself after last run.
     *
     * @var bool Whether the task is allowed to deactivate itself after processing all existing user records.
     */
    protected $defaultCanDeactivateSelf = true;

    /**
     * Default value for the number of records to handle at each run.
     *
     * @var int Number of records
     */
    protected $defaultNumberOfRecords = 250;

    /**
     * Add a field for the number of records and if the task should deactivate itself after
     * processing all records
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param \TYPO3\CMS\Saltedpasswords\Task\BulkUpdateTask $task When editing, reference to the current task object. Null when adding.
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        // Initialize selected fields
        if (!isset($taskInfo['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'])) {
            $taskInfo['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'] = $this->defaultCanDeactivateSelf;
            if ($parentObject->CMD === 'edit') {
                $taskInfo['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'] = $task->getCanDeactivateSelf();
            }
        }
        if (!isset($taskInfo['scheduler_saltedpasswordsBulkUpdateNumberOfRecords'])) {
            $taskInfo['scheduler_saltedpasswordsBulkUpdateNumberOfRecords'] = $this->defaultNumberOfRecords;
            if ($parentObject->CMD === 'edit') {
                $taskInfo['scheduler_saltedpasswordsBulkUpdateNumberOfRecords'] = $task->getNumberOfRecords();
            }
        }
        // Configuration for canDeactivateSelf
        $fieldName = 'tx_scheduler[scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf]';
        $fieldId = 'task_saltedpasswordsBulkUpdateCanDeactivateSelf';
        $fieldValue = 'IsChecked';
        $fieldChecked = (bool)$taskInfo['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'];
        $fieldHtml = '<div class="checkbox"><label><input type="checkbox"' . ' name="' . $fieldName . '"' . ' id="' . $fieldId . '"' . ' value="' . $fieldValue . '"' . ($fieldChecked ? ' checked="checked"' : '') . '></label></div>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:saltedpasswords/Resources/Private/Language/locallang.xlf:ext.saltedpasswords.tasks.bulkupdate.label.canDeactivateSelf',
            'cshKey' => '_txsaltedpasswords',
            'cshLabel' => $fieldId
        ];
        // Configuration for numberOfRecords
        $fieldName = 'tx_scheduler[scheduler_saltedpasswordsBulkUpdateNumberOfRecords]';
        $fieldId = 'task_saltedpasswordsBulkUpdateNumberOfRecords';
        $fieldValue = (int)$taskInfo['scheduler_saltedpasswordsBulkUpdateNumberOfRecords'];
        $fieldHtml = '<input type="text" class="form-control" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '">';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:saltedpasswords/Resources/Private/Language/locallang.xlf:ext.saltedpasswords.tasks.bulkupdate.label.numberOfRecords',
            'cshKey' => '_txsaltedpasswords',
            'cshLabel' => $fieldId
        ];
        return $additionalFields;
    }

    /**
     * Checks if the given values are boolean and integer
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $result = true;
        // Check if number of records is indeed a number and greater or equals to 0
        // If not, fail validation and issue error message
        if (!is_numeric($submittedData['scheduler_saltedpasswordsBulkUpdateNumberOfRecords']) || (int)$submittedData['scheduler_saltedpasswordsBulkUpdateNumberOfRecords'] < 0) {
            $result = false;
            $parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:saltedpasswords/Resources/Private/Language/locallang.xlf:ext.saltedpasswords.tasks.bulkupdate.invalidNumberOfRecords'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }
        return $result;
    }

    /**
     * Saves given values in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|\TYPO3\CMS\Saltedpasswords\Task\BulkUpdateTask $task Reference to the current task object
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        if (isset($submittedData['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf']) && $submittedData['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'] === 'IsChecked') {
            $task->setCanDeactivateSelf(true);
        } else {
            $task->setCanDeactivateSelf(false);
        }
        $task->setNumberOfRecords((int)$submittedData['scheduler_saltedpasswordsBulkUpdateNumberOfRecords']);
    }
}
