<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Module: Web>Info
 * Presents various page related information from extensions
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   70: class SC_mod_web_info_index extends t3lib_SCbase
 *   82:     function main()
 *  169:     function printContent()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_web_info.xml');
require_once (PATH_t3lib.'class.t3lib_scbase.php');

$BE_USER->modAccess($MCONF,1);



/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can connect their sub-modules
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_web_info_index extends t3lib_SCbase {

		// Internal, dynamic:
	var $be_user_Array;
	var $CALC_PERMS;
	var $pageinfo;

	/**
	 * Initialize module header etc and call extObjContent function
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH;

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			$this->CALC_PERMS = $BE_USER->calcPerms($this->pageinfo);
			if ($BE_USER->user['admin'] && !$this->id)	{
				$this->pageinfo=array('title' => '[root-level]','uid'=>0,'pid'=>0);
			}

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->docType = 'xhtml_trans';
			$this->doc->tableLayout = Array (
				'0' => Array (
					'0' => Array('<td valign="top"><b>','</b></td>'),
					"defCol" => Array('<td><img src="'.$this->doc->backPath.'clear.gif" width="10" height="1" alt="" /></td><td valign="top"><b>','</b></td>')
				),
				"defRow" => Array (
					"0" => Array('<td valign="top">','</td>'),
					"defCol" => Array('<td><img src="'.$this->doc->backPath.'clear.gif" width="10" height="1" alt="" /></td><td valign="top">','</td>')
				)
			);

				// JavaScript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL)	{	//
					document.location = URL;
				}
			');
			$this->doc->postCode=$this->doc->wrapScriptTags('
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');


				// Setting up the context sensitive menu:
			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.=$CMparts[0];
			$this->doc->postCode.= $CMparts[2];

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.
				$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path',1).': '.
				'<span title="'.htmlspecialchars($this->pageinfo['_thePathFull']).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],-50)).'</span>';

				// Draw the header.
			$this->doc->form = '<form action="index.php" method="post" name="webinfoForm">';
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);


			$vContent = $this->doc->getVersionSelector($this->id,1);
			if ($vContent)	{
				$this->content.=$this->doc->section('',$vContent);
			}

			$this->extObjContent();




				// Info Module CSH:
			$this->content.= t3lib_BEfunc::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '<br/>|', FALSE, 'margin-top: 30px;');

				// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Print module content (from $this->content)
	 *
	 * @return	void
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/web/info/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/web/info/index.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_web_info_index');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->checkExtObj();	// Checking for first level external objects

// Repeat Include files! - if any files has been added by second-level extensions
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->checkSubExtObj();	// Checking second level external objects

$SOBE->main();
$SOBE->printContent();
?>
