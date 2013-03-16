<?php
namespace TYPO3\CMS\Saltedpasswords\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Philipp Gampe (typo3@philippgampe.info)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Additional field for salted passwords bulk update task
 *
 * @autor Philipp Gampe <typo3@philippgampe.info>
 */
class BulkUpdateFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Default value whether the task deactivates itself after last run.
	 *
	 * @var boolean Whether the task is allowed to deactivate itself after processing all existing user records.
	 */
	protected $defaultCanDeactivateSelf = TRUE;

	/**
	 * Default value for the number of records to handle at each run.
	 *
	 * @var integer Number of records
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
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
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
		$fieldChecked = (bool) $taskInfo['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'];
		$fieldHtml = '<input type="checkbox"' . ' name="' . $fieldName . '"' . ' id="' . $fieldId . '"' . ' value="' . $fieldValue . '"' . ($fieldChecked ? ' checked="checked"' : '') . ' />';
		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:saltedpasswords/locallang.xml:ext.saltedpasswords.tasks.bulkupdate.label.canDeactivateSelf',
			'cshKey' => '_txsaltedpasswords',
			'cshLabel' => $fieldId
		);
		// Configuration for numberOfRecords
		$fieldName = 'tx_scheduler[scheduler_saltedpasswordsBulkUpdateNumberOfRecords]';
		$fieldId = 'task_saltedpasswordsBulkUpdateNumberOfRecords';
		$fieldValue = intval($taskInfo['scheduler_saltedpasswordsBulkUpdateNumberOfRecords']);
		$fieldHtml = '<input type="text" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '" />';
		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:saltedpasswords/locallang.xml:ext.saltedpasswords.tasks.bulkupdate.label.numberOfRecords',
			'cshKey' => '_txsaltedpasswords',
			'cshLabel' => $fieldId
		);
		return $additionalFields;
	}

	/**
	 * Checks if the given values are boolean and integer
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$result = TRUE;
		// Check if number of records is indeed a number and greater or equals to 0
		// If not, fail validation and issue error message
		if (!is_numeric($submittedData['scheduler_saltedpasswordsBulkUpdateNumberOfRecords']) || intval($submittedData['scheduler_saltedpasswordsBulkUpdateNumberOfRecords']) < 0) {
			$result = FALSE;
			$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:saltedpasswords/locallang.xml:ext.saltedpasswords.tasks.bulkupdate.invalidNumberOfRecords'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		if (isset($submittedData['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf']) && $submittedData['scheduler_saltedpasswordsBulkUpdateCanDeactivateSelf'] === 'IsChecked') {
			$task->setCanDeactivateSelf(TRUE);
		} else {
			$task->setCanDeactivateSelf(FALSE);
		}
		$task->setNumberOfRecords(intval($submittedData['scheduler_saltedpasswordsBulkUpdateNumberOfRecords']));
	}

}


?>