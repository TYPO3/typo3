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
 * @package TYPO3
 * @subpackage tx_recycler
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
				'code'	 => '<input type="text" name="tx_scheduler[RecyclerCleanerPeriod]" value="' . $taskInfo['RecyclerCleanerPeriod'] . '" />',
				'label'	=> 'LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskPeriod',
				'cshKey'   => '',
				'cshLabel' => $fieldId
			);

		$additionalFields['tca'] = array(
				'code'	 => $this->getTCASelectHTML($taskInfo['RecyclerCleanerTCA']),
				'label'	=> 'LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskTCA',
				'cshKey'   => '',
				'cshLabel' => $fieldId
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
		global $TCA;

		$return = '<select name="tx_scheduler[RecyclerCleanerTCA][]" multiple="multiple" class="wide">';

		foreach ($TCA as $table => $tableConf) {
			if (
				$tableConf['ctrl']['adminOnly'] != 1
				&& !empty($tableConf['ctrl']['delete'])
				&& !empty($tableConf['ctrl']['tstamp'])
			) {
				$selected = (in_array($table, $selectedTables)) ? ' selected="selected"' : '';
				$return .= '<option' . $selected . ' value="' . $table . '">' . $GLOBALS['LANG']->sL($tableConf['ctrl']['title']) . ' (' . $table . ')</option>';
			}
		}

		$return .= '</select>';

		return $return;
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
		$valid = TRUE;

		if (
			empty($submittedData['RecyclerCleanerPeriod'])
			|| !filter_var($submittedData['RecyclerCleanerPeriod'], FILTER_VALIDATE_INT)
		) {
			$schedulerModule->addMessage(
					'wrong data',
					t3lib_FlashMessage::ERROR
				);
			$valid = FALSE;
		}

		if (!is_array($submittedData['RecyclerCleanerTCA']) || !count($submittedData['RecyclerCleanerTCA'])) {
			$schedulerModule->addMessage(
					'at least one table',
					t3lib_FlashMessage::ERROR
				);
			$valid = FALSE;
		}

		return $valid;
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
				1295012802
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