<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Oliver Hader <oh@inpublica.de>
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
 * Main form rendering script for AJAX calls only.
 *
 * $Id$
 *
 * @author	Oliver Hader <oh@inpublica.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   65: class SC_alt_doc_ajax
 *   75:     function init()
 *  118:     function main()
 *  145:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_alt_doc.xml');
require_once (PATH_t3lib.'class.t3lib_tceforms.php');
	// @TODO: Do we really need this here?
require_once (PATH_t3lib.'class.t3lib_clipboard.php');

require_once (PATH_t3lib.'class.t3lib_tcemain.php');
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_transferdata.php');


t3lib_BEfunc::lockRecords();



class SC_alt_doc_ajax {
	var $content;				// Content accumulation
	var $retUrl;				// Return URL script, processed. This contains the script (if any) that we should RETURN TO from the alt_doc.php script IF we press the close button. Thus this variable is normally passed along from the calling script so we can properly return if needed.
	var $R_URL_parts;			// Contains the parts of the REQUEST_URI (current url). By parts we mean the result of resolving REQUEST_URI (current url) by the parse_url() function. The result is an array where eg. "path" is the script path and "query" is the parameters...
	var $R_URL_getvars;			// Contains the current GET vars array; More specifically this array is the foundation for creating the R_URI internal var (which becomes the "url of this script" to which we submit the forms etc.)
	var $R_URI;					// Set to the URL of this script including variables which is needed to re-display the form. See main()
	var $tceforms;				// Contains the instance of TCEforms class.
	var $localizationMode;		// GP var, localization mode for TCEforms (eg. "text")
	var $ajax = array();		// the AJAX paramerts from get/post

	var $doc;					// Document template object

	function init() {
		global $BE_USER;

			// get AJAX parameters
		$this->ajax = t3lib_div::_GP('ajax');

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
			// @TODO: showPalettes etc. should be stored on client side and submitted via each ajax call
		$this->MOD_MENU = array(
			'showPalettes' => '',
			'showDescriptions' => '',
			'disableRTE' => ''
		);
			// Setting virtual document name
		$this->MCONF['name']='xMOD_alt_doc.php';
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

			// Create an instance of the document template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';

			// Initialize TCEforms (rendering the forms)
		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->palettesCollapsed = !$this->MOD_SETTINGS['showPalettes'];
		$this->tceforms->disableRTE = $this->MOD_SETTINGS['disableRTE'];
		$this->tceforms->enableClickMenu = TRUE;
		$this->tceforms->enableTabMenu = TRUE;

			// Clipboard is initialized:
		$this->tceforms->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
		$this->tceforms->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session

			// Setting external variables:
		if ($BE_USER->uc['edit_showFieldHelp']!='text' && $this->MOD_SETTINGS['showDescriptions'])	$this->tceforms->edit_showFieldHelp='text';
	}

	/**
	 * The main function for the AJAX call.
	 * Checks if the requested function call is valid and forwards the request to t3lib_TCEforms_inline.
	 * The out is written to $this->content
	 *
	 * @return	void
	 */
	function main() {
		header('Expires: Fri, 27 Nov 1981 09:04:00 GMT');
		header('Last-Modified: '.gmdate("D, d M Y H:i:s").' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-type: text/javascript; charset=utf-8');

		$this->content = '';

		if (is_array($this->ajax) && count($this->ajax)) {
				// the first argument is the method that should handle the AJAX call
			$method = array_shift($this->ajax);

				// Security check
			if (!in_array($method, array('createNewRecord', 'setExpandedCollapsedState'))) {
				return false;
			}

				// Perform the requested action:
			$this->tceforms->inline->initForAJAX($method, $this->ajax);
			$this->content = call_user_func_array(
				array(&$this->tceforms->inline, $method),
				$this->ajax
			);
		}
	}

	/**
	 * Performs the output of $this->content.
	 *
	 * @return	void
	 */
	function printContent() {
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc_ajax.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc_ajax.php']);
}






// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_doc_ajax');

// Main:
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>