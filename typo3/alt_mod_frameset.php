<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Creates the frameset for 'Frameset modules' like Web>* and File>*
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant content (with exception of a few attributes for the <frameset> tags)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   63: class SC_alt_mod_frameset 
 *   82:     function main()	
 *  119:     function printContent()	
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require ('init.php');
require ('template.php');




/**
 * Script Class for rendering the frameset which keeps the navigation and list frames together for socalled "Frameset modules"
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_mod_frameset {

		// Internal, static:
	var $defaultWidth = 245;		// Default width of the navigation frame. Can be overridden from $TBE_STYLES['dims']['navFrameWidth'] (alternative default value) AND from User TSconfig
	
		// Internal, dynamic:
	var $content;					// Content accumulation.
	






	/**
	 * Creates the header and frameset for the module/submodules
	 * 
	 * @return	void		
	 */
	function main()	{
		global $BE_USER,$TBE_TEMPLATE,$TBE_STYLES;
		
			// Processing vars:
		$width = $BE_USER->uc['navFrameWidth'];
		$width = intval($width)?intval($width):($TBE_STYLES['dims']['navFrameWidth'] ? intval($TBE_STYLES['dims']['navFrameWidth']) : $this->defaultWidth);
		
			// Navigation frame URL:
		$script = t3lib_div::GPvar('script');
		$nav = t3lib_div::GPvar('nav');
		$URL_nav = htmlspecialchars($nav.'?currentSubScript='.rawurlencode($script));
		
			// List frame URL:
		$exScript = t3lib_div::GPvar('exScript');
		$id = t3lib_div::GPvar('id');
		$URL_list = htmlspecialchars($exScript?$exScript:($script.($id?'?id='.rawurlencode($id):'')));
		
			// Start page output
		$TBE_TEMPLATE->docType='xhtml_frames';
		$this->content = $TBE_TEMPLATE->startPage('Frameset');
		$this->content.= '

	<frameset cols="'.$width.',8,*" framespacing="0" frameborder="0" border="0">
		<frame name="nav_frame" src="'.$URL_nav.'" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
		<frame name="border_frame" src="border.html" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
		<frame name="list_frame" src="'.$URL_list.'" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
	</frameset>

</html>
';
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
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_mod_frameset.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_mod_frameset.php']);
}











// ******************************
// Starting document output
// ******************************

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_mod_frameset');
$SOBE->main();
$SOBE->printContent();
?>