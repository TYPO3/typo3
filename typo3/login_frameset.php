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
 * Login frameset
 *
 * This script generates a login-frameset used when the user must relogin.
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML-frames compatible.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   71: class SC_login_frameset
 *   82:     function main()
 *  108:     function printContent()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
define('TYPO3_PROCEED_IF_NO_USER', 1);
require ('init.php');
require ('template.php');








/**
 * Script Class, putting the frameset together.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_login_frameset {

		// Internal, dynamic
	var $content;

	/**
	 * Main function.
	 * Creates the header code in XHTML, then the frameset for the two frames.
	 *
	 * @return	void
	 */
	function main()	{
		global $TYPO3_CONF_VARS;

			// Set doktype:
		$GLOBALS['TBE_TEMPLATE']->docType='xhtml_frames';

		$title = 'TYPO3 Re-Login ('.$TYPO3_CONF_VARS['SYS']['sitename'].')';
		$this->content.=$GLOBALS['TBE_TEMPLATE']->startPage($title);

			// Create the frameset for the window:
		$this->content.='
			<frameset rows="*,1">
				<frame name="login" src="index.php?loginRefresh=1" marginwidth="0" marginheight="0" scrolling="no" noresize="noresize" />
				<frame name="dummy" src="dummy.php" marginwidth="0" marginheight="0" scrolling="auto" noresize="noresize" />
			</frameset>
		';

		$this->content.='
</html>';
	}

	/**
	 * Outputs the page content.
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/login_frameset.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/login_frameset.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_login_frameset');
$SOBE->main();
$SOBE->printContent();

?>