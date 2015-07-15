<?php
namespace TYPO3\CMS\Backend\Controller;

/*
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
 */
class DummyController {

	/**
	 * @var string
	 */
	public $content;

	/**
	 * Create content for the dummy script - outputting a blank page.
	 *
	 * @return void
	 */
	public function main() {
		// Start page
		$this->content .= $this->getDocumentTemplate()->startPage('Dummy document');
		// End page:
		$this->content .= $this->getDocumentTemplate()->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Returns an instance of DocumentTemplate
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

}
