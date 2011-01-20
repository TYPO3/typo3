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
 * TCE gateway (TYPO3 Core Engine) for database handling
 * This script is a gateway for POST forms to class.t3lib_TCEmain that manipulates all information in the database!!
 * For syntax and API information, see the document 'TYPO3 Core APIs'
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   78: class SC_tce_db
 *  106:     function init()
 *  162:     function initClipboard()
 *  182:     function main()
 *  218:     function finish()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require ('init.php');
require ('template.php');











/**
 * Script Class, creating object of t3lib_TCEmain and sending the posted data to the object.
 * Used by many smaller forms/links in TYPO3, including the QuickEdit module.
 * Is not used by alt_doc.php though (main form rendering script) - that uses the same class (TCEmain) but makes its own initialization (to save the redirect request).
 * For all other cases than alt_doc.php it is recommended to use this script for submitting your editing forms - but the best solution in any case would probably be to link your application to alt_doc.php, that will give you easy form-rendering as well.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_tce_db {

		// Internal, static: GPvar
	var $flags;			// Array. Accepts options to be set in TCE object. Currently it supports "reverseOrder" (boolean).
	var $data;			// Data array on the form [tablename][uid][fieldname] = value
	var $cmd;			// Command array on the form [tablename][uid][command] = value. This array may get additional data set internally based on clipboard commands send in CB var!
	var $mirror;		// Array passed to ->setMirror.
	var $cacheCmd;		// Cache command sent to ->clear_cacheCmd
	var $redirect;		// Redirect URL. Script will redirect to this location after performing operations (unless errors has occured)
	var $prErr;			// Boolean. If set, errors will be printed on screen instead of redirection. Should always be used, otherwise you will see no errors if they happen.
#	var $_disableRTE;
	var $CB;			// Clipboard command array. May trigger changes in "cmd"
	var $vC;			// Verification code
	var $uPT;			// Boolean. Update Page Tree Trigger. If set and the manipulated records are pages then the update page tree signal will be set.
	var $generalComment;	// String, general comment (for raising stages of workspace versions)

		// Internal, dynamic:
	var $include_once=array();		// Files to include after init() function is called:

	/**
	 * TYPO3 Core Engine
	 *
	 * @var t3lib_TCEmain
	 */
	var $tce;




	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER;

			// GPvars:
		$this->flags = t3lib_div::_GP('flags');
		$this->data = t3lib_div::_GP('data');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->mirror = t3lib_div::_GP('mirror');
		$this->cacheCmd = t3lib_div::_GP('cacheCmd');
		$this->redirect = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('redirect'));
		$this->prErr = t3lib_div::_GP('prErr');
		$this->_disableRTE = t3lib_div::_GP('_disableRTE');
		$this->CB = t3lib_div::_GP('CB');
		$this->vC = t3lib_div::_GP('vC');
		$this->uPT = t3lib_div::_GP('uPT');
		$this->generalComment = t3lib_div::_GP('generalComment');

			// Creating TCEmain object
		$this->tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$this->tce->stripslashes_values=0;
		$this->tce->generalComment = $this->generalComment;

			// Configuring based on user prefs.
		if ($BE_USER->uc['recursiveDelete'])	{
			$this->tce->deleteTree = 1;	// True if the delete Recursive flag is set.
		}
		if ($BE_USER->uc['copyLevels'])	{
			$this->tce->copyTree = t3lib_div::intInRange($BE_USER->uc['copyLevels'],0,100);	// Set to number of page-levels to copy.
		}
		if ($BE_USER->uc['neverHideAtCopy'])	{
			$this->tce->neverHideAtCopy = 1;
		}

		$TCAdefaultOverride = $BE_USER->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$this->tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}

			// Reverse order.
		if ($this->flags['reverseOrder'])	{
			$this->tce->reverseOrder=1;
		}

#		$this->tce->disableRTE = $this->_disableRTE;

			// Clipboard?
		if (is_array($this->CB))	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_clipboard.php';
		}
	}

	/**
	 * Clipboard pasting and deleting.
	 *
	 * @return	void
	 */
	function initClipboard()	{
		if (is_array($this->CB))	{
			$clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			$clipObj->initializeClipboard();
			if ($this->CB['paste'])	{
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->cmd = $clipObj->makePasteCmdArray($this->CB['paste'],$this->cmd);
			}
			if ($this->CB['delete'])	{
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->cmd = $clipObj->makeDeleteCmdArray($this->cmd);
			}
		}
	}

	/**
	 * Executing the posted actions ...
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$TYPO3_CONF_VARS;

			// LOAD TCEmain with data and cmd arrays:
		$this->tce->start($this->data,$this->cmd);
		if (is_array($this->mirror))	{$this->tce->setMirror($this->mirror);}

			// Checking referer / executing
		$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost!=$refInfo['host'] && $this->vC!=$BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
			$this->tce->log('',0,0,0,1,'Referer host "%s" and server host "%s" did not match and veriCode was not valid either!',1,array($refInfo['host'],$httpHost));
		} else {
				// Register uploaded files
			$this->tce->process_uploads($_FILES);

				// Execute actions:
			$this->tce->process_datamap();
			$this->tce->process_cmdmap();

				// Clearing cache:
			$this->tce->clear_cacheCmd($this->cacheCmd);

				// Update page tree?
			if ($this->uPT && (isset($this->data['pages'])||isset($this->cmd['pages'])))	{
				t3lib_BEfunc::setUpdateSignal('updatePageTree');
			}
		}
	}

	/**
	 * Redirecting the user after the processing has been done.
	 * Might also display error messages directly, if any.
	 *
	 * @return	void
	 */
	function finish()	{
			// Prints errors, if...
		if ($this->prErr)	{
			$this->tce->printLogErrorMessages($this->redirect);
		}

		if ($this->redirect && !$this->tce->debug) {
			t3lib_utility_Http::redirect($this->redirect);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/tce_db.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/tce_db.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_tce_db');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$formprotection = t3lib_formprotection_Factory::get('t3lib_formprotection_BackendFormProtection');

if ($formprotection->validateToken(t3lib_div::_GP('formToken'), 'tceAction')) {
	$SOBE->initClipboard();
	$SOBE->main();

		// This is done for the clear cache menu, so that it gets a new token
		// making it possible to clear cache several times.
	if (t3lib_div::_GP('ajaxCall')) {
		$token = array();
		$token['value'] = $formprotection->generateToken('tceAction');
		$token['name'] = 'formToken';
			// This will be used by clearcachemenu.js to replace the token for the next call
		echo t3lib_BEfunc::getUrlToken('tceAction');
	}
}
$formprotection->persistTokens();
$SOBE->finish();

?>
