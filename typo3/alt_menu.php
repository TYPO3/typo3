<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Displays the vertical menu in the left most frame of TYPO3s backend
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML-trans compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   79: class SC_alt_menu 
 *   90:     function init()	
 *  106:     function main()	
 *  191:     function removeCacheFiles()	
 *  210:     function printContent()	
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_loadmodules.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
require_once ('class.alt_menu_functions.inc');














/**
 * Script Class for rendering the vertical menu in the left side of the backend frameset
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_menu {

		// Internal, Static: GPvar
	var $_clearCacheFiles;

	/**
	 * Initialize
	 * Loads the backend modules available for the logged in user.
	 * 
	 * @return	void		
	 */
	function init()	{
		global $TBE_MODULES;
		
			// Setting GPvars:
		$this->_clearCacheFiles = t3lib_div::GPvar('_clearCacheFiles');

			// Loads the backend modules available for the logged in user.
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->load($TBE_MODULES);
	}

	/**
	 * Main content generated
	 * 
	 * @return	void		
	 */
	function main()	{
		global $BE_USER,$TYPO3_CONF_VARS,$TBE_TEMPLATE,$TYPO_VERSION;
		
		$TBE_TEMPLATE->docType='xhtml_trans';
		$TBE_TEMPLATE->divClass='vertical-menu';
		$this->content.=$TBE_TEMPLATE->startPage('Vertical Backend Menu');

			// Printing the menu
		$alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');
		$this->content.=$alt_menuObj->topMenu($this->loadModules->modules);
		
			// clear cache commands for Admins
		if($BE_USER->isAdmin()) {
			$this->content.='
			
<!-- 
  Menu with admin functions: Clearing cache:
-->
<div id="typo3-alt-menu-php-adminFunc">';
				
				// Header: Admin functions
			$this->content.='<h2 class="bgColor5">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.adminFunctions',1).'</h2>';

				// Table with those admin functions
			$this->content.='
				<table border="0" cellpadding="0" cellspacing="1" width="100%">';
			
				// Clearing of cache-files in typo3conf/ + menu
			if ($TYPO3_CONF_VARS['EXT']['extCache'])	{
				if ($this->_clearCacheFiles)	{
					$this->removeCacheFiles();
				}
				$this->content.='
					<tr>
						<td valign="top" align="center"><img'.t3lib_iconWorks::skinImg($backPath,'gfx/clear_cache_files_in_typo3c.gif','width="21" height="18"').' alt="" /></td>
						<td><a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('_clearCacheFiles'=>1))).'">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_allTypo3Conf',1).'</a></td>
					</tr>';

					// Divider
				$this->content.='
					<tr>
						<td colspan="2"><img'.t3lib_iconWorks::skinImg($backPath,'gfx/altmenuline.gif','width="105" height="3"').' alt="" /></td>
					</tr>';
			}

				// clear all page cache
			$href = htmlspecialchars($this->backPath.'tce_db.php?vC='.$BE_USER->veriCode().
						'&redirect='.rawurlencode(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT')).
						'&cacheCmd=all'
					);
			$this->content.='
				<tr>
					<td valign="top" align="center"><img'.t3lib_iconWorks::skinImg($backPath,'gfx/clear_all_cache.gif','width="21" height="18"').' alt="" /></td>
					<td><a href="'.$href.'">'.
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_all',1).
					'</a></td>
				</tr>';

			$this->content.='
				</table>';
			$this->content.='
</div>
';
		}

			// Printing bottons (logout button)
		$this->content.='
		

<!-- 
  Logout button / username
-->
<div id="typo3-alt-menu-php-logininfo">'.$alt_menuObj->topButtons().
						'<p id="username">['.htmlspecialchars($BE_USER->user['username']).']</p>
</div>';

			// End page:
		$this->content.=$TBE_TEMPLATE->endPage();
	}

	/**
	 * Unlink (delete) cache files
	 * 
	 * @return	integer		The number of files deleted
	 */
	function removeCacheFiles()	{
		$cacheFiles=t3lib_extMgm::currentCacheFiles();
		$out=0;
		if (is_array($cacheFiles))	{
			reset($cacheFiles);
			while(list(,$cfile)=each($cacheFiles))	{
				@unlink($cfile);
				clearstatcache();
				$out++;
			}
		}
		return $out;
	}

	/**
	 * Outputting the accumulated content to screen
	 * 
	 * @return	void		
	 */
	function printContent()	{
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_menu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_menu.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_menu');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>