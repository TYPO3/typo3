<?php
namespace TYPO3\CMS\Taskcenter\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Georg Ringer <typo3@ringerge.org>
 *      2013 Wouter Wolters <typo3@wouterwolters.nl>
 *
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
 * This class provides a taskcenter for BE users
 *
 * @author Georg Ringer <typo3@ringerge.org>
 */
class TaskModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * Overview action
	 *
	 * @return void
	 */
	public function overviewAction() {
		$this->view->assign('isAdmin', $GLOBALS['BE_USER']->isAdmin());
	}

	/**
	 * Tasks action
	 *
	 * @param string $task
	 */
	public function tasksAction($task = 'taskcenter.tasks') {
		list($extensionKey, $taskClassName) = explode('.', $task, 2);
		$taskContent = $this->getTaskContent($extensionKey, $taskClassName);
		$tasks = $this->getTasks();

		$this->view->assignMultiple(array(
			'currentTask' => $task,
			'taskContent' => $taskContent,
			'itemClass' => htmlspecialchars($extensionKey . '-' . $taskClassName),
			'tasks' => $tasks
		));
	}

	/**
	 * Get content for task
	 *
	 * @param string $extensionKey The extension key
	 * @param string $taskClassName The class name of the task
	 * @return string
	 */
	protected function getTaskContent($extensionKey, $taskClassName) {
		$taskContent = '';
		if (class_exists($taskClassName)) {
			$taskInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($taskClassName, $this);
			if ($taskInstance instanceof \TYPO3\CMS\Taskcenter\TaskInterface) {
				// Check if the task is restricted to admins only
				if ($this->checkAccess($extensionKey, $taskClassName)) {
					$taskContent = $taskInstance->getTask();
				} else {
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						$this->translate('error-access'),
						$this->translate('error_header'),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
					);
					$this->addFlashMessage($flashMessage);
				}
			} else {
				// Error if the task is not an instance of tx_taskcenter_Task
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
					'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					sprintf($this->translate('error_no-instance'), $taskClassName, 'TYPO3\\CMS\\Taskcenter\\TaskInterface'),
					$this->translate('error_header'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
				);
				$this->addFlashMessage($flashMessage);
			}
		} else {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$this->translate('LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xml:mlang_labels_tabdescr'),
				$this->translate('LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xml:mlang_tabs_tab'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::INFO
			);
			$this->addFlashMessage($flashMessage);
		}
		return $taskContent;
	}

	/**
	 * Get all available tasks
	 *
	 * @return string List of available reports
	 */
	protected function getTasks() {
		$tasks = array();
		$icon = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('taskcenter') . 'Resources/Public/Images/task.gif';
		// Render the tasks only if there are any available
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']) && count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']) > 0) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'] as $extensionKey => $extensionReports) {
				foreach ($extensionReports as $taskClassName => $task) {
					if (!$this->checkAccess($extensionKey, $taskClassName)) {
						continue;
					}

					$link = 'mod.php?M=user_TaskcenterTask&tx_taskcenter_user_taskcentertask[task]=' . $extensionKey . '.' . $taskClassName . '&tx_taskcenter_user_taskcentertask[action]=tasks&tx_taskcenter_user_taskcentertask[controller]=TaskModule';
					$taskTitle = $this->translate($task['title']);
					$taskDescriptionHtml = '';
					// Check for custom icon
					if (!empty($task['icon'])) {
						$icon = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFilename($task['icon']);
					}
					if (class_exists($taskClassName)) {
						$taskInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($taskClassName, $this);
						if ($taskInstance instanceof \TYPO3\CMS\Taskcenter\TaskInterface) {
							$taskDescriptionHtml = $taskInstance->getOverview();
						}
					}
					// Generate an array of all tasks
					$uniqueKey = $this->getUniqueKey($extensionKey . '.' . $taskClassName);
					$tasks[$uniqueKey] = array(
						'title' => htmlspecialchars($taskTitle),
						'descriptionHtml' => $taskDescriptionHtml,
						'description' => htmlspecialchars($this->translate($task['description'])),
						'icon' => $icon,
						'link' => $link,
						'uid' => $extensionKey . '.' . $taskClassName,
						'id' => $this->getUniqueKey($extensionKey . '.' . $taskClassName)
					);
				}
			}
		} else {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$this->translate('no-tasks'),
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::INFO
			);
			$this->addFlashMessage($flashMessage);
		}

		if (!empty($tasks)) {
			$tasks = $this->sortTasks($tasks);
		}

		return $tasks;
	}

	/**
	 * Helper function for translating labels
	 *
	 * @param string $label The label
	 * @return string The translated label
	 */
	protected function translate($label) {
		return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label, 'taskcenter');
	}

	/**
	 * Add flash message to the queue
	 *
	 * @param \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage
	 * @return void
	 */
	protected function addFlashMessage(\TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage) {
		/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

	/**
	 * Check the access to a task. Considered are:
	 * - Admins are always allowed
	 * - Tasks can be restriced to admins only
	 * - Tasks can be blinded for Users with TsConfig taskcenter.<extensionkey>.<taskName> = 0
	 *
	 * @param string $extensionKey The extension key
	 * @param string $taskClassName The class name of the task
	 * @return boolean Access to the task allowed or not
	 */
	protected function checkAccess($extensionKey, $taskClassName) {
		// Check if task is blinded with TsConfig (taskcenter.<extkey>.<taskName>
		$tsConfig = $GLOBALS['BE_USER']->getTSConfig('taskcenter.' . $extensionKey . '.' . $taskClassName);
		if (isset($tsConfig['value']) && intval($tsConfig['value']) === 0) {
			return FALSE;
		}
		// Admins are always allowed
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return TRUE;
		}
		// Check if task is restricted to admins
		if (intval($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'][$extensionKey][$taskClassName]['admin']) === 1) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Create a unique key from a string which can be used in Prototype's Sortable
	 * Therefore '_' are replaced
	 *
	 * @param string $string string which is used to generate the identifier
	 * @return string Modified string
	 */
	protected function getUniqueKey($string) {
		$search = array('.', '_');
		$replace = array('-', '');
		return str_replace($search, $replace, $string);
	}

	/**
	 * Sort tasks
	 *
	 * @param array $tasks
	 * @return array
	 */
	protected function sortTasks(array $tasks) {
		$userSorting = unserialize($GLOBALS['BE_USER']->uc['taskcenter']['sorting']);
		if (is_array($userSorting)) {
			$newSorting = array();
			foreach ($userSorting as $item) {
				if (isset($tasks[$item])) {
					$newSorting[] = $tasks[$item];
					unset($tasks[$item]);
				}
			}
			$tasks = $newSorting + $tasks;
		}
		return $tasks;
	}

}

?>