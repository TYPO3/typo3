<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Adding Import/Export clickmenu item
 *
 * Revised for TYPO3 3.6 December/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   64: class tx_impexp_clickmenu 
 *   73:     function main(&$backRef,$menuItems,$table,$uid)	
 *  115:     function includeLL()	
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 











/**
 * Adding Import/Export clickmenu item
 * 
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class tx_impexp_clickmenu {

	/**
	 * @param	object		Reference to parent
	 * @param	array		Menu items array to modify
	 * @param	string		Table name
	 * @param	integer		Uid of the record
	 * @return	array		Menu item array, returned after modification
	 * @todo	Skinning for icons...
	 */
	function main(&$backRef,$menuItems,$table,$uid)	{
		global $BE_USER,$TCA;
	
		$localItems=array();
		if ($backRef->cmLevel && t3lib_div::_GP('subname')=='moreoptions')	{

			$LL = $this->includeLL();
		
			$url = t3lib_extMgm::extRelPath('impexp').'app/index.php?tx_impexp[action]=export';
			if ($table=='pages')	{
				$url.='&tx_impexp[pagetree][id]='.$uid;
				$url.='&tx_impexp[pagetree][levels]=0';
				$url.='&tx_impexp[pagetree][tables][]=_ALL';
			} else {
				$url.='&tx_impexp[record][]='.rawurlencode($table.':'.$uid);
				$url.='&tx_impexp[external_ref][tables][]=_ALL';
			}
			$localItems[] = $backRef->linkItem(
				$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLLL('export',$LL)),
				$backRef->excludeIcon('<img src="'.$backRef->backPath.t3lib_extMgm::extRelPath('impexp').'export.gif" width="18" height="16" alt="" />'),
				$backRef->urlRefForCM($url),
				1	// Disables the item in the top-bar
			);

			if ($table=='pages')	{
				$url = t3lib_extMgm::extRelPath('impexp').'app/index.php?id='.$uid.'&table='.$table.'&tx_impexp[action]=import';
				$localItems[] = $backRef->linkItem(
					$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLLL('import',$LL)),
					$backRef->excludeIcon('<img src="'.t3lib_extMgm::extRelPath('impexp').'import.gif" width="18" height="16" alt="" />'),
					$backRef->urlRefForCM($url),
					1	// Disables the item in the top-bar
				);
			}
		}
		return array_merge($menuItems,$localItems);
	}

	/**
	 * Include local lang file and return $LOCAL_LANG array loaded.
	 * 
	 * @return	array		Local lang array
	 */
	function includeLL()	{
		include(t3lib_extMgm::extPath('impexp').'app/locallang.php');
		return $LOCAL_LANG;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp_clickmenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp_clickmenu.php']);
}
?>