<?php
namespace TYPO3\CMS\Backend\Controller;

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