<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Kai Vogel <kai.vogel@speedprogs.de>
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
 * Additional BE fields for recycler garbage collection task.
 *
 * Creates an integer input field for difference between scheduler run time
 * and file modification time in days to select from.
 *
 * @author 2011 Kai Vogel <kai.vogel@speedprogs.de>
 */
class RecyclerGarbageCollectionAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Default period in days to remove a recycled file
	 *
	 * @var integer Default number of days
	 */
	protected $defaultNumberOfDays = 30;

	/**
	 * Add an integer input field for difference between scheduler run time
	 * and file modification time in days to select from
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		// Initialize selected fields
		if (!isset($taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'])) {
			$taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'] = $this->defaultNumberOfDays;
			if ($parentObject->CMD === 'edit') {
				$taskInfo['scheduler_recyclerGarbageCollection_numberOfDays'] = $task->numberOfDays;
			}
		}
		$fieldName = 'tx_scheduler[scheduler_recyclerGarbageCollection_numberOfDays]';
		$fieldId = 'task_recyclerGarbageCollection_numberOfDays';
		$fieldValue = intval($taskInfo['scheduler_recyclerGarbageCollection_numberOfDays']);
		$fieldHtml = '<input type="text" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '" />';
		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.recyclerGarbageCollection.numberOfDays',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId
		);
		return $additionalFields;
	}

	/**
	 * Checks if the given value is an integer
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$result = TRUE;
		// Check if number of days is indeed a number and greater or equals to 0
		// If not, fail validation and issue error message
		if (!is_numeric($submittedData['scheduler_recyclerGarbageCollection_numberOfDays']) || intval($submittedData['scheduler_recyclerGarbageCollection_numberOfDays']) < 0) {
			$result = FALSE;
			$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:msg.invalidNumberOfDays'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$task->numberOfDays = intval($submittedData['scheduler_recyclerGarbageCollection_numberOfDays']);
	}

}


?>