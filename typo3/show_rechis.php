<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Shows information about a database or file item
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML Compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$BACK_PATH='';
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:lang/locallang_show_rechis.xml');
require_once ('class.show_rechis.inc');

/**
 * Script Class for showing the history module of TYPO3s backend
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see class.show_rechis.inc
 */
class SC_show_rechis {

		// Internal:
	var $content;

	/**
	 * Document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;

	/**
	 * Initialize the module output
	 *
	 * @return	void
	 */
	function init()	{

			// Create internal template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/show_rechis.html');

			// Start the page header:
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
	}

	/**
	 * Generate module output
	 *
	 * @return	void
	 */
	function main()	{

			// Start history object
		$historyObj = t3lib_div::makeInstance('recordHistory');

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
	 * @return	void
	 */
	function printContent()	{
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'back' => ''
		);

			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'history_log', $GLOBALS['BACK_PATH'], '', TRUE);

			// Start history object
		$historyObj = t3lib_div::makeInstance('recordHistory');

		if ($historyObj->returnUrl)	{
			$buttons['back'] = '<a href="' . htmlspecialchars($historyObj->returnUrl) . '" class="typo3-goBack">' . t3lib_iconWorks::getSpriteIcon('actions-view-go-back') . '</a>';
		}

		return $buttons;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/show_rechis.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/show_rechis.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_show_rechis');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>