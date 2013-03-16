<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Additional BE fields for caching framework garbage collection task.
 * Creates a multi selectbox with all available cache backends to select from.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class CachingFrameworkGarbageCollectionAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Add a multi select box with all available cache backends.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		// Initialize selected fields
		if (empty($taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'])) {
			$taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'] = array();
			if ($parentObject->CMD == 'add') {
				// In case of new task, set to dbBackend if it's available
				if (in_array('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', $this->getRegisteredBackends())) {
					$taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'][] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend';
				}
			} elseif ($parentObject->CMD == 'edit') {
				// In case of editing the task, set to currently selected value
				$taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'] = $task->selectedBackends;
			}
		}
		$fieldName = 'tx_scheduler[scheduler_cachingFrameworkGarbageCollection_selectedBackends][]';
		$fieldId = 'task_cachingFrameworkGarbageCollection_selectedBackends';
		$fieldOptions = $this->getCacheBackendOptions($taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends']);
		$fieldHtml = '<select name="' . $fieldName . '" id="' . $fieldId . '" class="wide" size="10" multiple="multiple">' . $fieldOptions . '</select>';
		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xml:label.cachingFrameworkGarbageCollection.selectBackends',
			'cshKey' => '_MOD_tools_txschedulerM1',
			'cshLabel' => $fieldId
		);
		return $additionalFields;
	}

	/**
	 * Checks that all selected backends exist in available backend list
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$validData = TRUE;
		$availableBackends = $this->getRegisteredBackends();
		if (is_array($submittedData['scheduler_cachingFrameworkGarbageCollection_selectedBackends'])) {
			$invalidBackends = array_diff($submittedData['scheduler_cachingFrameworkGarbageCollection_selectedBackends'], $availableBackends);
			if (!empty($invalidBackends)) {
				$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:msg.selectionOfNonExistingCacheBackends'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$validData = FALSE;
			}
		} else {
			$parentObject->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/mod1/locallang.xml:msg.noCacheBackendSelected'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$validData = FALSE;
		}
		return $validData;
	}

	/**
	 * Save selected backends in task object
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$task->selectedBackends = $submittedData['scheduler_cachingFrameworkGarbageCollection_selectedBackends'];
	}

	/**
	 * Build select options of available backends and set currently selected backends
	 *
	 * @param array $selectedBackends Selected backends
	 * @return string HTML of selectbox options
	 */
	protected function getCacheBackendOptions(array $selectedBackends) {
		$options = array();
		$availableBackends = $this->getRegisteredBackends();
		foreach ($availableBackends as $backendName) {
			if (in_array($backendName, $selectedBackends)) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options[] = '<option value="' . $backendName . '"' . $selected . '>' . $backendName . '</option>';
		}
		return implode('', $options);
	}

	/**
	 * Get all registered caching framework backends
	 *
	 * @return array Registered backends
	 */
	protected function getRegisteredBackends() {
		$backends = array();
		$cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
		if (is_array($cacheConfigurations)) {
			foreach ($cacheConfigurations as $cacheConfiguration) {
				$backend = $cacheConfiguration['backend'];
				if (!in_array($backend, $backends)) {
					$backends[] = $backend;
				}
			}
		}
		return $backends;
	}

}


?>