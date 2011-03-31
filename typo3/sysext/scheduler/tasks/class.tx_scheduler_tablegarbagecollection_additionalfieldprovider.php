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
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$additionalFields['task_tableGarbageCollection_allTables'] = $this->getAllTablesAdditionalField($taskInfo, $task, $parentObject);
		$additionalFields['task_tableGarbageCollection_table'] = $this->getTableAdditionalField($taskInfo, $task, $parentObject);
		$additionalFields['task_tableGarbageCollection_numberOfDays'] = $this->getNumberOfDaysAdditionalField($taskInfo, $task, $parentObject);

		return $additionalFields;
	}

	/**
	 * Add a select field of available tables.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getAllTablesAdditionalField(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		if ($parentObject->CMD === 'edit') {
			$checked = $task->allTables === TRUE ? 'checked="checked" ' : '';
		} else {
			$checked = '';
		}

		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_allTables]';
		$fieldId = 'task_tableGarbageCollection_allTables';
		$fieldHtml = '<input type="checkbox" ' .
			$checked .
			'onChange="actOnChangeSchedulerTableGarbageCollectionAllTables(this)" ' .
			'name="' . $fieldName . '" ' .
			'id="' . $fieldId . '" />';

		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.allTables',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId,
		);

		return $fieldConfiguration;
	}

	/**
	 * Add a select field of available tables.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getTableAdditionalField(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'];

		$options = array();

				// Add an empty option on top if an existing task is configured
				// with a table that can not be found in configuration anymore
		if ($parentObject->CMD === 'edit' && !array_key_exists($task->table, $tableConfiguration)) {
			$options[] = '<option value="" selected="selected"></option>';
		}

		$defaultNumberOfDays = array();
		foreach ($tableConfiguration as $tableName => $configuration) {
			if ($parentObject->CMD === 'add' && count($options) === 0) {
					// Select first table by default if adding a new task
				$options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
				if (isset($configuration['expirePeriod'])) {
					$defaultNumberOfDays[$tableName] = $configuration['expirePeriod'];
				}
			} elseif ($task->table === $tableName) {
					// Select currently selected table
				$options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
				if (isset($configuration['expirePeriod'])) {
					$defaultNumberOfDays[$tableName] = $task->numberOfDays;
				}
			} else {
				$options[] = '<option value="' . $tableName . '">' . $tableName . '</option>';
				if (isset($configuration['expirePeriod'])) {
					$defaultNumberOfDays[$tableName] = $configuration['expirePeriod'];
				}
			}
		}

		$disabled = $task->allTables === TRUE ? ' disabled="disabled"' : '';

		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_table]';
		$fieldId = 'task_tableGarbageCollection_table';

		$fieldHtml = array();
			// Add table drop down html
		$fieldHtml[] = '<select ' .
			'name="' . $fieldName . '" ' .
			$disabled .
			'onChange="actOnChangeSchedulerTableGarbageCollectionTable(this)"' .
			'id="' . $fieldId . '">' .
			implode(LF, $options) .
			'</select>';
			// Add js array for default 'number of days' values
		$fieldHtml[] = '<script type="text/javascript">/*<![CDATA[*/<!--';
		$fieldHtml[] = 'var defaultNumberOfDays = ' . json_encode($defaultNumberOfDays) . ';';
		$fieldHtml[] = '// -->/*]]>*/</script>';

		$fieldConfiguration = array(
			'code' => implode(LF, $fieldHtml),
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
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getNumberOfDaysAdditionalField(array &$taskInfo, $task, tx_scheduler_Module $parentObject) {
			// Initialize selected fields
		$disabled = '';
		if (empty($taskInfo['scheduler_tableGarbageCollection_numberOfDays'])) {
			if ($parentObject->CMD === 'add') {
					// In case of new task, set to 180 days
				$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = 180;
			} elseif ($parentObject->CMD === 'edit') {
					// In case of editing the task, set to currently selected value
				$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = $task->numberOfDays;
				if ($task->numberOfDays === 0) {
					$disabled = ' disabled="disabled"';
				}
			}
		}

		if ($task->allTables === TRUE) {
			$disabled = ' disabled="disabled"';
		}

		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_numberOfDays]';
		$fieldId = 'task_tableGarbageCollection_numberOfDays';
		$fieldHtml = '<input type="text" ' .
			'name="' . $fieldName . '" ' .
			'id="' . $fieldId . '" ' .
			$disabled .
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
		$validData = $this->validateAllTablesAdditionalField($submittedData, $parentObject);
		$validData &= $this->validateTableAdditionalField($submittedData, $parentObject);
		$validData &= $this->validateNumberOfDaysAdditionalField($submittedData, $parentObject);

		return $validData;
	}

	/**
	 * Checks if all table field is correct
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if data is valid
	 */
	public function validateAllTablesAdditionalField(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = FALSE;
		if (!isset($submittedData['scheduler_tableGarbageCollection_allTables'])) {
			$validData = TRUE;
		} elseif ($submittedData['scheduler_tableGarbageCollection_allTables'] === 'on') {
			$validData = TRUE;
		}

		return $validData;
	}

	/**
	 * Checks given table for existence in configuration array
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if table exists in configuration, false otherwise
	 */
	public function validateTableAdditionalField(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = FALSE;
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'];
		if (!isset($submittedData['scheduler_tableGarbageCollection_table'])) {
			$validData = TRUE;
		} elseif (array_key_exists($submittedData['scheduler_tableGarbageCollection_table'], $tableConfiguration)) {
			$validData = TRUE;
		}

		return $validData;
	}

	/**
	 * Checks if given number of days is a positive integer
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param tx_scheduler_Module $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateNumberOfDaysAdditionalField(array &$submittedData, tx_scheduler_Module $parentObject) {
		$validData = FALSE;
		if (!isset($submittedData['scheduler_tableGarbageCollection_numberOfDays'])) {
			$validData = TRUE;
		} elseif (intval($submittedData['scheduler_tableGarbageCollection_numberOfDays']) >= 0) {
			$validData = TRUE;
		} else {
				// Issue error message
			$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:msg.invalidNumberOfDays'), t3lib_FlashMessage::ERROR);
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
		$task->allTables = $submittedData['scheduler_tableGarbageCollection_allTables'] === 'on' ? TRUE : FALSE;
		$task->table = $submittedData['scheduler_tableGarbageCollection_table'];
		$task->numberOfDays = intval($submittedData['scheduler_tableGarbageCollection_numberOfDays']);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_tablegarbagecollection_additionalfieldprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/scheduler/tasks/class.tx_scheduler_tablegarbagecollection_additionalfieldprovider.php']);
}

?>