<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer additional field provider class for usage with the FAL migrate task
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id: $
 */
class tx_fal_MigrationTask_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * This method is used to define new fields for adding or editing a task
	 *
	 * @param	array					$taskInfo: reference to the array containing the info used in the add/edit form
	 * @param	object					$task: when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	array					Array containg all the information pertaining to the additional fields
	 *									The array is multidimensional, keyed to the task class name and each field's id
	 *									For each field it provides an associative sub-array with the following:
	 *										['code']		=> The HTML code for the field
	 *										['label']		=> The label of the field (possibly localized)
	 *										['cshKey']		=> The CSH key for the field
	 *										['cshLabel']	=> The code of the CSH label
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$this->task =& $task;

		$uid = $this->getTaskUid();

		if (empty($taskInfo[$uid]['limit'])) {
			if ($parentObject->CMD == 'add') {
					// default value on add
				$taskInfo[$uid]['limit'] = 500;
			} elseif ($parentObject->CMD == 'edit') {
					// value if not data was submitted already
				$taskInfo[$uid]['limit'] = $this->task->limit;
			} else {
					// Otherwise set an empty value, as it will not be used anyway
				$taskInfo[$uid]['limit'] = 0;
			}
		}

			// Write the code for the field
		$fieldID = 'task_limit';
		$fieldCode = '<input type="text" name="tx_scheduler[' . $uid . '][limit]" id="' . $fieldID . '" value="' .
			$taskInfo[$uid]['limit'] . '" size="30" />';

		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:fal/locallang.xml:label.limit',
			'cshKey'   => '_MOD_tools_txfalM1',
			'cshLabel' => $fieldID
		);

		if (empty($taskInfo[$uid]['tabletoworkon'])) {
			if ($parentObject->CMD == 'add') {
					// default value on add
				$taskInfo[$uid]['tabletoworkon'] = array();
			} elseif ($parentObject->CMD == 'edit') {
					// value if not data was submitted already
				$taskInfo[$uid]['tabletoworkon'] = $this->task->tableToWorkOn;
			} else {
					// Otherwise set an empty value, as it will not be used anyway
				$taskInfo[$uid]['tabletoworkon'] = array();
			}
		}

			// Write the code for the field
		$fieldID = 'task_tableToWorkOn';
		$fieldCode = $this->getTableNameSelectfield($uid, $fieldID, $taskInfo[$uid]['tabletoworkon']);

		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:fal/locallang.xml:label.tableToWorkOn',
			'cshKey'   => '_MOD_tools_txfalM1',
			'cshLabel' => $fieldID
		);

		return $additionalFields;
	}

	/**
	 * Fetches the uid of the task or defaults to 0 and returns it
	 *
	 * @return	integer		DESCRIPTION
	 */
	protected function getTaskUid() {
		$uid = 0;

		if (is_object($this->task)) {
			$uid = $this->task->getTaskUid();
		}

		return $uid;
	}

	/**
	 * Render the select field for tableNames with previous selected tableNames
	 *
	 * @param	array	$selectedTableNames		DESCRIPTION
	 * @return	string							DESCRIPTION
	 */
	protected function getTableNameSelectfield($uid, $fieldID, $selectedTableNames = array()) {
		$databaseFieldnameIterator = t3lib_div::makeInstance('tx_fal_DatabaseFieldnameIterator');

		$code = '<select name="tx_scheduler[' . $uid .
				'][tabletoworkon][]" id="' . $fieldID . '" multiple="multiple">';

		foreach ($databaseFieldnameIterator->getTableNames() as $tableName) {
			$label = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);

			$selected = '';
			if (in_array($tableName, $selectedTableNames)) {
				$selected = ' selected="selected"';
			}

			$code .= '<option value="' . $tableName . '"' . $selected . '>' . $label . '</option>';
		}

		$code .= '</select>';

		return $code;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task
	 * If the task class is not relevant, the method is expected to return true
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_Module		$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		$data = $submittedData[intval($submittedData['uid'])];

		$result = TRUE;

		if (!intval($data['limit'])) {
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$data = $submittedData[intval($submittedData['uid'])];

		$task->tableToWorkOn = (array) $data['tabletoworkon'];
		$task->limit = (int) $data['limit'];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/tasks/class.tx_fal_migrationtask_additionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/tasks/class.tx_fal_migrationtask_additionalfieldprovider.php']);
}
?>