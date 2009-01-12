<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Folder tree in the File main module.
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class localFolderTree extends t3lib_folderTree
 *   81:     function localFolderTree()
 *   92:     function wrapIcon($icon,&$row)
 *  121:     function wrapTitle($title,$row,$bank=0)
 *
 *
 *  146: class SC_alt_file_navframe
 *  163:     function init()
 *  253:     function main()
 *  284:     function printContent()
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$BACK_PATH='';
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_foldertree.php');


/**
 * Extension class for the t3lib_filetree class, needed for drag and drop functionality
 *
 * @author	Sebastian Kurfuerst <sebastian@garbage-group.de>
 * @package TYPO3
 * @subpackage core
 * @see class t3lib_browseTree
 */
class localFolderTree extends t3lib_folderTree {

	var $ext_IconMode;

	/**
	 * Calls init functions
	 *
	 * @return	void
	 */
	function localFolderTree() {
		parent::t3lib_folderTree();
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param	string		Icon IMG code
	 * @param	array		Data row for element.
	 * @return	string		Page icon
	 */
	function wrapIcon($icon,&$row)	{

			// Add title attribute to input icon tag
		$theFolderIcon = $this->addTagAttributes($icon,($this->titleAttrib ? $this->titleAttrib.'="'.$this->getTitleAttrib($row).'"' : ''));

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$theFolderIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theFolderIcon,$row['path'],'',0);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$this->getId($row).'\','.$this->bank.');';
			$theFolderIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$theFolderIcon.'</a>';
		}
			// Wrap icon in a drag/drop span.
		$spanOnDrag = htmlspecialchars('return dragElement("'.$this->getJumpToParam($row).'", "'.$row['uid'].'")');
		$spanOnDrop = htmlspecialchars('return dropElement("'.$this->getJumpToParam($row).'")');
		$dragDropIcon = '<span id="dragIconID_'.$row['uid'].'" ondragstart="'.$spanOnDrag.'" onmousedown="'.$spanOnDrag.'" onmouseup="'.$spanOnDrop.'">'.$theFolderIcon.'</span>';

		return $dragDropIcon;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title,$row,$bank=0)	{
		$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$this->getId($row).'\','.$bank.');';
		$CSM = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['useOnContextMenuHandler'])	{
			$CSM = ' oncontextmenu="'.htmlspecialchars($GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon('',$row['path'],'',0,'&bank='.$this->bank,'',TRUE)).'"';
		}
		$theFolderTitle='<a href="#" onclick="'.htmlspecialchars($aOnClick).'"'.$CSM.'>'.$title.'</a>';

			// Wrap title in a drag/drop span.
		$spanOnDrag = htmlspecialchars('return dragElement("'.$this->getJumpToParam($row).'","'.$row['uid'].'")');
		$spanOnDrop = htmlspecialchars('return dropElement("'.$this->getJumpToParam($row).'")');
		$dragDropTitle = '<span id="dragTitleID_'.$row['uid'].'" ondragstart="'.$spanOnDrag.'" onmousedown="'.$spanOnDrag.'" onmouseup="'.$spanOnDrop.'">'.$theFolderTitle.'</span>';
		return $dragDropTitle;
	}
}



/**
 * Main script class for rendering of the folder tree
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_file_navframe {

		// Internal, dynamic:
	var $content;		// Content accumulates in this variable.
	var $foldertree;	// Folder tree object.
	var $doc;			// Template object.

		// Internal, static: GPvar:
	var $currentSubScript;
	var $cMR;


	/**
	 * Initialiation of the script class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$CLIENT;

			// Setting GPvars:
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->cMR = t3lib_div::_GP('cMR');

			// Create folder tree object:
		$this->foldertree = t3lib_div::makeInstance('localFolderTree');
		$this->foldertree->ext_IconMode = $BE_USER->getTSConfigVal('options.folderTree.disableIconLinkToContextmenu');
		$this->foldertree->thisScript = 'alt_file_navframe.php';

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
	function jumpTo(id,linkObj,highLightID,bank)	{	//
		var theUrl = top.TS.PATH_typo3 + top.currentSubScript ;
		if (theUrl.indexOf("?") != -1) {
			theUrl += "&id=" + id
		} else {
			theUrl += "?id=" + id		    	
		}	top.fsMod.currentBank = bank;

		if (top.condensedMode)	{
			top.content.location.href=theUrl;
		} else {
			parent.list_frame.location.href=theUrl;
		}

        '.($this->doHighlight?'hilight_row("file",highLightID+"_"+bank);':'').'
		'.(!$CLIENT['FORMSTYLE'] ? '' : 'if (linkObj) {linkObj.blur();}').'
		return false;
	}


		// Call this function, refresh_nav(), from another script in the backend if you want to refresh the navigation frame (eg. after having changed a page title or moved pages etc.)
		// See t3lib_BEfunc::getSetUpdateSignal()
	function refresh_nav()	{	//
		window.setTimeout("_refresh_nav();",0);
	}
	function _refresh_nav()	{	//
		window.location.href="'.$this->pagetree->thisScript.'?unique='.time().'";
	}

		// Highlighting rows in the folder tree:
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

	'.($this->cMR?"jumpTo(top.fsMod.recentIds['file'],'');":'').';
		');

			// Click menu code is added:
		$CMparts=$this->doc->getContextMenuCode();
		$this->doc->bodyTagAdditions = $CMparts[1];
		$this->doc->JScode.=$CMparts[0];
		$this->doc->postCode.= $CMparts[2];

			// Drag and Drop code is added:
		$DDparts=$this->doc->getDragDropCode('folders');
			// ignore the $DDparts[1] for now
		$this->doc->JScode.= $DDparts[0];
		$this->doc->postCode.= $DDparts[2];
	}

	/**
	 * Main function, rendering the folder tree
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$CLIENT;

			// Produce browse-tree:
		$tree = $this->foldertree->getBrowsableTree();

		$this->content = '';
		$this->content.= $this->doc->startPage('Folder tree');
		$this->content.= $tree;
		$refreshUrl = t3lib_div::getIndpEnv('REQUEST_URI');
		$this->content.= '
			<p class="c-refresh">
				<a href="'.htmlspecialchars($refreshUrl).'">'.
				'<img'.t3lib_iconWorks::skinImg('','gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'" alt="" />'.
				'</a><a href="'.htmlspecialchars($refreshUrl).'">'.
				$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'</a>
			</p>
			<br />';
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'filetree', $GLOBALS['BACK_PATH']);

			// Adding highlight - JavaScript
		if ($this->doHighlight) $this->content .=$this->doc->wrapScriptTags('
			hilight_row("",top.fsMod.navFrameHighlightedID["file"]);
		');
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_file_navframe.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_file_navframe.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_file_navframe');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
