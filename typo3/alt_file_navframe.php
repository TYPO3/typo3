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

$BACK_PATH = '';
require('init.php');
require('template.php');
require_once('class.filelistfoldertree.php');


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
	var $backPath;

		// Internal, static: GPvar:
	var $ajax;							// Is set, if an AJAX call should be handled.
	var $currentSubScript;
	var $cMR;


	/**
	 * Initialiation of the script class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$CLIENT;

			// Setting backPath
		$this->backPath = $BACK_PATH;
		$this->doc->backPath = $BACK_PATH;

			// Setting GPvars:
		$this->ajax = t3lib_div::_GP('ajax');
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->cMR = t3lib_div::_GP('cMR');

			// Create folder tree object:
		$this->foldertree = t3lib_div::makeInstance('filelistFolderTree');
		$this->foldertree->ext_IconMode = $BE_USER->getTSConfigVal('options.folderTree.disableIconLinkToContextmenu');
		$this->foldertree->thisScript = 'alt_file_navframe.php';

			// Use template rendering only if this is a non-AJAX call:
		if (!$this->ajax) {
				// Setting highlight mode:
			$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');

				// Create template object:
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->docType = 'xhtml_trans';

				// Adding javascript code for AJAX (prototype), drag&drop and the pagetree
			$this->doc->JScode  = '
			<script type="text/javascript" src="'.$this->backPath.'contrib/prototype/prototype.js"></script>
			<script type="text/javascript" src="'.$this->backPath.'tree.js"></script>'."\n";

				// Setting JavaScript for menu.
			$this->doc->JScode .= $this->doc->wrapScriptTags(
			($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'

			// setting prefs for pagetree and drag & drop
			Tree.thisScript    = "'.$this->foldertree->thisScript.'";
			DragDrop.changeURL = "'.$this->backPath.'alt_clickmenu.php";
			DragDrop.backPath  = "'.t3lib_div::shortMD5(''.'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']).'";
			DragDrop.table     = "folders";

			// Function, loading the list frame from navigation tree:
			function jumpTo(id, linkObj, highlightID, bank)	{
				var theUrl = top.TS.PATH_typo3 + top.currentSubScript ;
				if (theUrl.indexOf("?") != -1) {
					theUrl += "&id=" + id
				} else {
					theUrl += "?id=" + id		    	
				}	
				top.fsMod.currentBank = bank;

				if (top.condensedMode)	{
					top.content.location.href=theUrl;
				} else {
					parent.list_frame.location.href=theUrl;
				}

				'.($this->doHighlight ? 'Tree.highlightActiveItem("file", highlightID + "_" + bank);' : '').'
				'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) linkObj.blur(); ').'
				return false;
			}
			'.($this->cMR ? " jumpTo(top.fsMod.recentIds['file'],'');" : '')
			);

				// Click menu code is added:
			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];
		}
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

			// Output only the tree if this is an AJAX call:
		if ($this->ajax) {
			$this->content = $LANG->csConvObj->utf8_encode($tree, $LANG->charSet);
			return;
		}

		$this->content = $this->doc->startPage('TYPO3 Folder Tree');

			// Outputting page tree:
		$this->content.= $tree;

			// Outputting refresh-link
		$this->content.= '
			<p class="c-refresh">
				<a href="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'">'.
				'<img'.t3lib_iconWorks::skinImg('','gfx/refresh_n.gif','width="14" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'" alt="" />'.
				$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh',1).'</a>
			</p>
			<br />';

			// CSH icon:
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'filetree', $GLOBALS['BACK_PATH']);

			// Adding javascript for drag & drop activation and highlighting
		$this->content .=$this->doc->wrapScriptTags('
			'.($this->doHighlight ? 'Tree.highlightActiveItem("", top.fsMod.navFrameHighlightedID["file"]);' : '').'
			'.(!$this->doc->isCMlayers() ? 'Tree.activateDragDrop = false;' : 'Tree.registerDragDropHandlers();')
		);

	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
			// If we handle an AJAX call, send headers:
		if ($this->ajax) {
			header('X-JSON: ('.($this->foldertree->ajaxStatus?'true':'false').')');
			header('Content-type: text/html; charset=utf-8');
			// If it's the regular call to fully output the tree:
		} else {
			$this->content.= $this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);
		}
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
