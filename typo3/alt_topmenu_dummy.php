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
 * Alternative top menu
 * Displays a horizontal menu with the same items as the default left vertical menu
 * in the backend frameset. Only the icons are displayed and linked.
 * Will appear as the default document in the top frame if configured to appear.
 * This is the default menu used during "condensed mode"
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant content
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class SC_alt_topmenu_dummy
 *   82:     function main()
 *  127:     function dummyContent()
 *  143:     function printContent()
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
 * Script Class for rendering the topframe dummy view.
 * In the case where TYPO3 backend is configured to show the menu in the top frame this class will render the horizontal line of module icons in the top frame.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_topmenu_dummy {
	var $content;

	/**
	 * Main function - making the menu happen.
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TBE_MODULES,$TBE_TEMPLATE;

			// IF noMenuMode is set to 'icons', then display menu instead of nothingness
		if (!strcmp($BE_USER->uc['noMenuMode'],'icons'))	{

				// Loading the modules for this backend user:
			$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
			$loadModules->load($TBE_MODULES);

				// Creating menu object:
			$alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');

				// Start page
			$TBE_TEMPLATE->docType = 'xhtml_trans';
			$TBE_TEMPLATE->bodyTagId.= '-iconmenu';
			$this->content.=$TBE_TEMPLATE->startPage('Top frame icon menu');

				// Make menu and add it:
			$this->content.='

				<!--
				  Alternative module menu made of icons, displayed in top frame:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-topMenu">
					<tr>
						<td class="c-menu">'.$alt_menuObj->topMenu($loadModules->modules,0,'',3).'</td>
						'.($BE_USER->isAdmin() ? '<td class="c-admin">'.$alt_menuObj->adminButtons().'</td>' : '').'
						<td class="c-logout">'.$alt_menuObj->topButtons().'</td>
					</tr>
				</table>';

				// End page:
			$this->content.=$TBE_TEMPLATE->endPage();
		} else {
				// Make dummy content:
			$this->dummyContent();
		}
	}

	/**
	 * Creates the dummy content of the top frame if no menu - which is a blank page.
	 *
	 * @return	void
	 */
	function dummyContent()	{
		global $TBE_TEMPLATE;

			// Start page
		$TBE_TEMPLATE->docType = 'xhtml_trans';
		$this->content.=$TBE_TEMPLATE->startPage('Top frame dummy display');

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
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_topmenu_dummy.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_topmenu_dummy.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_topmenu_dummy');
$SOBE->main();
$SOBE->printContent();
?>