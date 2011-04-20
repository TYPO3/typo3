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
 * Redirects to real module if shortcut pressed
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML-trans compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   68: class SC_listframe_loader
 *   75:     function main()
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require ('init.php');
require ('template.php');










/**
 * Script Class for redirecting shortcut actions to the correct script
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_listframe_loader {

	/**
	 * Main content generated
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_TEMPLATE;

		$TBE_TEMPLATE->divClass='';
		$this->content.=$TBE_TEMPLATE->startPage('List Frame Loader');
		$this->content.=$TBE_TEMPLATE->wrapScriptTags('
			var theUrl = top.getModuleUrl("");
			if (theUrl)	window.location.href=theUrl;
		');
			// End page:
		$this->content.=$TBE_TEMPLATE->endPage();

			// Output:
		echo $this->content;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/listframe_loader.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/listframe_loader.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_listframe_loader');
$SOBE->main();

?>