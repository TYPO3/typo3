<?php
namespace TYPO3\CMS\Reports\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Additional field to set the notification email address(es) for system health
 * issue notifications.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class SystemStatusUpdateTaskNotificationEmailField implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

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
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being eddited. Null when adding a task!
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$fields = array('notificationEmail');
		if ($schedulerModule->CMD == 'edit') {
			$taskInfo[$this->fieldPrefix . 'NotificationEmail'] = $task->getNotificationEmail();
		}
		$additionalFields = array();
		foreach ($fields as $field) {
			$fieldName = $this->getFullFieldName($field);
			$fieldId = 'task_' . $fieldName;
			$fieldHtml = '<input type="text" ' . 'name="tx_scheduler[' . $fieldName . ']" ' . 'id="' . $fieldId . '" ' . 'value="' . htmlspecialchars($taskInfo[$fieldName]) . '" />';
			$additionalFields[$fieldId] = array(
				'code' => $fieldHtml,
				'label' => 'LLL:EXT:reports/reports/locallang.xml:status_updateTaskField_' . $field,
				'cshKey' => '',
				'cshLabel' => $fieldId
			);
		}
		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$validInput = TRUE;
		$submittedData[$this->fieldPrefix . 'NotificationEmail'] = trim($submittedData[$this->fieldPrefix . 'NotificationEmail']);
		if (empty($submittedData[$this->fieldPrefix . 'NotificationEmail']) || !filter_var($submittedData[($this->fieldPrefix . 'NotificationEmail')], FILTER_VALIDATE_EMAIL)) {
			$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:reports/reports/locallang.xml:status_updateTaskField_notificationEmail_invalid'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$validInput = FALSE;
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
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		if (!$task instanceof \TYPO3\CMS\Reports\Task\SystemStatusUpdateTask) {
			throw new \InvalidArgumentException('Expected a task of type TYPO3\\CMS\\Reports\\Task\\SystemStatusUpdateTask, but got ' . get_class($task), 1295012802);
		}
		$task->setNotificationEmail($submittedData[$this->fieldPrefix . 'NotificationEmail']);
	}

	/**
	 * Constructs the full field name which can be used in HTML markup.
	 *
	 * @param string $fieldName A raw field name
	 * @return string Field name ready to use in HTML markup
	 */
	protected function getFullFieldName($fieldName) {
		return $this->fieldPrefix . ucfirst($fieldName);
	}

}


?>