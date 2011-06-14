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
 * No-document script
 * This is used by eg. the Doc module if no documents is registered as "open" (a concept which is better known from the "classic backend"...)
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class SC_alt_doc_nodoc
 *   84:     function init()
 *  108:     function main()
 *  168:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_alt_doc.xml');

require_once(t3lib_extMgm::extPath('opendocs') . 'class.tx_opendocs.php');


/**
 * Script Class for the "No-doc" display; This shows most recently edited records.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_doc_nodoc {

		// Internal:
	var $content;		// Content accumulation

	/**
	 * Document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;

	/**
	 * Object for backend modules.
	 *
	 * @var t3lib_loadModules
	 */
	var $loadModules;

	/**
	 * Constructor, initialize.
	 *
	 * @return	void
	 */
	function init()	{
			// Start the template object:
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->bodyTagMargins['x']=5;
		$this->doc->bodyTagMargins['y']=5;
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

			// Add JS
		$this->doc->JScode = $this->doc->wrapScriptTags('
		function jump(url, modName, mainModName) {
				// clear information about which entry in nav. tree that might have been highlighted.
			top.fsMod.navFrameHighlightedID = [];

			if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
				top.content.nav_frame.refresh_nav();
			}

			top.nextLoadModuleUrl = url;
			top.goToModule(modName);
		}
		');

			// Start the page:
		$this->content='';
		$this->content.=$this->doc->startPage('TYPO3 Edit Document');

			// Loads the backend modules available for the logged in user.
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->load($GLOBALS['TBE_MODULES']);
	}

	/**
	 * Rendering the content.
	 *
	 * @return	void
	 */
	function main()	{
		$msg=array();

			// Add a message, telling that no documents were open...
		$msg[] = '<p>' . $GLOBALS['LANG']->getLL('noDocuments_msg', 1) . '</p><br />';

			// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

			// Perform some acccess checks:
		$a_wl = $GLOBALS['BE_USER']->check('modules', 'web_list');
		$a_wp = t3lib_extMgm::isLoaded('cms') && $GLOBALS['BE_USER']->check('modules', $pageModule);


			// Finding module images: PAGE
		$imgFile = $GLOBALS['LANG']->moduleLabels['tabs_images']['web_layout_tab'];
		$imgInfo = @getimagesize($imgFile);
		$img_web_layout = is_array($imgInfo) ? '<img src="../'.substr($imgFile,strlen(PATH_site)).'" '.$imgInfo[3].' alt="" />' : '';

			// Finding module images: LIST
		$imgFile = $GLOBALS['LANG']->moduleLabels['tabs_images']['web_list_tab'];
		$imgInfo = @getimagesize($imgFile);
		$img_web_list = is_array($imgInfo) ? '<img src="../'.substr($imgFile,strlen(PATH_site)).'" '.$imgInfo[3].' alt="" />' : '';


			// If either the Web>List OR Web>Page module are active, show the little message with links to those modules:
		if ($a_wl || $a_wp)	{
			$msg_2 = array();
			if ($a_wp)	{	// Web>Page:
				$msg_2[] = '<strong><a href="#" onclick="top.goToModule(\'' . $pageModule . '\'); return false;">' . $GLOBALS['LANG']->getLL('noDocuments_pagemodule', 1) . $img_web_layout . '</a></strong>';
				if ($a_wl) {
					$msg_2[] = $GLOBALS['LANG']->getLL('noDocuments_OR');
				}
			}
			if ($a_wl)	{	// Web>List
				$msg_2[] = '<strong><a href="#" onclick="top.goToModule(\'web_list\'); return false;">' . $GLOBALS['LANG']->getLL('noDocuments_listmodule', 1) . $img_web_list . '</a></strong>';
			}
			$msg[] = '<p>' . sprintf($GLOBALS['LANG']->getLL('noDocuments_msg2', 1), implode(' ', $msg_2)) . '</p><br />';
		}

			// Display the list of the most recently edited documents:
		$modObj = t3lib_div::makeInstance('tx_opendocs');
		$msg[] = '<p>' . $GLOBALS['LANG']->getLL('noDocuments_msg3', TRUE) . '</p><br />' . $modObj->renderMenu();

			// Adding the content:
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('noDocuments'), implode(' ', $msg), 0, 1);
	}

	/**
	 * Printing the content.
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_doc_nodoc.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_doc_nodoc.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_doc_nodoc');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>