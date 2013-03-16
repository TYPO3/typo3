<?php
namespace TYPO3\CMS\Backend\Controller;

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
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LoginFramesetController {

	// Internal, dynamic
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Main function.
	 * Creates the header code in XHTML, then the frameset for the two frames.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Set doktype:
		$GLOBALS['TBE_TEMPLATE']->docType = 'xhtml_frames';
		$title = 'TYPO3 Re-Login (' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ')';
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage($title);
		// Create the frameset for the window:
		$this->content .= '
			<frameset rows="*,1">
				<frame name="login" src="index.php?loginRefresh=1" marginwidth="0" marginheight="0" scrolling="no" noresize="noresize" />
				<frame name="dummy" src="dummy.php" marginwidth="0" marginheight="0" scrolling="auto" noresize="noresize" />
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