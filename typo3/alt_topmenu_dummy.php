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
 * Alternative top menu
 * Displays a horizontal menu with the same items as the default left vertical menu
 * in the backend frameset. Only the icons are displayed and linked.
 * Will appear as the default document in the top frame if configured to appear.
 * This is the default menu used during "condensed mode"
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant content
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class SC_alt_topmenu_dummy
 *   82:     function main()
 *  162:     function dummyContent()
 *  178:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 * @deprecated since TYPO3 4.5, this file will be removed in TYPO3 4.7. The TYPO3 backend is using typo3/backend.php with less frames, which makes this file obsolete.
 */


require ('init.php');
require ('template.php');
require_once ('class.alt_menu_functions.inc');


t3lib_div::deprecationLog('alt_topmenu_dummy.php is deprecated since TYPO3 4.5, this file will be removed in TYPO3 4.7. The TYPO3 backend is using typo3/backend.php with less frames, which makes this file obsolete.');




/**
 * Script Class for rendering the topframe dummy view.
 * In the case where TYPO3 backend is configured to show the menu in the top frame this class will render the horizontal line of module icons in the top frame.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
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

			// Remember if noMenuMode is set to 'icons' or not because the hook will be ignored in this case.
		if (!strcmp($GLOBALS['BE_USER']->uc['noMenuMode'],'icons'))	{ $iconMenuMode = true; }

		$contentArray=array();

			// Hook for adding content to the topmenu. Only works if noMenuMode is not set to "icons" in the users setup!
		if (!$iconMenuMode && is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_topmenu_dummy.php']['fetchContentTopmenu']))	{
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_topmenu_dummy.php']['fetchContentTopmenu'] as $classRef)	{
				$hookObj = t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj,'fetchContentTopmenu_processContent'))	{
					$tempContent = $hookObj->fetchContentTopmenu_processContent($this);

						// Placement priority handling.
					if (is_int($hookObj->priority) && ($hookObj->priority>=1 && $hookObj->priority<=9)) {
						$priority = $hookObj->priority;
					} else $priority = 5;

					$overrulestyle = isset($hookObj->overrulestyle) ? $hookObj->overrulestyle : 'padding-top: 4px;';
					$contentArray[$priority][] = '<td class="c-menu" style="'.$overrulestyle.'">'.$tempContent.'</td>';
				}
			}
			ksort($contentArray);
		}

			// If noMenuMode is set to 'icons' or if a hook was found, display menu instead of nothingness
		if ($iconMenuMode || count($contentArray))	{

				// Loading the modules for this backend user:
			$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
			$loadModules->observeWorkspaces = TRUE;
			$loadModules->load($GLOBALS['TBE_MODULES']);

				// Creating menu object:
			$alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');

				// Start page
			$GLOBALS['TBE_TEMPLATE']->bodyTagId.= '-iconmenu';
			$GLOBALS['TBE_TEMPLATE']->JScodeArray[] = $alt_menuObj->generateMenuJScode($loadModules->modules);

			$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage('Top frame icon menu');

			if ($iconMenuMode)	{
				$contentArray[0][] = '<td class="c-menu">'.$alt_menuObj->topMenu($loadModules->modules,0,'',3).'</td>';
				if ($GLOBALS['BE_USER']->isAdmin())	{
					$contentArray[1][] = '<td class="c-admin">'.$alt_menuObj->adminButtons().'</td>';
				}
				$contentArray[2][] = '<td class="c-logout">'.$alt_menuObj->topButtons().'</td>';
			}

				// Make menu and add it:
			$this->content.='

				<!--
				  Alternative module menu made of icons, displayed in top frame:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-topMenu">
					<tr>';

			foreach ($contentArray as $key=>$menucontent)	{
				$this->content .= implode(LF, $menucontent);
			}

			$this->content.='
					</tr>
				</table>';

				// End page:
			$this->content .= $GLOBALS['TBE_TEMPLATE']->endPage();
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


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_topmenu_dummy.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_topmenu_dummy.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_topmenu_dummy');
$SOBE->main();
$SOBE->printContent();

?>