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

use TYPO3\CMS\Core\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Http\ControllerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileListFolderTree;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Recordlist\Browser\ElementBrowser;

/**
 * Main script class for rendering of the folder tree
 */
class FileSystemNavigationFrameController implements ControllerInterface {

	/**
	 * Content accumulates in this variable.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * @var \TYPO3\CMS\Backend\Tree\View\FolderTreeView
	 */
	public $foldertree;

	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var string
	 */
	public $currentSubScript;

	/**
	 * @var bool
	 */
	public $cMR;

	/**
	 * @var array
	 */
	protected $scopeData;

	/**
	 * @var bool
	 */
	public $doHighlight;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['SOBE'] = $this;
		$this->init();
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return Response
	 */
	public function processRequest(ServerRequestInterface $request) {
		$this->initPage();
		$this->main();
		/** @var Response $response */
		$response = GeneralUtility::makeInstance(Response::class);
		$response->getBody()->write($this->content);
		return $response;
	}

	/**
	 * Initialiation of the script class
	 *
	 * @return void
	 */
	protected function init() {
		// Setting GPvars:
		$this->currentSubScript = GeneralUtility::_GP('currentSubScript');
		$this->cMR = GeneralUtility::_GP('cMR');

		$scopeData = (string)GeneralUtility::_GP('scopeData');
		$scopeHash = (string)GeneralUtility::_GP('scopeHash');

		if (!empty($scopeData) && GeneralUtility::hmac($scopeData) === $scopeHash) {
			$this->scopeData = unserialize($scopeData);
		}

		// Create folder tree object:
		if (!empty($this->scopeData)) {
			$this->foldertree = GeneralUtility::makeInstance($this->scopeData['class']);
			$this->foldertree->thisScript = $this->scopeData['script'];
			$this->foldertree->ext_noTempRecyclerDirs = $this->scopeData['ext_noTempRecyclerDirs'];
			if ($this->foldertree instanceof ElementBrowserFolderTreeView) {
				$browser = GeneralUtility::makeInstance(ElementBrowser::class);
				$browser->mode = $this->scopeData['browser']['mode'];
				$browser->act = $this->scopeData['browser']['act'];
				$this->foldertree->setElementBrowser($browser);
			}
		} else {
			$this->foldertree = GeneralUtility::makeInstance(FileListFolderTree::class);
			$this->foldertree->thisScript = BackendUtility::getModuleUrl('file_navframe');
		}
		// Only set ext_IconMode if we are not running an ajax request from the ElementBrowser,
		// which has this property hardcoded to 1.
		if (!$this->foldertree instanceof ElementBrowserFolderTreeView::class) {
			$this->foldertree->ext_IconMode = $this->getBackendUser()->getTSConfigVal('options.folderTree.disableIconLinkToContextmenu');
		}
	}

	/**
	 * initialization for the visual parts of the class
	 * Use template rendering only if this is a non-AJAX call
	 *
	 * @return void
	 */
	public function initPage() {
		// Setting highlight mode:
		$this->doHighlight = !$this->getBackendUser()->getTSConfigVal('options.pageTree.disableTitleHighlight');
		// Create template object:
		$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$this->doc->bodyTagId = 'ext-backend-Modules-FileSystemNavigationFrame-index-php';
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/alt_file_navframe.html');
		$this->doc->showFlashMessages = FALSE;
		// Adding javascript code for drag&drop and the filetree as well as the click menu code
		$dragDropCode = '
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
			Tree.registerDragDropHandlers()';
		if ($this->doHighlight) {
			$hlClass = $this->getBackendUser()->workspace === 0 ? 'active' : 'active active-ws wsver' . $GLOBALS['BE_USER']->workspace;
			$dragDropCode .= '
			Tree.highlightClass = "' . $hlClass . '";
			Tree.highlightActiveItem("", top.fsMod.navFrameHighlightedID["file"]);
			';
		}
		// Adding javascript for drag & drop activation and highlighting
		$this->doc->getDragDropCode('folders', $dragDropCode);
		$this->doc->getContextMenuCode();

		// Setting JavaScript for menu.
		$this->doc->JScode .= $this->doc->wrapScriptTags(($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '
		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank) {
			var theUrl = top.currentSubScript;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			' . ($this->doHighlight ? 'Tree.highlightActiveItem("file", highlightID + "_" + bank);' : '') . '
			if (linkObj) { linkObj.blur(); }
			return false;
		}
		' . ($this->cMR ? ' jumpTo(top.fsMod.recentIds[\'file\'],\'\');' : ''));
	}

	/**
	 * Main function, rendering the folder tree
	 *
	 * @return void
	 */
	public function main() {
		// Produce browse-tree:
		$tree = $this->foldertree->getBrowsableTree();
		// Outputting page tree:
		$this->content .= $tree;
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'CONTENT' => $this->content
		);
		$subparts = array();
		// Build the <body> for the module
		$this->content = $this->doc->startPage('TYPO3 Folder Tree');
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markers, $subparts);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
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
			'refresh' => ''
		);
		// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' . IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';
		// CSH
		$buttons['csh'] = str_replace('typo3-csh-inline', 'typo3-csh-inline show-right', BackendUtility::cshItem('xMOD_csh_corebe', 'filetree'));
		return $buttons;
	}

	/**********************************
	 *
	 * AJAX Calls
	 *
	 **********************************/
	/**
	 * Makes the AJAX call to expand or collapse the foldertree.
	 * Called by typo3/ajax.php
	 *
	 * @param array $params Additional parameters (not used here)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The AjaxRequestHandler object of this request
	 * @return void
	 */
	public function ajaxExpandCollapse($params, $ajaxObj) {
		$tree = $this->foldertree->getBrowsableTree();
		if ($this->foldertree->getAjaxStatus() === FALSE) {
			$ajaxObj->setError($tree);
		} else {
			$ajaxObj->addContent('tree', $tree);
		}
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
