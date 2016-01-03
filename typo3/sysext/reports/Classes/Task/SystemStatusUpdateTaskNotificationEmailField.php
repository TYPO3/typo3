<?php
namespace TYPO3\CMS\Reports\Task;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Additional field to set the notification email address(es) for system health
 * issue notifications.
 */
class SystemStatusUpdateTaskNotificationEmailField implements AdditionalFieldProviderInterface
{
    /**
     * Additional fields
     *
     * @var array
     */
    protected $fields = array('notificationEmail');

    /**
     * Field prefix.
     *
     * @var string
     */
    protected $fieldPrefix = 'SystemStatusUpdate';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being edited. Null when adding a task!
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        if ($schedulerModule->CMD == 'edit') {
            $taskInfo[$this->fieldPrefix . 'NotificationEmail'] = $task->getNotificationEmail();
        }
            // build html for additional email field
        $fieldName = $this->getFullFieldName('notificationEmail');
        $fieldId = 'task_' . $fieldName;
        $fieldHtml = '<textarea class="form-control" ' . 'rows="5" cols="50" name="tx_scheduler[' . $fieldName . ']" ' . 'id="' . $fieldId . '" ' . '>' . htmlspecialchars($taskInfo[$fieldName]) . '</textarea>';

        $additionalFields = array();
        $additionalFields[$fieldId] = array(
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskField_notificationEmails',
            'cshKey' => '',
            'cshLabel' => $fieldId
        );

        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validInput = true;
        $notificationEmails = GeneralUtility::trimExplode(LF, $submittedData[$this->fieldPrefix . 'NotificationEmail'], true);
        foreach ($notificationEmails as $notificationEmail) {
            if (!GeneralUtility::validEmail($notificationEmail)) {
                $validInput = false;
                break;
            }
        }
        if (empty($submittedData[$this->fieldPrefix . 'NotificationEmail']) || !$validInput) {
            $schedulerModule->addMessage($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskField_notificationEmails_invalid'), FlashMessage::ERROR);
            $validInput = false;
        }
        return $validInput;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof SystemStatusUpdateTask) {
            throw new \InvalidArgumentException('Expected a task of type ' . SystemStatusUpdateTask::class . ', but got ' . get_class($task), 1295012802);
        }
        $task->setNotificationEmail($submittedData[$this->fieldPrefix . 'NotificationEmail']);
    }

    /**
     * Constructs the full field name which can be used in HTML markup.
     *
     * @param string $fieldName A raw field name
     * @return string Field name ready to use in HTML markup
     */
    protected function getFullFieldName($fieldName)
    {
        return $this->fieldPrefix . ucfirst($fieldName);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
