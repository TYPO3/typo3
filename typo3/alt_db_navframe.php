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
 * Page tree for the Web module
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant (almost)
 */


$BACK_PATH='';
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_browsetree.php');



// ***************************
// Script Classes
// ***************************
/**
 * Extension class for the t3lib_browsetree class, specially made for browsing pages in the Web module
 * @see class t3lib_browseTree
 */
class localPageTree extends t3lib_browseTree {

	function localPageTree() {
		$this->init();
	}

	function wrapIcon($icon,&$row)	{
			// If the record is locked, present a warning sign.
		if ($lockInfo=t3lib_BEfunc::isRecordLocked("pages",$row["uid"]))	{
			$aOnClick = 'alert('.$GLOBALS['LANG']->JScharCode($lockInfo["msg"]).');return false;';
			$lockIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
				'<img src="gfx/recordlock_warning3.gif" width="17" height="12" vspace=2 border="0" align=top'.t3lib_BEfunc::titleAttrib($lockInfo["msg"]).' alt="" />'.
				'</a>';
		} else $lockIcon="";

			// Add title attribute to input icon tag
		$thePageIcon = substr($icon,0,-1).' '.$this->titleAttrib.'="'.$this->getTitleAttrib($row).'" border="0" />';

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$thePageIcon = $GLOBALS["TBE_TEMPLATE"]->wrapClickMenuOnIcon($thePageIcon,'pages',$row['uid'],0);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo('.$this->getJumpToParm($row).',this,\''.$this->treeName.'\');';
			$thePageIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$thePageIcon.'</a>';
		}
		return $thePageIcon.$lockIcon;
	}
}

/**
 * Main script class
 */
class SC_alt_db_navframe {
	var $content;
	var $pagetree;
	var $doc;	
	
	/**
	 * Initialiation
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;

		$this->pagetree = t3lib_div::makeInstance('localPageTree');
		$this->pagetree->ext_IconMode = $BE_USER->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
		$this->pagetree->thisScript = 'alt_db_navframe.php';
		$this->pagetree->addField('alias');
		$this->pagetree->addField('shortcut');
		$this->pagetree->addField('shortcut_mode');
		$this->pagetree->addField('mount_pid');
		$this->pagetree->addField('url');

		$currentSubScript = t3lib_div::GPvar('currentSubScript');

		$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');

			// Create template object:
		$this->doc = t3lib_div::makeInstance('template');

			// Hmmm, setting "xhtml_trans" for the page will unfortunately break the Context Sensitive menu in Mozilla! But apart from that - and duplicate ID's for same page in different  mounts - the document checks out well as XHTML
#		$this->doc->docType='xhtml_trans';

			// Setting backPath
		$this->doc->backPath = $BACK_PATH;

			// Setting JavaScript for menu.
		$this->doc->JScode=$this->doc->wrapScriptTags(
	($currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($currentSubScript).'");':'').'
	
	function jumpTo(id,linkObj)	{
		var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?id="+id;

		if (top.condensedMode)	{
			top.content.document.location=theUrl;
		} else {
			parent.list_frame.document.location=theUrl;
		}
        '.($this->doHighlight?'hilight_row("pages"+top.fsMod.recentIds["web"],"pages"+id);':'').'
		'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}').'
		return false;
	}
	function refresh_nav()	{
		window.setTimeout("_refresh_nav();",0);
	}
	function _refresh_nav()	{
		document.location="'.$this->pagetree->thisScript.'?unique='.time().'";
	}

    function hilight_row(old_rowid,new_rowid) {
       if(document.all) {
         if(document.all.item(old_rowid)) {
           document.all.item(old_rowid).style.backgroundColor="";
         }
         if(document.all.item(new_rowid)) {
          document.all.item(new_rowid).style.backgroundColor="'.
		  	t3lib_div::modifyHTMLColorAll($this->doc->bgColor,-20).
			'";
         }
       } else {
         if(document.getElementsByName) {
           old_row_obj = document.getElementsByName(old_rowid)[0];
           new_row_obj = document.getElementsByName(new_rowid)[0];
           bgc = document.createAttribute("bgcolor");
           bgc.value="'.
		  	t3lib_div::modifyHTMLColorAll($this->doc->bgColor,-20).
			'";
           if(old_row_obj) {
             old_row_obj.removeAttribute("bgcolor");
           }
           if(new_row_obj) {
             new_row_obj.setAttributeNode(bgc);
           }
         }
       }
    }
	
	'.(t3lib_div::GPvar('cMR')?"jumpTo(top.fsMod.recentIds['web'],'');":"").';
		');

			// Click menu code is added:
		$CMparts=$this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];
	}

	/**
	 * Main function
	 */
	function main()	{
		global $LANG,$CLIENT;

			// Produce browse-tree:
		$tree = $this->pagetree->getBrowsableTree();
		/*
		if ($CLIENT['BROWSER']=='konqu')	{
				// Where <nobr> does not work, this will secure non-breaks in lines:
			$tree = '<table border=0 cellspacing=0 cellpadding=0><tr><td nowrap>'.$tree.'</td></tr></table>';
		}
		*/

		$this->content='';
		$this->content.=$this->doc->startPage('Page tree');
		$this->content.=$tree;
		$this->content.='<br />
			<a href="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'">'.
			'<img src="gfx/refresh_n.gif" width="14" hspace="2" height="14" hspace="4" border="0" align="top" title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'" alt="" />'.
			$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'</a>
			<br /><br />';

			// Adding highlight - JavaScript
		if ($this->doHighlight)	$this->content .=$this->doc->wrapScriptTags('
			rowid="pages"+top.fsMod.recentIds["web"];
			hilight_row("",rowid);
		');
	}

	/**
	 * Output tree.
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_db_navframe.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_db_navframe.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_db_navframe');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
