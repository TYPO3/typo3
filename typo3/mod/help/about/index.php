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
 * Module: About
 *
 * This document shows some standard-information for TYPO3 CMS: About-text, version number and so on.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

 
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
include (PATH_typo3."sysext/lang/locallang_mod_help_about.php");
$BE_USER->modAccess($MCONF,1);



// ***************************
// Script Classes
// ***************************
class SC_mod_help_about_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	var $content;
	
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $TBE_TEMPLATE,$TYPO_VERSION;
		$this->MCONF = $GLOBALS["MCONF"];
		
		// **************************
		// Main
		// **************************
		$TBE_TEMPLATE->bgColor="#cccccc";
		$this->content.=$TBE_TEMPLATE->startPage("About");
		
		$text='
		<div align="center"><b>'.$LANG->getLL("welcome").'</b></div><BR>
		<br>
		';
		$minorText =sprintf($LANG->getLL("minor"), 'TYPO3 Ver. '.$TYPO_VERSION.', Copyright (c) 1998-2003', 'Kasper Skårhøj');
		$content='
		<DIV align="center">
			<TABLE border="0" cellspacing="0" cellpadding="0" width="333" bgcolor="#cccccc">
				<TR>
					<TD><IMG src="'.$BACK_PATH.'gfx/typo3logo.gif" width="333" height="43" vspace="10">
					</TD>
				</TR>
				<TR>
					<TD bgcolor="black">
						<TABLE width="100%" border="0" cellspacing="1" cellpadding="10">
							<TR>
								<TD bgcolor="'.$TBE_TEMPLATE->bgColor.'"><FONT face="verdana,arial,helvetica" size="2">
								TYPO3 Information
								<BR></FONT>
								
		'.$text.$minorText.'
								
								</TD>
							</TR>
						</TABLE>
					</TD>
				</TR>
			</TABLE>
		</DIV>
		';
		$this->content.=$content;
		$this->content.=$TBE_TEMPLATE->endPage();
	}
	function printContent()	{
		echo $this->content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/help/about/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/mod/help/about/index.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_help_about_index");
$SOBE->main();
$SOBE->printContent();
?>