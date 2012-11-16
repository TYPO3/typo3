<?php
namespace TYPO3\CMS\Backend\Controller;

/**
 * Script Class for redirecting shortcut actions to the correct script
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ListFrameLoaderController {

	/**
	 * Main content generated
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		$GLOBALS['TBE_TEMPLATE']->divClass = '';
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage('List Frame Loader');
		$this->content .= $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
			var theUrl = top.getModuleUrl("");
			if (theUrl)	window.location.href=theUrl;
		');
		// End page:
		$this->content .= $GLOBALS['TBE_TEMPLATE']->endPage();
		// Output:
		echo $this->content;
	}

}


?>