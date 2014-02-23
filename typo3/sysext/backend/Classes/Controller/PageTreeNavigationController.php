<?php
namespace TYPO3\CMS\Backend\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main script class for the page tree navigation frame
 * This is the class for rendering the "page tree" navigation frame without ExtJS, used prior to TYPO3 CMS 4.5.
 * This functionality is deprecated since TYPO3 CMS 7, and will be removed with TYPO3 CMS 8
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageTreeNavigationController {

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var \TYPO3\CMS\Backend\View\PageTreeView
	 */
	public $pagetree;

	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Temporary mount point (record), if any
	 *
	 * @var int
	 */
	public $active_tempMountPoint = 0;

	/**
	 * @var string
	 */
	public $backPath;

	/**
	 * @var string
	 */
	public $currentSubScript;

	/**
	 * @var bool
	 */
	public $cMR;

	/**
	 * If not '' (blank) then it will clear (0) or set (>0) Temporary DB mount.
	 *
	 * @var string
	 */
	public $setTempDBmount;

	/**
	 * @var string
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
	 */
	public $template;

	/**
	 * Constructor
	 */
	public function __construct() {
		GeneralUtility::deprecationLog('PageTreeNavigationController is deprecated in favor of new pagetrees');
		$GLOBALS['SOBE'] = $this;
		$GLOBALS['BACK_PATH'] = '';

		$this->init();
	}

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	protected function init() {
		// Setting backPath
		$this->backPath = $GLOBALS['BACK_PATH'];
		// Setting GPvars:
		$this->cMR = (bool)GeneralUtility::_GP('cMR');
		$this->currentSubScript = GeneralUtility::_GP('currentSubScript');
		$this->setTempDBmount = GeneralUtility::_GP('setTempDBmount');
		// Create page tree object:
		$this->pagetree = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\View\PageTreeView::class);
		$this->pagetree->ext_IconMode = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
		$this->pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$this->pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
		$this->pagetree->ext_separateNotinmenuPages = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.separateNotinmenuPages');
		$this->pagetree->ext_alphasortNotinmenuPages = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.alphasortNotinmenuPages');
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
	 * Initialization for the visual parts of the class
	 * Use template rendering only if this is a non-AJAX call
	 *
	 * @return void
	 */
	public function initPage() {
		// Setting highlight mode:
		$this->doHighlight = !$GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.disableTitleHighlight');
		// Create template object:
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/alt_db_navframe.html');
		$this->doc->showFlashMessages = FALSE;
		// Get HTML-Template

		// Adding javascript for drag & drop activation and highlighting
		$dragDropCode = 'Tree.registerDragDropHandlers();';

		// If highlighting is active, define the CSS class for the active item depending on the workspace
		if ($this->doHighlight) {
			$hlClass = $GLOBALS['BE_USER']->workspace === 0 ? 'active' : 'active active-ws wsver' . $GLOBALS['BE_USER']->workspace;
			$dragDropCode .= '
				Tree.highlightClass = "' . $hlClass . '";
				Tree.highlightActiveItem("",top.fsMod.navFrameHighlightedID["web"]);';
		}
		// Adding javascript code for drag&drop and the pagetree as well as the click menu code
		$this->doc->getDragDropCode('pages', $dragDropCode);
		$this->doc->getContextMenuCode();
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $this->doc->getPageRenderer();
		$pageRenderer->loadExtJS();
		$this->doc->JScode .= $this->doc->wrapScriptTags(($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '
		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank) { //
			var theUrl = top.TS.PATH_typo3 + top.currentSubScript ;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			' . ($this->doHighlight ? 'Tree.highlightActiveItem("web", highlightID + "_" + bank);' : '') . '
			if (linkObj) { linkObj.blur(); }
			return false;
		}
		' . ($this->cMR ? 'jumpTo(top.fsMod.recentIds[\'web\'],\'\');' : '') . '

		');
		$this->doc->bodyTagId = 'typo3-pagetree';
	}

	/**
	 * Main function, rendering the browsable page tree
	 *
	 * @return void
	 */
	public function main() {
		// Produce browse-tree:
		$tree = $this->pagetree->getBrowsableTree();
		// Outputting Temporary DB mount notice:
		if ($this->active_tempMountPoint) {
			$flashText = '
				<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(array('setTempDBmount' => 0))) . '">' . $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xlf:labels.temporaryDBmount', TRUE) . '</a>		<br />' . $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xlf:labels.path', TRUE) . ': <span title="' . htmlspecialchars($this->active_tempMountPoint['_thePathFull']) . '">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->active_tempMountPoint['_thePath'], -50)) . '</span>
			';
			$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $flashText, '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$this->content .= $flashMessage->render();
		}
		// Outputting page tree:
		$this->content .= '<div id="PageTreeDiv">' . $tree . '</div>';
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'WORKSPACEINFO' => $this->getWorkspaceInfo(),
			'CONTENT' => $this->content
		);
		$subparts = array();
		// Build the <body> for the module
		$this->content = $this->doc->startPage('TYPO3 Page Tree');
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers, $subparts);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'new_page' => '',
			'refresh' => ''
		);
		// New Page
		$onclickNewPageWizard = 'top.content.list_frame.location.href=top.TS.PATH_typo3+\'db_new.php?pagesOnly=1&amp;id=\'+Tree.pageID;';
		$buttons['new_page'] = '<a href="#" onclick="' . $onclickNewPageWizard . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xlf:newPage', TRUE) . '">' . IconUtility::getSpriteIcon('actions-page-new') . '</a>';
		// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.refresh', TRUE) . '">' . IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';
		// CSH
		$buttons['csh'] = str_replace('typo3-csh-inline', 'typo3-csh-inline show-right', BackendUtility::cshItem('xMOD_csh_corebe', 'pagetree'));
		return $buttons;
	}

	/**
	 * Create the workspace information
	 *
	 * @return string HTML containing workspace info
	 */
	protected function getWorkspaceInfo() {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces') && ($GLOBALS['BE_USER']->workspace !== 0 || $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.onlineWorkspaceInfo'))) {
			$wsTitle = htmlspecialchars(\TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($GLOBALS['BE_USER']->workspace));
			$workspaceInfo = '
				<div class="bgColor4 workspace-info">' . IconUtility::getSpriteIcon('apps-toolbar-menu-workspace', array(
				'title' => $wsTitle,
				'onclick' => 'top.goToModule(\'web_WorkspacesWorkspaces\');',
				'style' => 'cursor:pointer;'
			)) . $wsTitle . '</div>
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
	 * @return void
	 */
	public function initializeTemporaryDBmount() {
		// Set/Cancel Temporary DB Mount:
		if ((string)$this->setTempDBmount !== '') {
			$set = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->setTempDBmount, 0);
			if ($set > 0 && $GLOBALS['BE_USER']->isInWebMount($set)) {
				// Setting...:
				$this->settingTemporaryMountPoint($set);
			} else {
				// Clear:
				$this->settingTemporaryMountPoint(0);
			}
		}
		// Getting temporary mount point ID:
		$temporaryMountPoint = (int)$GLOBALS['BE_USER']->getSessionData('pageTree_temporaryMountPoint');
		// If mount point ID existed and is within users real mount points, then set it temporarily:
		if ($temporaryMountPoint > 0 && $GLOBALS['BE_USER']->isInWebMount($temporaryMountPoint)) {
			if ($this->active_tempMountPoint = BackendUtility::readPageAccess($temporaryMountPoint, $GLOBALS['BE_USER']->getPagePermsClause(1))) {
				$this->pagetree->MOUNTS = array($temporaryMountPoint);
			} else {
				// Clear temporary mount point as we have no access to it any longer
				$this->settingTemporaryMountPoint(0);
			}
		}
	}

	/**
	 * Setting temporary page id as DB mount
	 *
	 * @param int $pageId The page id to set as DB mount
	 * @return void
	 */
	public function settingTemporaryMountPoint($pageId) {
		$GLOBALS['BE_USER']->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$pageId);
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
	 * @param array $params Additional parameters (not used here)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The AjaxRequestHandler object of this request
	 * @return void
	 */
	public function ajaxExpandCollapse($params, $ajaxObj) {
		$this->init();
		$tree = $this->pagetree->getBrowsableTree();
		if (!$this->pagetree->ajaxStatus) {
			$ajaxObj->setError($tree);
		} else {
			$ajaxObj->addContent('tree', $tree);
		}
	}

}
