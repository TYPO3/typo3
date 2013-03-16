<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 */
class TableGarbageCollectionAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * @var array Default number of days by table
	 */
	protected $defaultNumberOfDays = array();

	/**
	 * Add additional fields
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$this->initDefaultNumberOfDays();
		$additionalFields['task_tableGarbageCollection_allTables'] = $this->getAllTablesAdditionalField($taskInfo, $task, $parentObject);
		$additionalFields['task_tableGarbageCollection_table'] = $this->getTableAdditionalField($taskInfo, $task, $parentObject);
		$additionalFields['task_tableGarbageCollection_numberOfDays'] = $this->getNumberOfDaysAdditionalField($taskInfo, $task, $parentObject);
		return $additionalFields;
	}

	/**
	 * Initialize the default number of days for all configured tables
	 *
	 * @return void
	 */
	protected function initDefaultNumberOfDays() {
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables'];
		foreach ($tableConfiguration as $tableName => $configuration) {
			if (isset($configuration['expirePeriod'])) {
				$this->defaultNumberOfDays[$tableName] = $configuration['expirePeriod'];
			}
		}
	}

	/**
	 * Add a select field of available tables.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getAllTablesAdditionalField(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		if ($parentObject->CMD === 'edit') {
			$checked = $task->allTables === TRUE ? 'checked="checked" ' : '';
		} else {
			$checked = '';
		}
		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_allTables]';
		$fieldId = 'task_tableGarbageCollection_allTables';
		$fieldHtml = '<input type="checkbox" ' . $checked . 'onChange="actOnChangeSchedulerTableGarbageCollectionAllTables(this)" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" />';
		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.allTables',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId
		);
		return $fieldConfiguration;
	}

	/**
	 * Add a select field of available tables.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getTableAdditionalField(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables'];
		$options = array();
		// Add an empty option on top if an existing task is configured
		// with a table that can not be found in configuration anymore
		if ($parentObject->CMD === 'edit' && !array_key_exists($task->table, $tableConfiguration)) {
			$options[] = '<option value="" selected="selected"></option>';
		}
		foreach ($tableConfiguration as $tableName => $configuration) {
			if ($parentObject->CMD === 'add' && count($options) === 0) {
				// Select first table by default if adding a new task
				$options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
			} elseif ($task->table === $tableName) {
				// Select currently selected table
				$options[] = '<option value="' . $tableName . '" selected="selected">' . $tableName . '</option>';
			} else {
				$options[] = '<option value="' . $tableName . '">' . $tableName . '</option>';
			}
		}
		$disabled = $task->allTables === TRUE ? ' disabled="disabled"' : '';
		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_table]';
		$fieldId = 'task_tableGarbageCollection_table';
		$fieldHtml = array();
		// Add table drop down html
		$fieldHtml[] = '<select ' . 'name="' . $fieldName . '" ' . $disabled . 'onChange="actOnChangeSchedulerTableGarbageCollectionTable(this)"' . 'id="' . $fieldId . '">' . implode(LF, $options) . '</select>';
		// Add js array for default 'number of days' values
		$fieldHtml[] = '<script type="text/javascript">/*<![CDATA[*/<!--';
		$fieldHtml[] = 'var defaultNumberOfDays = ' . json_encode($this->defaultNumberOfDays) . ';';
		$fieldHtml[] = '// -->/*]]>*/</script>';
		$fieldConfiguration = array(
			'code' => implode(LF, $fieldHtml),
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.table',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId
		);
		return $fieldConfiguration;
	}

	/**
	 * Add a input field to get the number of days.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getNumberOfDaysAdditionalField(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		// Initialize selected fields
		$disabled = '';
		if (empty($taskInfo['scheduler_tableGarbageCollection_numberOfDays'])) {
			if ($parentObject->CMD === 'add') {
				// In case of new task, set to 180 days
				$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = 180;
			} elseif ($parentObject->CMD === 'edit') {
				// In case of editing the task, set to currently selected value
				$taskInfo['scheduler_tableGarbageCollection_numberOfDays'] = $task->numberOfDays;
				if ($task->numberOfDays === 0 && !isset($this->defaultNumberOfDays[$task->table])) {
					$disabled = ' disabled="disabled"';
				}
			}
		}
		if ($task->allTables === TRUE) {
			$disabled = ' disabled="disabled"';
		}
		$fieldName = 'tx_scheduler[scheduler_tableGarbageCollection_numberOfDays]';
		$fieldId = 'task_tableGarbageCollection_numberOfDays';
		$fieldHtml = '<input type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . $disabled . 'value="' . intval($taskInfo['scheduler_tableGarbageCollection_numberOfDays']) . '" ' . 'size="4" />';
		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.tableGarbageCollection.numberOfDays',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId
		);
		return $fieldConfiguration;
	}

	/**
	 * Validate additional fields
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$validData = $this->validateAllTablesAdditionalField($submittedData, $parentObject);
		$validData &= $this->validateTableAdditionalField($submittedData, $parentObject);
		$validData &= $this->validateNumberOfDaysAdditionalField($submittedData, $parentObject);
		return $validData;
	}

	/**
	 * Checks if all table field is correct
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if data is valid
	 */
	public function validateAllTablesAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
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
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if table exists in configuration, false otherwise
	 */
	public function validateTableAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$validData = FALSE;
		$tableConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables'];
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
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateNumberOfDaysAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$validData = FALSE;
		if (!isset($submittedData['scheduler_tableGarbageCollection_numberOfDays'])) {
			$validData = TRUE;
		} elseif (intval($submittedData['scheduler_tableGarbageCollection_numberOfDays']) >= 0) {
			$validData = TRUE;
		} else {
			// Issue error message
			$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:msg.invalidNumberOfDays'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		}
		return $validData;
	}

	/**
	 * Save additional field in task
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$task->allTables = $submittedData['scheduler_tableGarbageCollection_allTables'] === 'on' ? TRUE : FALSE;
		$task->table = $submittedData['scheduler_tableGarbageCollection_table'];
		$task->numberOfDays = intval($submittedData['scheduler_tableGarbageCollection_numberOfDays']);
	}

}


?>