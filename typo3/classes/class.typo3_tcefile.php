<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  (c) 2009-2010 Benjamin Mack (benni.typo3.org)
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
 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
 * This script serves as the fileadministration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 * Before TYPO3 4.3, it was located in typo3/tce_file.php and redirected back to a
 * $redirectURL. Since 4.3 this class is also used for accessing via AJAX
 *
 *
 * For syntax and API information, see the document 'TYPO3 Core APIs'
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * Revised for TYPO3 4.3 Mar/2009 by Benjamin Mack
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */

require_once(PATH_typo3 . 'template.php');

/**
 * Script Class, handling the calling of methods in the file admin classes.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TYPO3_tcefile {

		// Internal, static: GPvar:
		// Array of file-operations.
	protected $file;
		// Clipboard operations array
	protected $CB;
		// If existing files should be overridden.
	protected $overwriteExistingFiles;
		// VeriCode - a hash of server specific value and other things which
		// identifies if a submission is OK. (see $BE_USER->veriCode())
	protected $vC;
	    // the page where the user should be redirected after everything is done
	protected $redirect;

		// Internal, dynamic:
		// File processor object
	protected $fileProcessor;
	    // the result array from the file processor
	protected $fileData;



	/**
	 * Registering incoming data
	 *
	 * @return	void
	 */
	public function init() {
			// set the GPvars from outside
		$this->file = t3lib_div::_GP('file');
		$this->CB = t3lib_div::_GP('CB');
		$this->overwriteExistingFiles = t3lib_div::_GP('overwriteExistingFiles');
		$this->vC = t3lib_div::_GP('vC');
		$this->redirect = t3lib_div::_GP('redirect');

		$this->initClipboard();
	}

	/**
	 * Initialize the Clipboard. This will fetch the data about files to paste/delete if such an action has been sent.
	 *
	 * @return	void
	 */
	public function initClipboard() {
		if (is_array($this->CB)) {
			$clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			$clipObj->initializeClipboard();
			if ($this->CB['paste']) {
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->file = $clipObj->makePasteCmdArray_file($this->CB['paste'], $this->file);
			}
			if ($this->CB['delete']) {
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->file = $clipObj->makeDeleteCmdArray_file($this->file);
			}
		}
	}

	/**
	 * Performing the file admin action:
	 * Initializes the objects, setting permissions, sending data to object.
	 *
	 * @return	void
	 */
	public function main() {
			// Initializing:
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
		$this->fileProcessor->dontCheckForUnique = ($this->overwriteExistingFiles ? 1 : 0);

			// Checking referer / executing:
		$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost != $refInfo['host']
			&& $this->vC != $GLOBALS['BE_USER']->veriCode()
			&& !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
			&& $GLOBALS['CLIENT']['BROWSER'] != 'flash') {
			$this->fileProcessor->writeLog(0, 2, 1, 'Referer host "%s" and server host "%s" did not match!', array($refInfo['host'], $httpHost));
		} else {
			$this->fileProcessor->start($this->file);
			$this->fileData = $this->fileProcessor->processData();
		}
	}

	/**
	 * Redirecting the user after the processing has been done.
	 * Might also display error messages directly, if any.
	 *
	 * @return	void
	 */
	public function finish() {
			// Prints errors, if there are any
		$this->fileProcessor->printLogErrorMessages($this->redirect);
		t3lib_BEfunc::setUpdateSignal('updateFolderTree');
		if ($this->redirect) {
			t3lib_utility_Http::redirect($this->redirect);
		}
	}

	/**
	 * Handles the actual process from within the ajaxExec function
	 * therefore, it does exactly the same as the real typo3/tce_file.php
	 * but without calling the "finish" method, thus makes it simpler to deal with the
	 * actual return value
	 *
	 *
	 * @param string $params 	always empty.
	 * @param string $ajaxObj	The Ajax object used to return content and set content types
	 * @return void
	 */
	public function processAjaxRequest(array $params, TYPO3AJAX $ajaxObj) {
		$this->init();
		$this->main();
		$errors = $this->fileProcessor->getErrorMessages();
		if (count($errors)) {
			$ajaxObj->setError(implode(',', $errors));
		} else {
			$ajaxObj->addContent('result', $this->fileData);
			if ($this->redirect) {
				$ajaxObj->addContent('redirect', $this->redirect);
			}
			$ajaxObj->setContentFormat('json');
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.typo3_tcefile.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.typo3_tcefile.php']);
}

?>