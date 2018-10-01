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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional BE fields for recycler garbage collection task.
 *
 * Creates an integer input field for difference between scheduler run time
 * and file modification time in days to select from.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class RecyclerGarbageCollectionAdditionalFieldProvider extends AbstractAdditionalFieldProvider
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
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        // Initialize selected fields
        if (!isset($taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'])) {
            $taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'] = $this->defaultNumberOfDays;
            if ($currentSchedulerModuleAction->equals(Action::EDIT)) {
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
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $result = true;
        // Check if number of days is indeed a number and greater or equals to 0
        // If not, fail validation and issue error message
        if (!is_numeric($submittedData['scheduler_recyclerGarbageCollection_numberOfDays']) || (int)$submittedData['scheduler_recyclerGarbageCollection_numberOfDays'] < 0) {
            $result = false;
            $this->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidNumberOfDays'), FlashMessage::ERROR);
        }
        return $result;
    }

    /**
     * Saves given integer value in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->numberOfDays = (int)$submittedData['scheduler_recyclerGarbageCollection_numberOfDays'];
    }
}
