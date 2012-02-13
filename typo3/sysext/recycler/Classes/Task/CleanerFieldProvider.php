<?php
namespace TYPO3\CMS\Recycler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Bergsmann <p.bergsmann@opendo.at>
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
 * @author Philipp Bergsmann <p.bergsmann@opendo.at>
 */
class CleanerFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param \TYPO3\CMS\Recycler\Task\CleanerTask $task The task object being eddited. Null when adding a task!
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		if ($schedulerModule->CMD == 'edit') {
			$taskInfo['RecyclerCleanerTCA'] = $task->getTcaTables();
			$taskInfo['RecyclerCleanerPeriod'] = $task->getPeriod();
		}

		$additionalFields['period'] = array(
			'code' => '<input type="text" name="tx_scheduler[RecyclerCleanerPeriod]" value="' . $taskInfo['RecyclerCleanerPeriod'] . '" />',
			'label' => 'LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskPeriod',
			'cshKey' => '',
			'cshLabel' => 'task_recyclerCleaner_selectedPeriod'
		);

		$additionalFields['tca'] = array(
			'code' => $this->getTcaSelectHtml($taskInfo['RecyclerCleanerTCA']),
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
	protected function getTcaSelectHtml($selectedTables = array()) {
		if (!is_array($selectedTables)) {
			$selectedTables = array();
		}
		$tcaSelectHtml = '<select name="tx_scheduler[RecyclerCleanerTCA][]" multiple="multiple" class="wide" size="10">';

		$options = array();
		foreach ($GLOBALS['TCA'] as $table => $tableConf) {
			if ($tableConf['ctrl']['adminOnly'] != 1 && !empty($tableConf['ctrl']['delete'])) {
				$selected = (in_array($table, $selectedTables)) ? ' selected="selected"' : '';
				$tableTitle = $GLOBALS['LANG']->sL($tableConf['ctrl']['title']);
				$options[$tableTitle] = '<option' . $selected . ' value="' . $table . '">' . htmlspecialchars($tableTitle . ' (' . $table . ')') . '</option>';
			}
		}

		ksort($options);
		$tcaSelectHtml .= implode('', $options);
		$tcaSelectHtml .= '</select>';

		return $tcaSelectHtml;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$validPeriod = $this->validateAdditionalFieldPeriod($submittedData['RecyclerCleanerPeriod'], $schedulerModule);
		$validTca = $this->validateAdditionalFieldTca($submittedData['RecyclerCleanerTCA'], $schedulerModule);

		$valid = ($validPeriod === TRUE && $validTca === TRUE) ? TRUE : FALSE;

		return $valid;
	}

	/**
	 * Validates the selected Tables
	 *
	 * @param array $tca The given TCA-tables as array
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok, FALSE otherwise
	 */
	protected function validateAdditionalFieldTca($tca, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController &$schedulerModule) {
		$validTca = FALSE;
		if ($this->checkTcaIsNotEmpty($tca, $schedulerModule) === TRUE
			&& $this->checkTcaIsValid($tca, $schedulerModule) === TRUE
		) {
			$validTca = TRUE;
		}

		return $validTca;
	}

	/**
	 * Checks if the array is empty
	 *
	 * @param array $tca The given TCA-tables as array
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok, FALSE otherwise
	 */
	protected function checkTcaIsNotEmpty($tca, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController &$schedulerModule) {
		if (is_array($tca) === TRUE	&& count($tca) > 0) {
			$validTca = TRUE;
		} else {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorTCAempty', TRUE),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			$validTca = FALSE;
		}

		return $validTca;
	}

	/**
	 * Checks if the given tables are in the TCA
	 *
	 * @param array $tca The given TCA-tables as array
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return bool TRUE if validation was ok, FALSE otherwise
	 */
	protected function checkTcaIsValid(array $tca, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController &$schedulerModule) {
		foreach ($tca as $tcaTable) {
			if (isset($GLOBALS['TCA'][$tcaTable]) === FALSE) {
				$checkTca = FALSE;
				$schedulerModule->addMessage(
						sprintf($GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorTCANotSet', TRUE), $tcaTable),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
					);
				break;
			} else {
				$checkTca = TRUE;
			}
		}

		return $checkTca;
	}

	/**
	 * Validates the input of period
	 *
	 * @param integer $period The given period as integer
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok, FALSE otherwise
	 */
	protected function validateAdditionalFieldPeriod($period, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController &$schedulerModule) {
		if (empty($period) === FALSE && filter_var($period, FILTER_VALIDATE_INT) !== FALSE) {
			$validPeriod = TRUE;
		} else {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:recycler/locallang_tasks.xlf:cleanerTaskErrorPeriod', TRUE),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			$validPeriod = FALSE;
		}

		return $validPeriod;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		if (!($task instanceof \TYPO3\CMS\Recycler\Task\CleanerTask)) {
			throw new \InvalidArgumentException(
				'Expected a task of type \TYPO3\CMS\Recycler\Task\CleanerTask, but got ' . get_class($task),
				1329219449
			);
		}

		$task->setTcaTables($submittedData['RecyclerCleanerTCA']);
		$task->setPeriod($submittedData['RecyclerCleanerPeriod']);
	}
}

?>