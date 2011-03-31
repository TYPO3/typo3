<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Additional BE fields for sys log table garbage collection task.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage scheduler
 */
class tx_scheduler_SysLogGarbageCollection_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Add a input field to get the number of days.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containg all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
			// Initialize selected fields
		if (empty($taskInfo['scheduler_sysLogGarbageCollection_numberOfDays'])) {
			if ($parentObject->CMD === 'add') {
					// In case of new task, set to 180 days
				$taskInfo['scheduler_sysLogGarbageCollection_numberOfDays'] = 180;
			} elseif ($parentObject->CMD === 'edit') {
					// In case of editing the task, set to currently selected value
				$taskInfo['scheduler_sysLogGarbageCollection_numberOfDays'] = $task->numberOfDays;
			}
		}

		$fieldName = 'tx_scheduler[scheduler_sysLogGarbageCollection_numberOfDays]';
		$fieldId = 'task_sysLogGarbageCollection_numberOfDays';
		$fieldHtml = '<input type="text" ' .
			'name="' . $fieldName . '" ' .
			'id="' . $fieldId . '" ' .
			'value="' . htmlspecialchars($taskInfo['scheduler_sysLogGarbageCollection_numberOfDays']) . '" ' .
			'size="4" />';

		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.sysLogGarbageCollection.numberOfDays',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId,
		);

		return $additionalFields;
	}

	/**
	 * Checks that given number of days is a positive integer
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = FALSE;
		if (intval($submittedData['scheduler_sysLogGarbageCollection_numberOfDays']) > 0) {
			$validData = TRUE;
		}

		return $validData;
	}

	/**
	 * Save number of days in task
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param tx_scheduler_Task $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->numberOfDays = intval($submittedData['scheduler_sysLogGarbageCollection_numberOfDays']);
	}
} // End of class

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_syloggarbagecollection_additionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_syloggarbagecollection_additionalfieldprovider.php']);
}

?>