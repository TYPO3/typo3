<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Class, adding SU link to context menu
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */












/**
 * Class, adding SU link to context menu
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class tx_beuser {

	/**
	 * Adding various standard options to the context menu.
	 * This includes both first and second level.
	 *
	 * @param	object		The calling object. Value by reference.
	 * @param	array		Array with the currently collected menu items to show.
	 * @param	string		Table name of clicked item.
	 * @param	integer		UID of clicked item.
	 * @return	array		Modified $menuItems array
	 */
	function main(&$backRef,$menuItems,$table,$uid)	{
		$localItems = array();	// Accumulation of local items.

			// Detecting menu level
		if ($GLOBALS['BE_USER']->isAdmin() && !$backRef->cmLevel && $table == 'be_users') {
			// LEVEL: Primary menu.

				// "SU" element added:
			$url = 'mod.php?M=tools_beuser&SwitchUser='.rawurlencode($uid).'&switchBackUser=1';
			$localItems[] = $backRef->linkItem(
				'Switch To User',
				$backRef->excludeIcon(t3lib_iconWorks::getSpriteIcon('actions-system-backend-user-emulate')),
				$backRef->urlRefForCM($url,'',1,'top'),
				1
			);

			$menuItems=array_merge($menuItems,$localItems);
		}
		return $menuItems;
	}

	/**
	 * Include local lang file.
	 *
	 * @return	array		Local lang array.
	 */
	function includeLL()	{
		global $LANG;

		$LOCAL_LANG = $GLOBALS['LANG']->includeLLFile('EXT:extra_page_cm_options/locallang.php', FALSE);
		return $LOCAL_LANG;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/extra_page_cm_options/class.tx_extrapagecmoptions.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/extra_page_cm_options/class.tx_extrapagecmoptions.php']);
}
?>