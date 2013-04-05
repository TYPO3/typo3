<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2031 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Script Class for showing the history module of TYPO3s backend
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see class.show_rechis.inc
 */
class ElementHistoryController {

	// Internal:
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * Initialize the module output
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Create internal template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/show_rechis.html');
		// Start the page header:
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
	}

	/**
	 * Generate module output
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Start history object
		$historyObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\History\\RecordHistory');
		// Get content:
		$this->content .= $historyObj->main();
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CONTENT'] = $this->content;
		$markers['CSH'] = $docHeaderButtons['csh'];
		// Build the <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'back' => ''
		);
		// CSH
		$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'history_log', $GLOBALS['BACK_PATH'], '', TRUE);
		// Start history object
		$historyObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\History\\RecordHistory');
		if ($historyObj->returnUrl) {
			$buttons['back'] = '<a href="' . htmlspecialchars($historyObj->returnUrl) . '" class="typo3-goBack">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back') . '</a>';
		}
		return $buttons;
	}

}


?>