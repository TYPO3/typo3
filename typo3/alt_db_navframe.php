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
 * Page navigation tree for the Web module
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class localPageTree extends t3lib_browseTree
 *   79:     function localPageTree()
 *   90:     function wrapIcon($icon,&$row)
 *
 *
 *  131: class SC_alt_db_navframe
 *  147:     function init()
 *  235:     function main()
 *  263:     function printContent()
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$BACK_PATH='';
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_browsetree.php');



/**
 * Extension class for the t3lib_browsetree class, specially made for browsing pages in the Web module
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see class t3lib_browseTree
 */
class localPageTree extends t3lib_browseTree {

	var $ext_showPageId;
	var $ext_IconMode;

	/**
	 * Calls init functions
	 *
	 * @return	void
	 */
	function localPageTree() {
		$this->init();
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param	string		Icon IMG code
	 * @param	array		Data row for element.
	 * @return	string		Page icon
	 */
	function wrapIcon($icon,&$row)	{
			// If the record is locked, present a warning sign.
		if ($lockInfo=t3lib_BEfunc::isRecordLocked('pages',$row['uid']))	{
			$aOnClick = 'alert('.$GLOBALS['LANG']->JScharCode($lockInfo['msg']).');return false;';
			$lockIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
				'<img'.t3lib_iconWorks::skinImg('','gfx/recordlock_warning3.gif','width="17" height="12"').' title="'.htmlspecialchars($lockInfo['msg']).'" alt="" />'.
				'</a>';
		} else $lockIcon="";

			// Add title attribute to input icon tag
		$thePageIcon = $this->addTagAttributes($icon, $this->titleAttrib.'="'.$this->getTitleAttrib($row).'"');

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$thePageIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($thePageIcon,'pages',$row['uid'],0);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->treeName.'\');';
			$thePageIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$thePageIcon.'</a>';
		}

			// Add Page ID:
		if ($this->ext_showPageId)	{
			$pageIdStr = '['.$row['uid'].']&nbsp;';
		} else {
			$pageIdStr = '';
		}

		return $thePageIcon.$lockIcon.$pageIdStr;
	}
}












/**
 * Main script class for the page tree navigation frame
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_db_navframe {

		// Internal:
	var $content;
	var $pagetree;
	var $doc;

		// Internal, static: GPvar:
	var $currentSubScript;
	var $cMR;

	/**
	 * Initialiation of the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;

			// Setting GPvars:
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->cMR = t3lib_div::_GP('cMR');

			// Create page tree object:
		$this->pagetree = t3lib_div::makeInstance('localPageTree');
		$this->pagetree->ext_IconMode = $BE_USER->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
		$this->pagetree->ext_showPageId = $BE_USER->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$this->pagetree->thisScript = 'alt_db_navframe.php';
		$this->pagetree->addField('alias');
		$this->pagetree->addField('shortcut');
		$this->pagetree->addField('shortcut_mode');
		$this->pagetree->addField('mount_pid');
		$this->pagetree->addField('mount_pid_ol');
		$this->pagetree->addField('nav_hide');
		$this->pagetree->addField('url');

			// Setting highlight mode:
		$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');

			// Create template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType='xhtml_trans';

			// Setting backPath
		$this->doc->backPath = $BACK_PATH;

			// Setting JavaScript for menu.
		$this->doc->JScode=$this->doc->wrapScriptTags(
	($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'

		// Function, loading the list frame from navigation tree:
	function jumpTo(id,linkObj,highLightID)	{	//
		var theUrl = top.TS.PATH_typo3+top.currentSubScript+"?id="+id;

		if (top.condensedMode)	{
			top.content.document.location=theUrl;
		} else {
			parent.list_frame.document.location=theUrl;
		}

		'.($this->doHighlight?'hilight_row("web",highLightID);':'').'

		'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}').'
		return false;
	}

		// Call this function, refresh_nav(), from another script in the backend if you want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
		// See t3lib_BEfunc::getSetUpdateSignal()
	function refresh_nav()	{	//
		window.setTimeout("_refresh_nav();",0);
	}
	function _refresh_nav()	{	//
		document.location="'.$this->pagetree->thisScript.'?unique='.time().'";
	}

		// Highlighting rows in the page tree:
	function hilight_row(frameSetModule,highLightID) {	//

			// Remove old:
		theObj = document.getElementById(top.fsMod.navFrameHighlightedID[frameSetModule]);
		if (theObj)	{
			theObj.style.backgroundColor="";
		}

			// Set new:
		top.fsMod.navFrameHighlightedID[frameSetModule] = highLightID;
		theObj = document.getElementById(highLightID);
		if (theObj)	{
			theObj.style.backgroundColor="'.t3lib_div::modifyHTMLColorAll($this->doc->bgColor,-20).'";
		}
	}

	'.($this->cMR?"jumpTo(top.fsMod.recentIds['web'],'');":'').';
		');

			// Click menu code is added:
		$CMparts=$this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];
	}

	/**
	 * Main function, rendering the browsable page tree
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$CLIENT;

			// Produce browse-tree:
		$tree = $this->pagetree->getBrowsableTree();

		$this->content='';
		$this->content.=$this->doc->startPage('Page tree');
		$this->content.=$tree;
		$this->content.='
			<p class="c-refresh">
				<a href="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'">'.
				'<img'.t3lib_iconWorks::skinImg('','gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'" alt="" />'.
				$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'</a>
			</p>
			<br />';

			// Adding highlight - JavaScript
		if ($this->doHighlight)	$this->content .=$this->doc->wrapScriptTags('
			hilight_row("",top.fsMod.navFrameHighlightedID["web"]);
		');
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
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
