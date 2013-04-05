<?php
namespace TYPO3\CMS\Recordlist\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Script Class, putting the frameset together.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ElementBrowserFramesetController {

	// Internal, dynamic
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Main function.
	 * Creates the header code in XHTML, the JavaScript, then the frameset for the two frames.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Setting GPvars:
		$mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');
		$bparams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('bparams');
		// Set doktype:
		$GLOBALS['TBE_TEMPLATE']->docType = 'xhtml_frames';
		$GLOBALS['TBE_TEMPLATE']->JScode = $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
				function closing() {	//
					close();
				}
				function setParams(mode,params) {	//
					parent.content.location.href = "browse_links.php?mode="+mode+"&bparams="+params;
				}
				if (!window.opener) {
					alert("ERROR: Sorry, no link to main window... Closing");
					close();
				}
		');
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:TYPO3_Element_Browser'));
		// URL for the inner main frame:
		$url = $GLOBALS['BACK_PATH'] . 'browse_links.php?mode=' . rawurlencode($mode) . '&bparams=' . rawurlencode($bparams);
		// Create the frameset for the window:
		// Formerly there were a ' onunload="closing();"' in the <frameset> tag - but it failed on Safari browser on Mac unless the handler was "onUnload"
		$this->content .= '
			<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
				<frame name="content" src="' . htmlspecialchars($url) . '" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
				<frame name="menu" src="' . $GLOBALS['BACK_PATH'] . 'dummy.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		';
		$this->content .= '
</html>';
	}

	/**
	 * Outputs the page content.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

}


?>