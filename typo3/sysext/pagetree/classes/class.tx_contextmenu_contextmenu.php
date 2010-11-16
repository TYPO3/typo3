<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Susanne Moog (s.moog@neusta.de)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class with helperfunctions for backendtrees
 *
 * $Id: $
 *
 * @author	Susanne Moog
 * @package TYPO3
 */
class tx_contextmenu_Contextmenu {
	/**
	 * Fetches the available context menu actions configured in TS config and merges
	 * those with the ones available after the access checks
	 * 
	 * @param array $availableActions The actions that were already "access checked" and approved
	 * @param string $tskey the tskey holding the context menu configuration options. you only need the 
	 * 	individual part, like options.contextMenu.[$tskey].items
	 * 
	 * @return array An array of the allowed and configured actions
	 */
	public function getTsConfigActions($availableActions, $tskey='records.pages') {
		return $availableActions;

//		$allowedAndConfiguredActions = array();
//		$contextMenuItemTsConfig = $GLOBALS['BE_USER']->getTSConfig('options.contextMenu.' . $tskey . '.items');
//
//		// we only need the action configuration
//		$actions = t3lib_div::multi_array_key_exists('action', $contextMenuItemTsConfig['properties']);
//
//		// flatten the tsconfig actions array and intersect it with the avilableactions
//		return array_intersect($availableActions, array_values(t3lib_div::array_flatten('-', $actions)));;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/contextmenu/classes/tx_contextmenu_contextmenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/contextmenu/classes/tx_contextmenu_contextmenu.php']);
}

?>
