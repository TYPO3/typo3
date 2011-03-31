<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kai Vogel <kai.vogel@speedprogs.de>
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
 * Additional BE fields for recycler cleanup task.
 * 
 * Creates an integer input field for difference between scheduler run time
 * and file modification time in days to select from.
 *
 * @author 2011 Kai Vogel <kai.vogel@speedprogs.de>
 * @package TYPO3
 * @subpackage scheduler
 */
class tx_scheduler_RecyclerCleanup_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

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
	 * @param array Reference to the array containing the info used in the add/edit form
	 * @param object When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module Reference to the calling object (Scheduler's BE module)
	 * @return array Array containg all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
			// Initialize selected fields
		if (empty($taskInfo['scheduler_recyclerCleanup_numberOfDays'])) {
			$taskInfo['scheduler_recyclerCleanup_numberOfDays'] = $this->defaultNumberOfDays;
			if ($parentObject->CMD === 'edit') {
				$taskInfo['scheduler_recyclerCleanup_numberOfDays'] = $task->numberOfDays;
			}
		}

		$fieldName    = 'tx_scheduler[scheduler_recyclerCleanup_numberOfDays]';
		$fieldId      = 'task_recyclerCleanup_numberOfDays';
		$fieldValue   = (int) $taskInfo['scheduler_recyclerCleanup_numberOfDays'];
		$fieldHtml    = '<input type="text" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '" />';

		$additionalFields[$fieldId] = array(
			'code'     => $fieldHtml,
			'label'    => 'LLL:EXT:scheduler/mod1/locallang.xml:label.recyclerCleanup.numberOfDays',
			'cshKey'   => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId,
		);

		return $additionalFields;
	}


	/**
	 * Checks that all selected backends exist in available backend list
	 *
	 * @param array Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		return (bool) intval($submittedData['scheduler_recyclerCleanup_numberOfDays']);
	}


	/**
	 * Save selected backends in task object
	 *
	 * @param array Contains data submitted by the user
	 * @param tx_scheduler_Task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->numberOfDays = (int) $submittedData['scheduler_recyclerCleanup_numberOfDays'];
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_recyclercleanup_additionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_recyclercleanup_additionalfieldprovider.php']);
}

?>