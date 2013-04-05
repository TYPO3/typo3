<?php
namespace TYPO3\CMS\Opendocs\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Benjamin Mack <mack@xnos.org>
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
 * Adding a list of all open documents of a user to the backend.php
 *
 * @author Benjamin Mack <benni@typo3.org>
 * @author Ingo Renner <ingo@typo3.org>
 */
class OpendocsController implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	/**
	 * reference back to the backend object
	 *
	 * @var TYPO3backend
	 */
	protected $backendReference;

	protected $openDocs;

	protected $recentDocs;

	protected $EXTKEY = 'opendocs';

	/**
	 * Constructor, loads the documents from the user control
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
		$this->loadDocsFromUserSession();
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		$conf = $GLOBALS['BE_USER']->getTSConfig('backendToolbarItem.tx_opendocs.disabled');
		return $conf['value'] == 1 ? FALSE : TRUE;
	}

	/**
	 * Loads the opened and recently opened documents from the user
	 *
	 * @return void
	 */
	public function loadDocsFromUserSession() {
		list($this->openDocs, ) = $GLOBALS['BE_USER']->getModuleData('alt_doc.php', 'ses');
		$this->recentDocs = $GLOBALS['BE_USER']->getModuleData('opendocs::recent');
	}

	/**
	 * Renders the toolbar item and the initial menu
	 *
	 * @return string The toolbar item including the initial menu content as HTML
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$this->addCssToBackend();
		$numDocs = count($this->openDocs);
		$opendocsMenu = array();
		$title = $GLOBALS['LANG']->getLL('toolbaritem', TRUE);

		// Toolbar item icon
		$opendocsMenu[] = '<a href="#" class="toolbar-item">';
		$opendocsMenu[] = '<input type="text" id="tx-opendocs-counter" disabled="disabled" value="' . $numDocs . '" />';
		$opendocsMenu[] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-opendocs', array('title' => $title)) . '</a>';

		// Toolbar item menu and initial content
		$opendocsMenu[] = '<div class="toolbar-item-menu" style="display: none;">';
		$opendocsMenu[] = $this->renderMenu();
		$opendocsMenu[] = '</div>';
		return implode(LF, $opendocsMenu);
	}

	/**
	 * renders the pure contents of the menu
	 *
	 * @return string The menu's content
	 */
	public function renderMenu() {
		$openDocuments = $this->openDocs;
		$recentDocuments = $this->recentDocs;
		$entries = array();
		$content = '';
		if (count($openDocuments)) {
			$entries[] = '<tr><th colspan="3">' . $GLOBALS['LANG']->getLL('open_docs', TRUE) . '</th></tr>';
			$i = 0;
			foreach ($openDocuments as $md5sum => $openDocument) {
				$i++;
				$entries[] = $this->renderMenuEntry($openDocument, $md5sum, FALSE, $i == 1);
			}
		}
		// If there are "recent documents" in the list, add them
		if (count($recentDocuments)) {
			$entries[] = '<tr><th colspan="3">' . $GLOBALS['LANG']->getLL('recent_docs', TRUE) . '</th></tr>';
			$i = 0;
			foreach ($recentDocuments as $md5sum => $recentDocument) {
				$i++;
				$entries[] = $this->renderMenuEntry($recentDocument, $md5sum, TRUE, $i == 1);
			}
		}
		if (count($entries)) {
			$content = '<table class="list" cellspacing="0" cellpadding="0" border="0">' . implode('', $entries) . '</table>';
		} else {
			$content = '<div class="no-docs">' . $GLOBALS['LANG']->getLL('no_docs', TRUE) . '</div>';
		}
		return $content;
	}

	/**
	 * Returns the recent documents list as an array
	 *
	 * @return array All recent documents as list-items
	 */
	public function renderMenuEntry($document, $md5sum, $isRecentDoc = FALSE, $isFirstDoc = FALSE) {
		$table = $document[3]['table'];
		$uid = $document[3]['uid'];
		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, $uid);
		if (!is_array($record)) {
			// Record seems to be deleted
			return '';
		}
		$label = htmlspecialchars(strip_tags(\TYPO3\CMS\Core\Utility\GeneralUtility::htmlspecialchars_decode($document[0])));
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $record);
		$link = $GLOBALS['BACK_PATH'] . 'alt_doc.php?' . $document[2];
		$pageId = intval($document[3]['uid']);
		if ($document[3]['table'] !== 'pages') {
			$pageId = intval($document[3]['pid']);
		}
		$firstRow = '';
		if ($isFirstDoc) {
			$firstRow = ' first-row';
		}
		if (!$isRecentDoc) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE);
			// Open document
			$closeIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close');
			$entry = '
				<tr class="opendoc' . $firstRow . '">
					<td class="icon">' . $icon . '</td>
					<td class="label"><a href="#" onclick="jump(unescape(\'' . htmlspecialchars($link) . '\'), \'web_list\', \'web\', ' . $pageId . '); TYPO3BackendOpenDocs.toggleMenu(); return false;" target="content">' . $label . '</a></td>
					<td class="close" onclick="return TYPO3BackendOpenDocs.closeDocument(\'' . $md5sum . '\');">' . $closeIcon . '</td>
				</tr>';
		} else {
			// Recently used document
			$entry = '
				<tr class="recentdoc' . $firstRow . '">
					<td class="icon">' . $icon . '</td>
					<td class="label" colspan="2"><a href="#" onclick="jump(unescape(\'' . htmlspecialchars($link) . '\'), \'web_list\', \'web\', ' . $pageId . '); TYPO3BackendOpenDocs.toggleMenu(); return false;" target="content">' . $label . '</a></td>
				</tr>';
		}
		return $entry;
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="tx-opendocs-menu"';
	}

	/**
	 * Adds the neccessary javascript to the backend
	 *
	 * @return void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->EXTKEY) . 'Resources/Public/JavaScript/opendocs.js'
		);
	}

	/**
	 * Adds the necessary CSS to the backend
	 *
	 * @return void
	 */
	protected function addCssToBackend() {
		$this->backendReference->addCssFile(
			'opendocs',
				\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->EXTKEY) . '/Resources/Public/Css/opendocs.css'
		);
	}

	/*******************
	 ***    HOOKS    ***
	 *******************/
	/**
	 * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal, calls a JS function to change
	 * the number of opened documents
	 *
	 * @param array $params
	 * @param unknown_type $ref
	 * @return string list item HTML attibutes
	 */
	public function updateNumberOfOpenDocsHook(&$params, $ref) {
		$params['JScode'] = '
			if (top && top.TYPO3BackendOpenDocs) {
				top.TYPO3BackendOpenDocs.updateNumberOfDocs(' . count($this->openDocs) . ', true);
			}
		';
	}

	/******************
	 *** AJAX CALLS ***
	 ******************/
	/**
	 * Closes a document in the session and
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return string List item HTML attibutes
	 */
	public function closeDocument($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$md5sum = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('md5sum');
		if ($md5sum && isset($this->openDocs[$md5sum])) {
			// Add the document to be closed to the recent documents
			$this->recentDocs = array_merge(array($md5sum => $this->openDocs[$md5sum]), $this->recentDocs);
			// Allow a maximum of 8 recent documents
			if (count($this->recentDocs) > 8) {
				$this->recentDocs = array_slice($this->recentDocs, 0, 8);
			}
			// Remove it from the list of the open documents, and store the status
			unset($this->openDocs[$md5sum]);
			list(, $docDat) = $GLOBALS['BE_USER']->getModuleData('alt_doc.php', 'ses');
			$GLOBALS['BE_USER']->pushModuleData('alt_doc.php', array($this->openDocs, $docDat));
			$GLOBALS['BE_USER']->pushModuleData('opendocs::recent', $this->recentDocs);
		}
		$this->renderAjax($params, $ajaxObj);
	}

	/**
	 * Renders the menu so that it can be returned as response to an AJAX call
	 *
	 * @param array $params Array of parameters from the AJAX interface, currently unused
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj Object of type TYPO3AJAX
	 * @return void
	 */
	public function renderAjax($params = array(), \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj = NULL) {
		$menuContent = $this->renderMenu();
		$ajaxObj->addContent('opendocsMenu', $menuContent);
	}

}

?>