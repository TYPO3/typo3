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
 * EXAMPLE SCRIPT! Simply strips HTML of content from RTE
 * Belongs to the "rte" extension
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 

$BACK_PATH="";
require ("init.php");
require ("template.php");
//include("sysext/lang/locallang_rte_user.php");


// ***************************
// Script Classes
// ***************************
class SC_rte_cleaner {
	var $content;
	var $siteURL;
	var $doc;	
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->siteURL = substr(t3lib_div::getIndpEnv("TYPO3_SITE_URL"),0,-1);

		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form = '';
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			var RTEobj = self.parent.parent;
		
			function setSelectedTextContent(content)	{
				var oSel = RTEobj.GLOBAL_SEL;
				var sType = oSel.type;
				if (sType=="Text")	{
					oSel.pasteHTML(content);
				}
			}
		</script>
		';
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->doc->startPage("RTE cleaner");

		$this->content.='
		<script language="javascript" type="text/javascript">
//			alert('.$GLOBALS['LANG']->JScharCode(t3lib_div::GPvar("processContent")).');
			setSelectedTextContent(unescape("'.rawurlencode(strip_tags(t3lib_div::GPvar("processContent"))).'"));
			RTEobj.edHidePopup();
		</script>
		';
	}
	function printContent()	{
		global $SOBE;
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	
	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/rte_cleaner.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/rte_cleaner.php"]);
}





// Make instance:
$SOBE = t3lib_div::makeInstance("SC_rte_cleaner");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>