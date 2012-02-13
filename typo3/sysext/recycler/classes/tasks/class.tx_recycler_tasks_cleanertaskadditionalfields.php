<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * A task that should be run regularly that deletes
 * datasets flagged as "deleted" from the DB.
 *
 * @author	Philipp Bergsmann <p.bergsmann@opendo.at>
 * @package	TYPO3
 * @subpackage	tx_recycler
 */
class tx_recycler_tasks_CleanerTaskAdditionalFields implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param tx_scheduler_Task $task The task object being eddited. Null when adding a task!
	 * @param tx_scheduler_Module $schedulerModule Reference to the scheduler backend module
	 * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule)
	{

		if ($schedulerModule->CMD == 'edit') {
			$taskInfo['RecyclerCleanerTCA'] = $task->getTCATables();
			$taskInfo['RecyclerCleanerPeriod'] = $task->getPeriod();
		}

		$additionalFields['period'] = array(
				'code' => '<input type="text" name="tx_scheduler[RecyclerCleanerPeriod]" value="' . $taskInfo['RecyclerCleanerPeriod'] . '" />',
				'label' => 'LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskPeriod',
				'cshKey' => '',
				'cshLabel' => 'task_recyclerCleaner_selectedPeriod'
			);

		$additionalFields['tca'] = array(
				'code' => $this->getTCASelectHTML($taskInfo['RecyclerCleanerTCA']),
				'label' => 'LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskTCA',
				'cshKey' => '',
				'cshLabel' => 'task_recyclerCleaner_selectedTables'
			);

		return $additionalFields;
	}

	/**
	 * Gets the select-box from the TCA-fields
	 *
	 * @param array $selectedTables
	 * @return string
	 */
	protected function getTCASelectHTML($selectedTables = array()) {

		$tcaSelectHtml = '<select name="tx_scheduler[RecyclerCleanerTCA][]" multiple="multiple" class="wide">';

		foreach ($GLOBALS['TCA'] as $table => $tableConf) {
			if (
				$tableConf['ctrl']['adminOnly'] != 1
				&& !empty($tableConf['ctrl']['delete'])
				&& !empty($tableConf['ctrl']['tstamp'])
			) {
				$selected = (in_array($table, $selectedTables)) ? ' selected="selected"' : '';
				$tcaSelectHtml .= '<option' . $selected . ' value="' . $table . '">' . $GLOBALS['LANG']->sL($tableConf['ctrl']['title'], TRUE) . ' (' . $table . ')</option>';
			}
		}

		$tcaSelectHtml .= '</select>';

		return $tcaSelectHtml;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param tx_scheduler_Module $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule)
	{
		$validPeriod = $this->validateAdditionalFieldPeriod($submittedData['RecyclerCleanerPeriod'], $schedulerModule);
		$validTCA = $this->validateAdditionalFieldTca($submittedData['RecyclerCleanerTCA'], $schedulerModule);

		$valid = ($validPeriod === TRUE && $validTCA === TRUE) ? TRUE : FALSE;

		return $valid;
	}

	/**
	 * Validates the selected Tables
	 *
	 * @param array $tca The given TCA-tables as array
	 * @param tx_scheduler_Module $schedulerModule Reference to the scheduler backend module
	 * @return bool TRUE if validation was ok, FALSE otherwise
	 */
	protected function validateAdditionalFieldTca($tca, tx_scheduler_Module &$schedulerModule) {
		if (
			$this->checkTcaIsNotEmpty($tca, $schedulerModule) === TRUE
			&& $this->checkTcaIsValid($tca, $schedulerModule) === TRUE
		) {
			$validTCA = TRUE;
		} else {
			$validTCA = FALSE;
		}

		return $validTCA;
	}

	/**
	 * Checks if the array is empty
	 *
	 * @param array $tca The given TCA-tables as array
	 * @param tx_scheduler_Module $schedulerModule Reference to the scheduler backend module
	 * @return bool TRUE if validation was ok, FALSE otherwise
	 */
	protected function checkTcaIsNotEmpty($tca, tx_scheduler_Module &$schedulerModule) {
		if (
			is_array($tca) === TRUE
			&& count($tca) > 0
		) {
			$validTCA = TRUE;
		} else {
			$schedulerModule->addMessage(
					$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorTCAempty', TRUE),
					t3lib_FlashMessage::ERROR
				);
			$validTCA = FALSE;
		}

		return $validTCA;
	}

	/**
	 * Checks if the given tables are in the TCA
	 *
	 * @param array $tca The given TCA-tables as array
	 * @param tx_scheduler_Module $schedulerModule Reference to the scheduler backend module
	 * @return bool TRUE if validation was ok, FALSE otherwise
	 */
	protected function checkTcaIsValid(array $tca, tx_scheduler_Module &$schedulerModule) {
		foreach ($tca as $tcaTable) {
			if (isset($GLOBALS['TCA'][$tcaTable]) === FALSE) {
				$checkTCA = FALSE;
				$schedulerModule->addMessage(
						sprintf($GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorTCANotSet', TRUE), $tcaTable),
						t3lib_FlashMessage::ERROR
					);
				break;
			} else {
				$checkTCA = TRUE;
			}
		}

		return $checkTCA;
	}

	/**
	 * Validates the input of period
	 *
	 * @param int $period The given period as integer
	 * @param tx_scheduler_Module $schedulerModule Reference to the scheduler backend module
	 * @return bool TRUE if validation was ok, FALSE otherwise
	 */
	protected function validateAdditionalFieldPeriod($period, &$schedulerModule) {
		if (
			empty($period) === FALSE
			&& filter_var($period, FILTER_VALIDATE_INT) !== FALSE
		) {
			$validPeriod = TRUE;
		} else {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorPeriod', TRUE),
					t3lib_FlashMessage::ERROR
				);
			$validPeriod = FALSE;
		}

		return $validPeriod;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param tx_scheduler_Task $task Reference to the scheduler backend module
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task)
	{
		if (!($task instanceof tx_recycler_tasks_CleanerTask)) {
			throw new InvalidArgumentException(
				'Expected a task of type tx_recycler_tasks_CleanerTask, but got ' . get_class($task),
				1329219449
			);
		}

		$task->setTCATables($submittedData['RecyclerCleanerTCA']);

		$task->setPeriod($submittedData['RecyclerCleanerPeriod']);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/tasks/class.tx_recycler_task_cleanertaskadditionalfields.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/tasks/class.tx_recycler_task_cleanertaskadditionalfields.php']);
}

?>