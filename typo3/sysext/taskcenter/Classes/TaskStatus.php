<?php
namespace TYPO3\CMS\Taskcenter;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Status of tasks
 *
 * @author Georg Ringer <typo3@ringerge.org>
 */
class TaskStatus {

	/**
	 * Saves the section toggle state of tasks in the backend user's uc
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function saveCollapseState(array $params, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		// Remove 'el_' in the beginning which is needed for the saveSortingState()
		$item = substr(htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('item')), 3);
		$state = (bool) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('state');
		$GLOBALS['BE_USER']->uc['taskcenter']['states'][$item] = $state;
		$GLOBALS['BE_USER']->writeUC();
	}

	/**
	 * Saves the sorting order of tasks in the backend user's uc
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type AjaxRequestHandler
	 * @return void
	 */
	public function saveSortingState(array $params, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		$sort = array();
		$items = explode('&', \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('data'));
		foreach ($items as $item) {
			$sort[] = substr($item, 12);
		}
		$GLOBALS['BE_USER']->uc['taskcenter']['sorting'] = serialize($sort);
		$GLOBALS['BE_USER']->writeUC();
	}

}
