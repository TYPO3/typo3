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
 * Additional BE fields for recycler garbage collection task.
 *
 * Creates an integer input field for difference between scheduler run time
 * and file modification time in days to select from.
 */
class RecyclerGarbageCollectionAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Default period in days to remove a recycled file
     *
     * @var int Default number of days
     */
    protected $defaultNumberOfDays = 30;

    /**
     * Add an integer input field for difference between scheduler run time
     * and file modification time in days to select from
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|NULL $task When editing, reference to the current task. NULL when adding.
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        // Initialize selected fields
        if (!isset($taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'])) {
            $taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'] = $this->defaultNumberOfDays;
            if ($parentObject->CMD === 'edit') {
                $taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'] = $task->numberOfDays;
            }
        }
        $fieldName = 'tx_scheduler[scheduler_recyclerGarbageCollection_numberOfDays]';
        $fieldId = 'task_recyclerGarbageCollection_numberOfDays';
        $fieldValue = (int)$taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'];
        $fieldHtml = '<input class="form-control" type="text" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '">';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.recyclerGarbageCollection.numberOfDays',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $additionalFields;
    }

    /**
     * Checks if the given value is an integer
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $result = true;
        // Check if number of days is indeed a number and greater or equals to 0
        // If not, fail validation and issue error message
        if (!is_numeric($submittedData['scheduler_recyclerGarbageCollection_numberOfDays']) || (int)$submittedData['scheduler_recyclerGarbageCollection_numberOfDays'] < 0) {
            $result = false;
            $parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidNumberOfDays'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }
        return $result;
    }

    /**
     * Saves given integer value in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $task->numberOfDays = (int)$submittedData['scheduler_recyclerGarbageCollection_numberOfDays'];
    }
}
