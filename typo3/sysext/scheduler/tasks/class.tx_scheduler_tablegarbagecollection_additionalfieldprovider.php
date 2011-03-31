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
class tx_scheduler_TableGarbageCollection_AdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Add addional fields
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containg all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$additionalFields['scheduler_tableGarbageCollection_table'] = $this->getTableAdditionalField($taskInfo, $task, $parentObject);
		$additionalFields['scheduler_tableGarbageCollection_numberOfDays'] = $this->getNumberOfDaysAdditionalField($taskInfo, $task, $parentObject);

		return $additionalFields;
	}

	/**
	 * Add a select field of available tables.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containg all the information pertaining to the additional fields
	 */
	protected function getTableAdditionalField(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'];

		$options = array();
		if ($parentObject->CMD === 'edit') {
				// Add an empty option on top if an existing task is configured
				// with a table that can not be found in configuration anymore
			$tableInConfiguration = t3lib_utility_Array::filterByValueRecursive($task->table, $tableConfiguration);
			if (count($tableInConfiguration) === 0) {
				$options[] = '<option value="" selected="selected"></option>';
				$currentTable = $task->table;
			} elseif (count($tableInConfiguration) >= 1) {
				$row = current($tableInConfiguration);
				if ($row['table'] === $task->table) {
					$currentTable = $task->table;
				}
			}
		}

		foreach ($tableConfiguration as $rowCounter => $configuration) {
			if ($parentObject->CMD === 'add' && count($options) === 0) {
					// Select first table by default if adding a new task
				$options[] = '<option value="' . $configuration['table'] . '" selected="selected">' . $configuration['table'] . '</option>';
			} elseif (isset($currentTable) && $currentTable === $configuration['table']) {
					// Select currently selected task
				$options[] = '<option value="' . $configuration['table'] . '" selected="selected">' . $configuration['table'] . '</option>';
			} else {
				$options[] = '<option value="' . $configuration['table'] . '">' . $configuration['table'] . '</option>';
			}
		}

		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_table]';
		$fieldId = 'task_tableGarbageCollection_table';
		$fieldHtml = '<select ' .
			'name="' . $fieldName . '" ' .
			'id="' . $fieldId . '">' .
			implode(LF, $options) .
			'</select>';

		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.table',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId,
		);

		return $fieldConfiguration;
	}

	/**
	 * Add a input field to get the number of days.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containg all the information pertaining to the additional fields
	 */
	protected function getNumberOfDaysAdditionalField(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
			// Initialize selected fields
		if (empty($taskInfo['scheduler_tableGarbageCollection_numberOfDays'])) {
			if ($parentObject->CMD === 'add') {
					// In case of new task, set to 180 days
				$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = 180;
			} elseif ($parentObject->CMD === 'edit') {
					// In case of editing the task, set to currently selected value
				$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = $task->numberOfDays;
			}
		}

		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_numberOfDays]';
		$fieldId = 'task_tableGarbageCollection_numberOfDays';
		$fieldHtml = '<input type="text" ' .
			'name="' . $fieldName . '" ' .
			'id="' . $fieldId . '" ' .
			'value="' . intval($taskInfo['scheduler_tableGarbageCollection_numberOfDays']) . '" ' .
			'size="4" />';

		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.numberOfDays',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId,
		);

		return $fieldConfiguration;
	}

	/**
	 * Validate additional fields
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = $this->validateTableAdditionalField($submittedData, $parentObject);
		$validData &= $this->validateNumberOfDaysAdditionalField($submittedData, $parentObject);

		return $validData;
	}

	/**
	 * Checks give table for exisstence in configuration array
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if table exists in configuration, false otherwise
	 */
	public function validateTableAdditionalField(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = FALSE;
		$table = $submittedData['scheduler_tableGarbageCollection_table'];
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'];
		$tableInConfiguration = t3lib_utility_Array::filterByValueRecursive($table, $tableConfiguration);
		if (count($tableInConfiguration) >= 1) {
			$row = array_pop($tableInConfiguration);
			if ($row['table'] === $table) {
				$validData = TRUE;
			}
		}

		return $validData;
	}

	/**
	 * Checks that given number of days is a positive integer
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateNumberOfDaysAdditionalField(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = FALSE;
		if (intval($submittedData['scheduler_tableGarbageCollection_numberOfDays']) > 0) {
			$validData = TRUE;
		}

		return $validData;
	}

	/**
	 * Save additional field in task
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param tx_scheduler_Task $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->numberOfDays = intval($submittedData['scheduler_tableGarbageCollection_numberOfDays']);
		$task->table = $submittedData['scheduler_tableGarbageCollection_table'];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_tablegarbagecollection_additionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_tablegarbagecollection_additionalfieldprovider.php']);
}

?>