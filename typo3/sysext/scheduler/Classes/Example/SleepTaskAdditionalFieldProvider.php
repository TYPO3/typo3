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

namespace TYPO3\CMS\Scheduler\Example;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional fields provider class for usage with the Scheduler's sleep task
 * @internal This class is an example is not considered part of the Public TYPO3 API.
 */
class SleepTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * This method is used to define new fields for adding or editing a task
     * In this case, it adds a sleep time field
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param SleepTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        // Initialize extra field value
        if (empty($taskInfo['sleepTime'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                // In case of new task and if field is empty, set default sleep time
                $taskInfo['sleepTime'] = 30;
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                // In case of edit, set to internal value if no data was submitted already
                $taskInfo['sleepTime'] = $task->sleepTime;
            } else {
                // Otherwise set an empty value, as it will not be used anyway
                $taskInfo['sleepTime'] = '';
            }
        }
        // Write the code for the field
        $fieldID = 'task_sleepTime';
        $fieldCode = '<input type="text" class="form-control" name="tx_scheduler[sleepTime]" id="' . $fieldID . '" value="' . $taskInfo['sleepTime'] . '" size="10">';
        $additionalFields = [];
        $additionalFields[$fieldID] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.sleepTime',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldID,
        ];
        return $additionalFields;
    }

    /**
     * This method checks any additional data that is relevant to the specific task
     * If the task class is not relevant, the method is expected to return TRUE
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $submittedData['sleepTime'] = (int)$submittedData['sleepTime'];
        if ($submittedData['sleepTime'] < 0) {
            $this->addMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidSleepTime'), FlashMessage::ERROR);
            $result = false;
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * This method is used to save any additional input into the current task object
     * if the task class matches
     *
     * @param array $submittedData Array containing the data submitted by the user
     * @param SleepTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->sleepTime = $submittedData['sleepTime'];
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
