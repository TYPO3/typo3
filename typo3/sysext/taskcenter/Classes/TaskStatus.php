<?php
namespace TYPO3\CMS\Taskcenter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Georg Ringer <typo3@ringerge.org>
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
 * Status of tasks
 *
 * @author Georg Ringer <typo3@ringerge.org>
 */
class TaskStatus {

	/**
	 * Saves the section toggle state of tasks in the backend user's uc
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
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
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
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

?>