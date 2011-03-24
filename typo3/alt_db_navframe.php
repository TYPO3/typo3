<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
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
require_once('init.php');
require('template.php');
require_once('class.webpagetree.php');


/**
 * Main script class for the page tree navigation frame
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_db_navframe {

		// Internal:
	var $content;
	var $pagetree;

	/**
	 * document template object
	 *
	 * @var template
	 */
	var $doc;
	var $active_tempMountPoint = 0;		// Temporary mount point (record), if any
	var $backPath;

		// Internal, static: GPvar:
	var $currentSubScript;
	var $cMR;
	var $setTempDBmount;			// If not '' (blank) then it will clear (0) or set (>0) Temporary DB mount.

	var $template;					// a static HTML template, usually in templates/alt_db_navframe.html
	var $hasFilterBox;				//depends on userTS-setting

	/**
	 * Initialiation of the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;

			// Setting backPath
		$this->backPath = $BACK_PATH;

			// Setting GPvars:
		$this->cMR = t3lib_div::_GP('cMR');
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->setTempDBmount = t3lib_div::_GP('setTempDBmount');

			// look for User setting
		$this->hasFilterBox = !$BE_USER->getTSConfigVal('options.pageTree.hideFilter');

			// Create page tree object:
		$this->pagetree = t3lib_div::makeInstance('webPageTree');
		$this->pagetree->ext_IconMode = $BE_USER->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
		$this->pagetree->ext_showPageId = $BE_USER->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$this->pagetree->ext_showNavTitle = $BE_USER->getTSConfigVal('options.pageTree.showNavTitle');
		$this->pagetree->ext_separateNotinmenuPages = $BE_USER->getTSConfigVal('options.pageTree.separateNotinmenuPages');
		$this->pagetree->ext_alphasortNotinmenuPages = $BE_USER->getTSConfigVal('options.pageTree.alphasortNotinmenuPages');
		$this->pagetree->thisScript = 'alt_db_navframe.php';
		$this->pagetree->addField('alias');
		$this->pagetree->addField('shortcut');
		$this->pagetree->addField('shortcut_mode');
		$this->pagetree->addField('mount_pid');
		$this->pagetree->addField('mount_pid_ol');
		$this->pagetree->addField('nav_hide');
		$this->pagetree->addField('nav_title');
		$this->pagetree->addField('url');

			// Temporary DB mounts:
		$this->initializeTemporaryDBmount();
	}


	/**
	 * initialization for the visual parts of the class
	 * Use template rendering only if this is a non-AJAX call
	 *
	 * @return	void
	 */
	public function initPage() {
 		global $BE_USER;

			// Setting highlight mode:
		$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');

			// If highlighting is active, define the CSS class for the active item depending on the workspace
		if ($this->doHighlight) {
			$hlClass = ($BE_USER->workspace === 0 ? 'active' : 'active active-ws wsver'.$BE_USER->workspace);
		}

			// Create template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/alt_db_navframe.html');
		$this->doc->showFlashMessages = FALSE;

			// get HTML-Template


			// Adding javascript code for AJAX (prototype), drag&drop and the pagetree as well as the click menu code
		$this->doc->getDragDropCode('pages');
		$this->doc->getContextMenuCode();
		$this->doc->getPageRenderer()->loadScriptaculous('effects');
		$this->doc->getPageRenderer()->loadExtJS();

		if ($this->hasFilterBox) {
			$this->doc->getPageRenderer()->addJsFile('js/pagetreefiltermenu.js');
		}

		$this->doc->JScode .= $this->doc->wrapScriptTags(
		($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'
		// setting prefs for pagetree and drag & drop
		'.($this->doHighlight ? 'Tree.highlightClass = "'.$hlClass.'";' : '').'

		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank)	{ //
			var theUrl = top.TS.PATH_typo3 + top.currentSubScript ;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			'.($this->doHighlight ? 'Tree.highlightActiveItem("web", highlightID + "_" + bank);' : '').'
			'.(!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) linkObj.blur(); ').'
			return false;
		}
		'.($this->cMR?"jumpTo(top.fsMod.recentIds['web'],'');":'').

			($this->hasFilterBox ? 'var TYPO3PageTreeFilter = new PageTreeFilter();' : '') . '

		');

		$this->doc->bodyTagId = 'typo3-pagetree';
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


			// Outputting Temporary DB mount notice:
		if ($this->active_tempMountPoint)	{
			$flashText = '
				<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => 0))) . '">' .
				$LANG->sl('LLL:EXT:lang/locallang_core.xml:labels.temporaryDBmount',1) .
				'</a>		<br />' .
				$LANG->sl('LLL:EXT:lang/locallang_core.xml:labels.path',1) . ': <span title="' .
				htmlspecialchars($this->active_tempMountPoint['_thePathFull']) . '">' .
				htmlspecialchars(t3lib_div::fixed_lgd_cs($this->active_tempMountPoint['_thePath'],-50)).
				'</span>
			';

			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$flashText,
				'',
				t3lib_FlashMessage::INFO
			);


			$this->content.= $flashMessage->render();
		}

			// Outputting page tree:
		$this->content .= '<div id="PageTreeDiv">'.$tree.'</div>';

			// Adding javascript for drag & drop activation and highlighting
		$this->content .= $this->doc->wrapScriptTags('
			'.($this->doHighlight ? 'Tree.highlightActiveItem("",top.fsMod.navFrameHighlightedID["web"]);' : '').'
			'.(!$this->doc->isCMlayers() ? 'Tree.activateDragDrop = false;' : 'Tree.registerDragDropHandlers();')
		);

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'IMG_RESET'     => t3lib_iconWorks::getSpriteIcon('actions-document-close', array(
						'id' =>'treeFilterReset',
						'alt'=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.resetFilter'),
						'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.resetFilter')
					)),
			'WORKSPACEINFO' => $this->getWorkspaceInfo(),
			'CONTENT'       => $this->content
		);
		$subparts = array();

		if (!$this->hasFilterBox) {
			$subparts['###SECOND_ROW###'] = '';
		}
			// Build the <body> for the module
		$this->content = $this->doc->startPage('TYPO3 Page Tree');
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers, $subparts);
		$this->content.= $this->doc->endPage();

		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $LANG;

		$buttons = array(
			'csh' => '',
			'new_page' => '',
			'refresh' => '',
			'filter' => '',
		);

			// New Page
		$onclickNewPageWizard = 'top.content.list_frame.location.href=top.TS.PATH_typo3+\'db_new.php?pagesOnly=1&amp;id=\'+Tree.pageID;';
		$buttons['new_page'] = '<a href="#" onclick="' . $onclickNewPageWizard . '" title="' . $LANG->sL('LLL:EXT:cms/layout/locallang.xml:newPage', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-page-new') .
			'</a>';

			// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')) . '" title="' . $LANG->sL('LLL:EXT:lang/locallang_core.php:labels.refresh', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-system-refresh') .
			'</a>';

			// CSH
		$buttons['csh'] = str_replace('typo3-csh-inline','typo3-csh-inline show-right',t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'pagetree', $GLOBALS['BACK_PATH'], '', TRUE));

			// Filter
		if ($this->hasFilterBox) {
			$buttons['filter'] = '<a href="#" id="tree-toolbar-filter-item">' . t3lib_iconWorks::getSpriteIcon('actions-system-tree-search-open', array('title'=> $LANG->sL('LLL:EXT:cms/layout/locallang.xml:labels.filter', 1))) . '</a>';
		}

		return $buttons;
	}

	/**
	 * Create the workspace information
	 *
	 * @return	string	HTML containing workspace info
	 */
	protected function getWorkspaceInfo() {

		if (t3lib_extMgm::isLoaded('workspaces') && ($GLOBALS['BE_USER']->workspace !== 0 || $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.onlineWorkspaceInfo'))) {
			$wsTitle = htmlspecialchars(tx_Workspaces_Service_Workspaces::getWorkspaceTitle($GLOBALS['BE_USER']->workspace));

			$workspaceInfo = '
				<div class="bgColor4 workspace-info">' .
					 t3lib_iconWorks::getSpriteIcon(
						'apps-toolbar-menu-workspace',
						array(
							'title' => $wsTitle,
							'onclick' => 'top.goToModule(\'web_WorkspacesWorkspaces\');',
							'style' => 'cursor:pointer;'
						)
					) .
					$wsTitle .
				'</div>
			';
		}

		return $workspaceInfo;
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
		$GLOBALS['BE_USER']->setAndSaveSessionData('pageTree_temporaryMountPoint',intval($pageId));
	}


	/**********************************
	 *
	 * AJAX Calls
	 *
	 **********************************/

	/**
	 * Makes the AJAX call to expand or collapse the pagetree.
	 * Called by typo3/ajax.php
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	$ajaxObj: The TYPO3AJAX object of this request
	 * @return	void
	 */
	public function ajaxExpandCollapse($params, $ajaxObj) {
		global $LANG;

		$this->init();
		$tree = $this->pagetree->getBrowsableTree();
		if (!$this->pagetree->ajaxStatus) {
			$ajaxObj->setError($tree);
		} else	{
			$ajaxObj->addContent('tree', $tree);
		}
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_db_navframe.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_db_navframe.php']);
}


// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$SOBE = t3lib_div::makeInstance('SC_alt_db_navframe');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
