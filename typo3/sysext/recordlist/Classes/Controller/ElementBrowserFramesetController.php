<?php
namespace TYPO3\CMS\Recordlist\Controller;

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
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:TYPO3_Element_Browser'));
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