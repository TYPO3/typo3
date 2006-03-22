<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author Sebastian Kurfürst <sebastian@garbage-group.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   80: class SC_alt_menu
 *   91:     function init()
 *  108:     function main()
 *  190:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @co-author Sebastian Kurfürst <sebastian@garbage-group.de>
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
		$this->_clearCacheFiles = t3lib_div::_GP('_clearCacheFiles');

			// Loads the backend modules available for the logged in user.
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->observeWorkspaces = TRUE;
		$this->loadModules->load($TBE_MODULES);
	}

	/**
	 * Main content generated
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$TYPO3_CONF_VARS,$TBE_TEMPLATE;

		$TBE_TEMPLATE->docType='xhtml_trans';
		$TBE_TEMPLATE->divClass='vertical-menu';
		$TBE_TEMPLATE->bodyTagAdditions = 'onload="top.restoreHighlightedModuleMenuItem()"';
		$this->content.=$TBE_TEMPLATE->startPage('Vertical Backend Menu');
		$backPath = $GLOBALS['BACK_PATH'];

			// Printing the menu
		$alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');
		$this->content.= $alt_menuObj->topMenu($this->loadModules->modules);

			// clear cache commands for Admins
		if($BE_USER->isAdmin()) {	//  && $BE_USER->workspace===0 NOT used anyway because under a workspace developers might still like to clear cache!
			$functionsArray = $alt_menuObj->adminFunctions($backPath);

			$this->content.='

<!--
  Menu with admin functions: Clearing cache:
-->
<div id="typo3-alt-menu-php-adminFunc">';


				// Table with those admin functions
			$this->content.='
				<table border="0" cellpadding="0" cellspacing="1" width="100%" id="typo3-adminmenu">';

				// Header: Admin functions
			$this->content.='
					<tr class="c-mainitem">
						<td colspan="2"><span class="c-label"><b>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.adminFunctions',1).'</b>&nbsp;</span><span class="c-iconCollapse"></span></td>
					</tr>';

			$rows=array();
			foreach($functionsArray as $functionsArraySetup)	{
				$rows[]='
					<tr class="c-subitem">
						<td valign="top" align="center" class="icon">'.$functionsArraySetup['icon'].'</td>
						<td><a href="'.htmlspecialchars($functionsArraySetup['href']).'">'.htmlspecialchars($functionsArraySetup['title']).'</a></td>
					</tr>';
			}

				// Imploding around the divider table row:
			$this->content.=implode('
					<tr>
						<td colspan="2"><img'.t3lib_iconWorks::skinImg($backPath,'gfx/altmenuline.gif','width="105" height="3"').' alt="" /></td>
					</tr>',$rows);

			$this->content.='
				</table>';
			$this->content.=t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'menu_adminFunction', $GLOBALS['BACK_PATH']);
			$this->content.='
</div>
';
		}

			// superuser mode
		if($BE_USER->user['ses_backuserid']) {
			$username = '<p id="username" class="typo3-red-background">[SU: '.htmlspecialchars($BE_USER->user['username']).']</p>';
		} else {
			$username = '<p id="username">['.htmlspecialchars($BE_USER->user['username']).']</p>';
		}
			// Printing bottons (logout button)
		$this->content.='


<!--
  Logout button / username
-->
<div id="typo3-alt-menu-php-logininfo">'.$alt_menuObj->topButtons().$username.'
</div>';

			// End page:
		$this->content.=$TBE_TEMPLATE->endPage();
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