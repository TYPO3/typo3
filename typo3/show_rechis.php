<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML Compliant 
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   80: class SC_show_rechis 
 *   89:     function init()	
 *  104:     function main()	
 *  141:     function printContent()	
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 
 
$BACK_PATH='';
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
require_once (PATH_t3lib.'class.t3lib_diff.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');
require ('sysext/lang/locallang_show_rechis.php');
require ('class.show_rechis.inc');









/**
 * Script Class
 * 
 * HTTP_GET_VARS:
 * $table	:		Record table (or filename)
 * $uid	:		Record uid  (or "" when filename)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_show_rechis {
	var $content;
	var $doc;	

	/**
	 * Initialize the module output
	 * 
	 * @return	void		
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->doc = t3lib_div::makeInstance('mediumDoc');
		
		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);
	}

	/**
	 * Generate module output
	 * 
	 * @return	void		
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$historyObj = t3lib_div::makeInstance('recordHistory');
		
		// **********************************************************
		// If link from sys log:
		// sh_uid is the id-number of the sys_history log item
		// **********************************************************
		if (t3lib_div::GPvar('sh_uid'))	{
			$this->content.=$historyObj->displaySysHistoryEntry(t3lib_div::GPvar('sh_uid'));
		}
		
		// **********************************************************
		// If link to element:
		// **********************************************************
		if (t3lib_div::GPvar('element'))	{
			if (t3lib_div::GPvar('revert') && t3lib_div::GPvar('sumUp'))	{
				$this->content.=$historyObj->revertToPreviousValues(t3lib_div::GPvar('element'),t3lib_div::GPvar('revert'));
			}
			if (t3lib_div::GPvar('saveState'))	{
				$this->content.=$historyObj->saveState(t3lib_div::GPvar('element'),t3lib_div::GPvar('saveState'));
			}
			$this->content.=$historyObj->displayHistory(t3lib_div::GPvar('element'));
		}
		
		// **********************************************************
		// Return link:
		// **********************************************************
		$this->content.=t3lib_div::GPvar('returnUrl') ? $this->doc->section($LANG->getLL('return'),'<a href="'.htmlspecialchars(t3lib_div::GPvar('returnUrl')).'" class="typo3-goBack"><img src="gfx/goback.gif" width="14" height="14" hspace="2" border="0" align="top" alt="" /><strong>'.$LANG->getLL('returnLink').'</strong></a>',0,1) : '';
	}

	/**
	 * Print content
	 * 
	 * @return	void		
	 */
	function printContent()	{
		$this->content.=$this->doc->spacer(8);
		$this->content.=$this->doc->middle();
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/show_rechis.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/show_rechis.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_show_rechis');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>