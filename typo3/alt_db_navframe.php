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
 * Page navigation tree for the Web module
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
 *  192: class SC_alt_db_navframe
 *  210:     function init()
 *  313:     function main()
 *  387:     function printContent()
 *
 *              SECTION: Temporary DB mounts
 *  415:     function initializeTemporaryDBmount()
 *  449:     function settingTemporaryMountPoint($pageId)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


$BACK_PATH = '';
require('init.php');
require('template.php');
require_once('class.webpagetree.php');


/**
 * Main script class for the page tree navigation frame
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_db_navframe {

		// Internal:
	var $content;
	var $pagetree;
	var $doc;
	var $active_tempMountPoint = 0;		// Temporary mount point (record), if any
	var $backPath;

		// Internal, static: GPvar:
	var $ajax;							// Is set, if an AJAX call should be handled.
	var $currentSubScript;
	var $cMR;
	var $setTempDBmount;			// If not '' (blank) then it will clear (0) or set (>0) Temporary DB mount.

	/**
	 * Initialiation of the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;

			// Setting backPath
		$this->backPath = $BACK_PATH;
		$this->doc->backPath = $BACK_PATH;

			// Setting GPvars:
		$this->ajax = t3lib_div::_GP('ajax');
		$this->cMR = t3lib_div::_GP('cMR');
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->setTempDBmount = t3lib_div::_GP('setTempDBmount');

			// Create page tree object:
		$this->pagetree = t3lib_div::makeInstance('webPageTree');
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

			// Temporary DB mounts:
		$this->initializeTemporaryDBmount();

			// Use template rendering only if this is a non-AJAX call:
		if (!$this->ajax) {
				// Setting highlight mode:
			$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');

				// If highlighting is active, define the CSS class for the active item depending on the workspace
			if ($this->doHighlight) {
				if ($BE_USER->workspace === 0) $hlClass = 'active';
				else $hlClass = 'active active-ws wsver'.$BE_USER->workspace; 
			}

				// Create template object:
			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->docType = 'xhtml_trans';

				// Adding javascript code for AJAX (prototype), drag&drop and the pagetree
			$this->doc->JScode  = '
			<script type="text/javascript" src="'.$this->backPath.'contrib/prototype/prototype.js"></script>
			<script type="text/javascript" src="'.$this->backPath.'tree.js"></script>'."\n";

			$this->doc->JScode .= $this->doc->wrapScriptTags(
			($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'
			// setting prefs for pagetree and drag & drop
			Tree.thisScript    = "'.$this->pagetree->thisScript.'";
			'.($this->doHighlight ? 'Tree.highlightClass = "'.$hlClass.'";' : '').'

			DragDrop.changeURL = "'.$this->backPath.'alt_clickmenu.php";
			DragDrop.backPath  = "'.t3lib_div::shortMD5(''.'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']).'";
			DragDrop.table     = "pages";

			// Function, loading the list frame from navigation tree:
			function jumpTo(id, linkObj, highlightID, bank)	{ //
				var theUrl = top.TS.PATH_typo3 + top.currentSubScript + "?id=" + id;
				top.fsMod.currentBank = bank;

				if (top.condensedMode) top.content.location.href = theUrl;
				else                   parent.list_frame.location.href=theUrl;

				'.($this->doHighlight ? 'Tree.highlightActiveItem("web", highlightID + "_" + bank);' : '').'
				'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) linkObj.blur(); ').'
				return false;
			}
			'.($this->cMR?"jumpTo(top.fsMod.recentIds['web'],'');":'').'
			');

				// Click menu code is added:
			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.= $CMparts[0];
			$this->doc->postCode.= $CMparts[2];
		}
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

			// Output only the tree if this is an AJAX call:
		if ($this->ajax) {
			$this->content = $LANG->csConvObj->utf8_encode($tree, $LANG->charSet);
			return;
		}

			// Start page:
		$this->content = $this->doc->startPage('TYPO3 Page Tree');
		
			// Outputting workspace info
		if ($GLOBALS['BE_USER']->workspace!==0 || $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.onlineWorkspaceInfo'))	{
			switch($GLOBALS['BE_USER']->workspace)	{
				case 0:
					$wsTitle = '&nbsp;'.$this->doc->icons(2).'['.$LANG->sL('LLL:EXT:lang/locallang_misc.xml:shortcut_onlineWS',1).']';
				break;
				case -1:
					$wsTitle = '['.$LANG->sL('LLL:EXT:lang/locallang_misc.xml:shortcut_offlineWS',1).']';
				break;
				default:
					$wsTitle = '['.$GLOBALS['BE_USER']->workspace.'] '.htmlspecialchars($GLOBALS['BE_USER']->workspaceRec['title']);
				break;
			}

			$this->content.= '
				<div class="bgColor4 workspace-info">'.
					'<a href="'.htmlspecialchars('mod/user/ws/index.php').'" target="content">'.
					'<img'.t3lib_iconWorks::skinImg('','gfx/i/sys_workspace.png','width="18" height="16"').' align="top" alt="" />'.
					'</a>'.$wsTitle.'
				</div>
			';
		}

			// Outputting Temporary DB mount notice:
		if ($this->active_tempMountPoint)	{
			$this->content.= '
				<div class="bgColor4 c-notice">
					<img'.t3lib_iconWorks::skinImg('','gfx/icon_note.gif','width="18" height="16"').' align="top" alt="" />'.
					'<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => 0))).'">'.
					$LANG->sl('LLL:EXT:lang/locallang_core.php:labels.temporaryDBmount',1).
					'</a><br/>
					'.$LANG->sl('LLL:EXT:lang/locallang_core.php:labels.path',1).': <span title="'.htmlspecialchars($this->active_tempMountPoint['_thePathFull']).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($this->active_tempMountPoint['_thePath'],-50)).'</span>
				</div>
			';
		}

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
		$this->content.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'pagetree', $GLOBALS['BACK_PATH']);

			// Adding javascript for drag & drop activation and highlighting
		$this->content .=$this->doc->wrapScriptTags('
			'.($this->doHighlight ? 'Tree.highlightActiveItem("",top.fsMod.navFrameHighlightedID["web"]);' : '').'
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
			header('X-JSON: ('.($this->pagetree->ajaxStatus?'true':'false').')');
			header('Content-type: text/html; charset=utf-8');
			// If it's the regular call to fully output the tree:
		} else {
			$this->content.= $this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);
		}
		echo $this->content;
	}





	/**********************************
	 *
	 * Temporary DB mounts
	 *
	 **********************************/

	/**
	 * Getting temporary DB mount
	 *
	 * @return	void
	 */
	function initializeTemporaryDBmount(){
		global $BE_USER;

			// Set/Cancel Temporary DB Mount:
		if (strlen($this->setTempDBmount))	{
			$set = t3lib_div::intInRange($this->setTempDBmount,0);
			if ($set>0 && $BE_USER->isInWebMount($set))	{	// Setting...:
				$this->settingTemporaryMountPoint($set);
			} else {	// Clear:
				$this->settingTemporaryMountPoint(0);
			}
		}

			// Getting temporary mount point ID:
		$temporaryMountPoint = intval($BE_USER->getSessionData('pageTree_temporaryMountPoint'));

			// If mount point ID existed and is within users real mount points, then set it temporarily:
		if ($temporaryMountPoint > 0 && $BE_USER->isInWebMount($temporaryMountPoint))	{
			if ($this->active_tempMountPoint = t3lib_BEfunc::readPageAccess($temporaryMountPoint, $BE_USER->getPagePermsClause(1))) {
				$this->pagetree->MOUNTS = array($temporaryMountPoint);
			}
			else {
				// Clear temporary mount point as we have no access to it any longer
				$this->settingTemporaryMountPoint(0);
			}
		}
	}


	/**
	 * Setting temporary page id as DB mount
	 *
	 * @param	integer		The page id to set as DB mount
	 * @return	void
	 */
	function settingTemporaryMountPoint($pageId)	{
		global $BE_USER;

			// Setting temporary mount point ID:
		$BE_USER->setAndSaveSessionData('pageTree_temporaryMountPoint',intval($pageId));
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