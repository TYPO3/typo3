<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 */

require_once(PATH_t3lib.'class.t3lib_topmenubase.php');

require_once (PATH_t3lib.'class.t3lib_loadmodules.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
require_once ('class.alt_menu_functions.inc');




/**
 * Main script class for the drop down menu
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_menu extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF;
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':
					// Initialize modules
				$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
				$loadModules->observeWorkspaces = TRUE;
				$loadModules->load($TBE_MODULES);

					// Make menu and add it:
				$alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');
				$itemArray = $alt_menuObj->topMenu($loadModules->modules,0,'',5);

/*
				echo '
				&nbsp;Modules&nbsp;';
				
				echo $this->menuLayer($itemArray);
	*/
	
				foreach($itemArray as $k => $prop)	{
					$content = '&nbsp;'.htmlspecialchars($prop['title']).'&nbsp;';
					if (is_array($prop['subitems']))	{
						
						foreach($prop['subitems'] as $kk => $kprop)	{
							if ($GLOBALS['TBE_MODULES_EXT'][$kprop['moduleName']]['MOD_MENU']['function'])	{
								$prop['subitems'][$kk]['subitems'] = array();
								foreach($GLOBALS['TBE_MODULES_EXT'][$kprop['moduleName']]['MOD_MENU']['function'] as $function)	{
									$prop['subitems'][$kk]['subitems'][] = array(
										'title' => $GLOBALS['LANG']->sL($function['title']),
									);
								}
							}
						}
						$content.= $this->menuLayer($prop['subitems']);
					}
					echo $this->menuItemLayer($MCONF['name'].'_'.$k,$content,$prop['onclick']); 
				}
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/menu/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/menu/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_menu');
$SOBE->main();
?>