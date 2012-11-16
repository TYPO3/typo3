<?php
namespace TYPO3\CMS\Backend\Controller;

/**
 * Script Class, creating the content for the dummy script - which is just blank output.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DummyController {

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Create content for the dummy script - outputting a blank page.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Start page
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage('Dummy document');
		// End page:
		$this->content .= $GLOBALS['TBE_TEMPLATE']->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

}


?>