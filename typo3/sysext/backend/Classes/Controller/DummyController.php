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
	 */
	public function printContent() {
		echo $this->content;
	}

}
