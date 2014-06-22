<?php
namespace TYPO3\CMS\Backend\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Script Class, putting the frameset together.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LoginFramesetController {

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * Main function.
	 * Creates the header code in XHTML, then the frameset for the two frames.
	 *
	 * @return void
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
	 */
	public function printContent() {
		echo $this->content;
	}

}
