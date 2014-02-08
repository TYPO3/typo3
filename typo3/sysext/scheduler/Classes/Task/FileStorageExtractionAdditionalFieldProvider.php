<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Additional BE fields for task which extracts metadata from storage
 *
 */
class FileStorageExtractionAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Add additional fields
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 * @throws \InvalidArgumentException
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		if ($task !== NULL && !$task instanceof FileStorageExtractionTask) {
			throw new \InvalidArgumentException('Task not of type FileStorageExtractionTask', 1384275695);
		}
		$additionalFields['scheduler_fileStorageIndexing_storage'] = $this->getAllStoragesField($task);
		$additionalFields['scheduler_fileStorageIndexing_fileCount'] = $this->getFileCountField($task);
		return $additionalFields;
	}

	/**
	 * Returns a field configuration including a selectbox for available storages
	 *
	 * @param FileStorageExtractionTask $task When editing, reference to the current task object. NULL when adding.
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getAllStoragesField(FileStorageExtractionTask $task = NULL) {
		/** @var \TYPO3\CMS\Core\Resource\ResourceStorage[] $storages */
		$storages = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\StorageRepository')->findAll();
		$options = array();
		foreach ($storages as $storage) {
			if ($task !== NULL && $task->storageUid === $storage->getUid()) {
				$options[] = '<option value="' . $storage->getUid() . '" selected="selected">' . $storage->getName() . '</option>';
			} else {
				$options[] = '<option value="' . $storage->getUid() . '">' . $storage->getName() . '</option>';
			}
		}

		$fieldName = 'tx_scheduler[scheduler_fileStorageIndexing_storage]';
		$fieldId = 'scheduler_fileStorageIndexing_storage';
		$fieldHtml = '<select name="' . $fieldName . '" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';

		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xlf:label.fileStorageIndexing.storage',
			'cshKey' => '_MOD_system_txschedulerM1',
			'cshLabel' => $fieldId
		);
		return $fieldConfiguration;
	}

	/**
	 * Returns a field configuration including a input field for the file count
	 *
	 * @param FileStorageExtractionTask $task When editing, reference to the current task object. NULL when adding.
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	protected function getFileCountField(FileStorageExtractionTask $task = NULL) {
		$fieldName = 'tx_scheduler[scheduler_fileStorageIndexing_fileCount]';
		$fieldId = 'scheduler_fileStorageIndexing_fileCount';
		$fieldValue = $task !== NULL ? (int)$task->maxFileCount : 100;
		$fieldHtml = '<input type="text" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '" />';

		$fieldConfiguration = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:scheduler/mod1/locallang.xlf:label.fileStorageExtraction.fileCount',
			'cshKey' => '_MOD_system_txschedulerM1',
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
		if (!MathUtility::canBeInterpretedAsInteger($submittedData['scheduler_fileStorageIndexing_storage']) ||
			!MathUtility::canBeInterpretedAsInteger($submittedData['scheduler_fileStorageIndexing_fileCount'])) {
			return FALSE;
		} elseif(\TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getStorageObject($submittedData['scheduler_fileStorageIndexing_storage']) === NULL) {
			return FALSE;
		} elseif (!MathUtility::isIntegerInRange($submittedData['scheduler_fileStorageIndexing_fileCount'], 1, 9999)) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Save additional field in task
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		if ($task !== NULL && !$task instanceof FileStorageExtractionTask) {
			throw new \InvalidArgumentException('Task not of type FileStorageExtractionTask', 1384275698);
		}
		$task->storageUid = (int)$submittedData['scheduler_fileStorageIndexing_storage'];
		$task->maxFileCount = (int)$submittedData['scheduler_fileStorageIndexing_fileCount'];
	}

}
