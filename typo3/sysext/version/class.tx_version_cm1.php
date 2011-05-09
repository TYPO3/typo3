<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Addition of the versioning item to the clickmenu
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   54: class tx_version_cm1
 *   65:     function main(&$backRef,$menuItems,$table,$uid)
 *  111:     function includeLL()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





/**
 * "Versioning" item added to click menu of elements.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_version_cm1 {

	/**
	 * Main function, adding the item to input menuItems array
	 *
	 * @param	object		References to parent clickmenu objects.
	 * @param	array		Array of existing menu items accumulated. New element added to this.
	 * @param	string		Table name of the element
	 * @param	integer		Record UID of the element
	 * @return	array		Modified menuItems array
	 */
	function main(&$backRef,$menuItems,$table,$uid)	{
		global $BE_USER,$TCA,$LANG;

		$localItems = Array();
		if (!$backRef->cmLevel && $uid>0 && $BE_USER->check('modules','web_txversionM1'))	{

				// Returns directly, because the clicked item was not from the pages table
			if (in_array('versioning', $backRef->disabledItems) || !$TCA[$table] || !$TCA[$table]['ctrl']['versioningWS']) {
				return $menuItems;
			}

				// Adds the regular item
			$LL = $this->includeLL();

				// "Versioning" element added:
			$url = t3lib_extMgm::extRelPath('version').'cm1/index.php?table='.rawurlencode($table).'&uid='.$uid;
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->getLLL('title',$LL),
				$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('version').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
				$backRef->urlRefForCM($url),
				1
			);

				// "Send to review" element added:
			$url = t3lib_extMgm::extRelPath('version').'cm1/index.php?id='.($table=='pages'?$uid:$backRef->rec['pid']).'&table='.rawurlencode($table).'&uid='.$uid.'&sendToReview=1';
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->getLLL('title_review',$LL),
				$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('version').'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" alt="" />'),
				$backRef->urlRefForCM($url),
				1
			);

				// Find position of "delete" element:
			$c=0;
			foreach ($menuItems as $k => $value) {
				$c++;
				if (!strcmp($k,'delete'))	break;
			}
				// .. subtract two (delete item + divider line)
			$c-=2;
				// ... and insert the items just before the delete element.
			array_splice(
				$menuItems,
				$c,
				0,
				$localItems
			);
		}
		return $menuItems;
	}

	/**
	 * Includes the [extDir]/locallang.php and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return	array		Local lang array
	 */
	function includeLL()	{
		global $LANG;

		return $LANG->includeLLFile('EXT:version/locallang.xml',FALSE);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/version/class.tx_version_cm1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/version/class.tx_version_cm1.php']);
}
?>