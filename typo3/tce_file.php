<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 *
 * This script serves as the fileadministration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 *
 * For syntax and API information, see the document 'TYPO3 Core APIs' 
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 */
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
require_once (PATH_t3lib.'class.t3lib_extfilefunc.php');











/**
 * Script Class, handling the calling of methods in the file admin classes.
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_tce_file {
	var $include_once=array();
	var $CB;
	var $file;
	var $redirect;
	
	/**
	 * Registering Incoming data
	 * 
	 * @return	void		
	 */
	function init()	{
		$this->file = t3lib_div::GPvar('file');
		$this->redirect = t3lib_div::GPvar('redirect');

		$this->CB = t3lib_div::GPvar('CB');
		if (is_array($this->CB))	{
			$this->include_once[]=PATH_t3lib.'class.t3lib_clipboard.php';
		}
	}

	/**
	 * Initialize the Clipboard. This will fetch the data about files to paste/delete if such an action has been sent.
	 * 
	 * @return	void		
	 */
	function initClipboard()	{
		if (is_array($this->CB))	{
			$clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			$clipObj->initializeClipboard();
			if ($this->CB['paste'])	{
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->file = $clipObj->makePasteCmdArray_file($this->CB['paste'],$this->file);
			}
			if ($this->CB['delete'])	{
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
	function main()	{
		global $FILEMOUNTS,$TYPO3_CONF_VARS,$BE_USER;
		
		// *********************************
		// Initializing
		// *********************************
		$fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$fileProcessor->init($FILEMOUNTS, $TYPO3_CONF_VARS['BE']['fileExtensions']);
		$fileProcessor->init_actionPerms($BE_USER->user['fileoper_perms']);
		$fileProcessor->dontCheckForUnique = t3lib_div::GPvar('overwriteExistingFiles') ? 1 : 0;
		
		// ***************************
		// Checking referer / executing
		// ***************************
		$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost!=$refInfo['host'] && t3lib_div::GPvar('vC')!=$BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
			$fileProcessor->writeLog(0,2,1,'Referer host "%s" and server host "%s" did not match!',array($refInfo['host'],$httpHost));
		} else {
			$fileProcessor->start($this->file);
			$fileProcessor->processData();
		}
		
		if (!$this->redirect)	{
			$this->redirect = 'status_file.php';
		}
	}

	/**
	 * Redirecting to the status script for files.
	 * 
	 * @return	void		
	 */
	function finish()	{
		Header('Location: '.t3lib_div::locationHeaderUrl($this->redirect));

		echo '
		<script type="text/javascript">
				if (confirm(\'System Error:\n\n Some error happend in tce_file.php. Continue?\'))	{
					document.location = \''.$this->redirect.'\';
				}
		</script>
		';
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/tce_file.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/tce_file.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_tce_file');
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->initClipboard();
$SOBE->main();
$SOBE->finish();
?>