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
 * No-document script
 *
 * $Id$
 *  
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   68: class SC_alt_doc_nodoc 
 *   74:     function init()	
 *   91:     function main()	
 *  127:     function printContent()	
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_alt_doc.php');


if (t3lib_extMgm::isLoaded('taskcenter') && t3lib_extMgm::isLoaded('taskcenter_recent'))	{
	require_once(t3lib_extMgm::extPath('taskcenter').'task/class.mod_user_task.php');
	require_once(t3lib_extMgm::extPath('taskcenter_recent').'class.tx_taskcenterrecent.php');
}



/**
 * Script Class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_doc_nodoc {
	var $content;
	
	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BACK_PATH;

		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->bodyTagMargins['x']=5;
		$this->doc->bodyTagMargins['y']=5;
		$this->doc->backPath = $BACK_PATH;
		
		$this->content='';
		$this->content.=$this->doc->startPage('TYPO3 Edit Document');
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$msg=array();
		$msg[]=$LANG->getLL('noDocuments_msg');
		$a_wl = $BE_USER->check('modules','web_list');
		$a_wp = t3lib_extMgm::isLoaded('cms') && $BE_USER->check('modules','web_layout');
		if ($a_wl || $a_wp)	{
			$msg_2 = array();
			if ($a_wp)	{
				$msg_2[]='<strong><a href="#" onClick="top.goToModule(\'web_layout\'); return false;">'.$LANG->getLL("noDocuments_pagemodule").' <img src="'.t3lib_extMgm::extRelPath("cms").'/layout/layout.gif" width="14" height="12" border="0" align="top"></a></strong>';
				if ($a_wl)	$msg_2[]=$LANG->getLL("noDocuments_OR");
			}
			if ($a_wl)	{
				$msg_2[]='<strong><a href="#" onClick="top.goToModule(\'web_list\'); return false;">'.$LANG->getLL("noDocuments_listmodule").' <img src="mod/web/list/list.gif" width="14" height="12" border="0" align="top"></a></strong>';
			}
			$msg[]="<BR><BR>".sprintf($LANG->getLL("noDocuments_msg2"),implode(" ",$msg_2));
		}
		
		if ($BE_USER->check("modules","user_task") && t3lib_extMgm::isLoaded("taskcenter_recent"))	{
			$modObj = t3lib_div::makeInstance("tx_taskcenterrecent");
			$modObj->backPath = $BACK_PATH;
			$modObj->BE_USER = $BE_USER;
			$modObj->perms_clause = $BE_USER->getPagePermsClause(1);

			$msg[]="<BR><BR>".$LANG->getLL("noDocuments_msg3")."<BR><BR>".$modObj->_renderRecent();
		}
		
		$this->content.=$this->doc->section($LANG->getLL("noDocuments"),implode(" ",$msg),0,1);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content.$this->doc->endPage();
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc_nodoc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc_nodoc.php']);
}











// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_doc_nodoc');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>