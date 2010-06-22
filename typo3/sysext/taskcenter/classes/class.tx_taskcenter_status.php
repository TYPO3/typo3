<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Georg Ringer <typo3@ringerge.org>
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
 * @author		Georg Ringer <typo3@ringerge.org>
 * @package		TYPO3
 * @subpackage	taskcenter
 *
 */
class tx_taskcenter_status {

	/**
	 * Saves the section toggle state of tasks in the backend user's uc
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function saveCollapseState(array $params, TYPO3AJAX $ajaxObj) {
			// remove 'el_' in the beginning which is needed for the saveSortingState()
		$item	= substr(htmlspecialchars(t3lib_div::_POST('item')), 3);
		$state	= (bool)t3lib_div::_POST('state');

		$GLOBALS['BE_USER']->uc['taskcenter']['states'][$item] = $state;
		$GLOBALS['BE_USER']->writeUC();
	}


	/**
	 * Saves the sorting order of tasks in the backend user's uc
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function saveSortingState(array $params, TYPO3AJAX $ajaxObj) {
		$sort = array();
		$items = explode('&', t3lib_div::_POST('data'));
		foreach($items as $item) {
		 $sort[] = substr($item, 12);
		}

		$GLOBALS['BE_USER']->uc['taskcenter']['sorting'] = serialize($sort);
		$GLOBALS['BE_USER']->writeUC();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/classes/class.tx_taskcenter_status.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/classes/class.tx_taskcenter_status.php']);
}

?>