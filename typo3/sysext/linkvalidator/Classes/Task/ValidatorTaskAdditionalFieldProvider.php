<?php
namespace TYPO3\CMS\Linkvalidator\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2013 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Scheduler Additional Field plugin implementation
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class ValidatorTaskAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Render additional information fields within the scheduler backend.
	 *
	 * @param array $taskInfo Array information of task to return
	 * @param ValidatorTask $task Task object
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
	 * @return array Additional fields
	 * @see \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface->getAdditionalFields($taskInfo, $task, $schedulerModule)
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$additionalFields = array();
		if (empty($taskInfo['configuration'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['configuration'] = '';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['configuration'] = $task->getConfiguration();
			} else {
				$taskInfo['configuration'] = $task->getConfiguration();
			}
		}
		if (empty($taskInfo['depth'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['depth'] = array();
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['depth'] = $task->getDepth();
			} else {
				$taskInfo['depth'] = $task->getDepth();
			}
		}
		if (empty($taskInfo['page'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['page'] = '';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['page'] = $task->getPage();
			} else {
				$taskInfo['page'] = $task->getPage();
			}
		}
		if (empty($taskInfo['email'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['email'] = '';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['email'] = $task->getEmail();
			} else {
				$taskInfo['email'] = $task->getEmail();
			}
		}
		if (empty($taskInfo['emailOnBrokenLinkOnly'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['emailOnBrokenLinkOnly'] = 1;
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
			} else {
				$taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
			}
		}
		if (empty($taskInfo['emailTemplateFile'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['emailTemplateFile'] = 'EXT:linkvalidator/Resources/Private/Templates/mailtemplate.html';
			} elseif ($schedulerModule->CMD == 'edit') {
				$taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
			} else {
				$taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
			}
		}
		$fieldId = 'task_page';
		$fieldCode = '<input type="text" name="tx_scheduler[linkvalidator][page]" id="' . $fieldId . '" value="' . htmlspecialchars($taskInfo['page']) . '"/>';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.page');
		$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		// input for depth
		$fieldId = 'task_depth';
		$fieldValueArray = array(
			'0' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
			'1' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
			'2' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
			'3' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
			'4' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
			'999' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
		);
		$fieldCode = '<select name="tx_scheduler[linkvalidator][depth]" id="' . $fieldId . '">';
		foreach ($fieldValueArray as $depth => $label) {
			$fieldCode .= "\t" . '<option value="' . htmlspecialchars($depth) . '"' .
						(($depth == $taskInfo['depth']) ? ' selected="selected"' : '') .
						'>' . $label . '</option>';
		}
		$fieldCode .= '</select>';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.depth');
		$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		$fieldId = 'task_configuration';
		$fieldCode = '<textarea  name="tx_scheduler[linkvalidator][configuration]" id="' . $fieldId . '" >' .
					htmlspecialchars($taskInfo['configuration']) . '</textarea>';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.conf');
		$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		$fieldId = 'task_email';
		$fieldCode = '<input type="text"  name="tx_scheduler[linkvalidator][email]" id="' . $fieldId . '" value="' .
					htmlspecialchars($taskInfo['email']) . '" />';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.email');
		$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		$fieldId = 'task_emailOnBrokenLinkOnly';
		$fieldCode = '<input type="checkbox"  name="tx_scheduler[linkvalidator][emailOnBrokenLinkOnly]" id="' . $fieldId . '" ' .
					(htmlspecialchars($taskInfo['emailOnBrokenLinkOnly']) ? 'checked="checked"' : '') . ' />';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.emailOnBrokenLinkOnly');
		$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		$fieldId = 'task_emailTemplateFile';
		$fieldCode = '<input type="text"  name="tx_scheduler[linkvalidator][emailTemplateFile]" id="' . $fieldId .
					'" value="' . htmlspecialchars($taskInfo['emailTemplateFile']) . '" />';
		$label = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.emailTemplateFile');
		$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
		$additionalFields[$fieldId] = array(
			'code' => $fieldCode,
			'label' => $label
		);
		return $additionalFields;
	}

	/**
	 * Mark current value as selected by returning the "selected" attribute
	 *
	 * @param array $configurationArray Array of configuration
	 * @param string $currentValue Value of selector object
	 * @return string Html fragment for a selected option or empty
	 */
	protected function getSelectedState(array $configurationArray, $currentValue) {
		$selected = '';
		if (in_array($currentValue, $configurationArray, TRUE)) {
			$selected = 'selected="selected" ';
		}
		return $selected;
	}

	/**
	 * This method checks any additional data that is relevant to the specific task.
	 * If the task class is not relevant, the method is expected to return TRUE.
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$isValid = TRUE;
		//TODO add validation to validate the $submittedData['configuration'] which is normally a comma separated string
		if (!empty($submittedData['linkvalidator']['email'])) {
			$emailList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $submittedData['linkvalidator']['email']);
			foreach ($emailList as $emailAdd) {
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($emailAdd)) {
					$isValid = FALSE;
					$schedulerModule->addMessage($GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidEmail'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				}
			}
		}
		if ($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid = ' . intval($submittedData['linkvalidator']['page']))) {
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 0 && $submittedData['linkvalidator']['page'] > 0) {
				$isValid = FALSE;
				$schedulerModule->addMessage(
					$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidPage'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
				);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		} else {
			$isValid = FALSE;
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidPage'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
		}
		if ($submittedData['linkvalidator']['depth'] < 0) {
			$isValid = FALSE;
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidDepth'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
		}
		return $isValid;
	}

	/**
	 * This method is used to save any additional input into the current task object
	 * if the task class matches.
	 *
	 * @param array $submittedData Array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		/** @var $task ValidatorTask */
		$task->setDepth($submittedData['linkvalidator']['depth']);
		$task->setPage($submittedData['linkvalidator']['page']);
		$task->setEmail($submittedData['linkvalidator']['email']);
		if ($submittedData['linkvalidator']['emailOnBrokenLinkOnly']) {
			$task->setEmailOnBrokenLinkOnly(1);
		} else {
			$task->setEmailOnBrokenLinkOnly(0);
		}
		$task->setConfiguration($submittedData['linkvalidator']['configuration']);
		$task->setEmailTemplateFile($submittedData['linkvalidator']['emailTemplateFile']);
	}

}
?>