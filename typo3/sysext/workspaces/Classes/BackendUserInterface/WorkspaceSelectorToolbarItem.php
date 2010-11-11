<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Ingo Renner <ingo@typo3.org>
*  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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

if(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
	require_once(PATH_typo3 . 'interfaces/interface.backend_toolbaritem.php');
}

/**
 * class to render the workspace selector
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package Workspaces
 * @subpackage BackendUserInterface
 */
class WorkspaceSelectorToolbarItem implements backend_toolbarItem {

	protected $changeWorkspace;
	protected $changeWorkspacePreview;

	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	protected $backendReference;

	protected $checkAccess = NULL;

	/**
	 * constructor
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		$this->backendReference       = $backendReference;
		$this->changeWorkspace        = t3lib_div::_GP('changeWorkspace');
		$this->changeWorkspacePreview = t3lib_div::_GP('changeWorkspacePreview');

		$pageRenderer = t3lib_div::makeInstance('t3lib_pageRenderer');
		$this->backendReference->addJavaScript("TYPO3.Workspaces = { workspaceTitle : '" . tx_Workspaces_Service_Workspaces::getWorkspaceTitle($GLOBALS['BE_USER']->workspace) . "'};\n");
	}

	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @see		typo3/alt_shortcut.php
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
		if (t3lib_extMgm::isLoaded('workspaces')) {
			if ($this->checkAccess == NULL) {
					$availableWorkspaces = $this->getAvailableWorkspaces();
					if (count($availableWorkspaces) > 1) {
						$this->checkAccess = TRUE;
					} else {
						$this->checkAccess = FALSE;
					}
			}
			return $this->checkAccess;
		}
		return FALSE;
	}

	/**
	 * retrieves the available workspaces from the database and checks whether
	 * they're available to the current BE user
	 *
	 * @return	array	array of worspaces available to the current user
	 */
	protected function getAvailableWorkspaces() {
		$availableWorkspaces = array();

			// add default workspaces
		if($GLOBALS['BE_USER']->checkWorkspace(array('uid' => 0))) {
			$availableWorkspaces[0] = '['.$GLOBALS['LANG']->getLL('bookmark_onlineWS').']';
		}
		if ($GLOBALS['BE_USER']->checkWorkspace(array('uid' => -1))) {
			$availableWorkspaces[-1] = '['.$GLOBALS['LANG']->getLL('bookmark_offlineWS').']';
		}

			// add custom workspaces (selecting all, filtering by BE_USER check):
		$customWorkspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, title, adminusers, members, reviewers',
			'sys_workspace',
			'pid = 0'.t3lib_BEfunc::deleteClause('sys_workspace'),
			'',
			'title'
		);
		if(count($customWorkspaces)) {
			foreach($customWorkspaces as $workspace) {
				if($GLOBALS['BE_USER']->checkWorkspace($workspace)) {
					$availableWorkspaces[$workspace['uid']] = $workspace['uid'] . ': ' . htmlspecialchars($workspace['title']);
				}
			}
		}

		return $availableWorkspaces;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return	string		workspace selector as HTML select
	 */
	public function render() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:toolbarItems.workspace', true);
		$this->addJavascriptToBackend();
		$availableWorkspaces = $this->getAvailableWorkspaces();
		$workspaceMenu       = array();

		$stateCheckedIcon = t3lib_iconWorks::getSpriteIcon('status-status-checked');

		$stateUncheckedIcon = t3lib_iconWorks::getSpriteIcon('empty-empty', array(
			'title' => $GLOBALS['LANG']->getLL('bookmark_inactive')
		));

		$workspaceMenu[] = '<a href="#" class="toolbar-item">' .
			t3lib_iconWorks::getSpriteIcon('apps-toolbar-menu-workspace', array('title' => $title)) .
				'</a>';
		$workspaceMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';

		if (count($availableWorkspaces)) {
			foreach($availableWorkspaces as $workspaceId => $label) {
				$selected = '';
				$icon = $stateUncheckedIcon;
				if((int) $GLOBALS['BE_USER']->workspace === $workspaceId) {
					$selected = ' class="selected"';
					$icon = $stateCheckedIcon;
				}

				$workspaceMenu[] = '<li' . $selected . '>' . $icon .
					' <a href="backend.php?changeWorkspace=' .
					intval($workspaceId) . '" id="ws-' . intval($workspaceId) .
					'" class="ws">' . $label . '</a></li>';
			}
		} else {
			$workspaceMenu[] = '<li>' . $stateUncheckedIcon . ' ' .
				$GLOBALS['LANG']->getLL('bookmark_noWSfound', true) .
				'</li>';
		}

			// frontend preview toggle
		$frontendPreviewActiveIcon = $stateUncheckedIcon;
		if ($GLOBALS['BE_USER']->user['workspace_preview']) {
			$frontendPreviewActiveIcon = $stateCheckedIcon;
		}

		$workspaceMenu[] = '<li class="divider">' . $frontendPreviewActiveIcon .
			'<a href="backend.php?changeWorkspacePreview=' .
			($GLOBALS['BE_USER']->user['workspace_preview'] ? '0' : '1') .
			'" id="frontendPreviewToggle">' . $GLOBALS['LANG']->getLL('bookmark_FEPreview', true) . '</a></li>';

			// go to workspace module link
		$workspaceMenu[] = '<li>' . $stateUncheckedIcon . ' ' .
			'<a href="javascript:top.goToModule(\'web_WorkspacesWorkspaces\');" target="content" id="goToWsModule">' .
			' '. $GLOBALS['LANG']->getLL('bookmark_workspace', true) . '</a></li>';

		$workspaceMenu[] = '</ul>';

		return implode(LF, $workspaceMenu);
	}

	/**
	 * adds the necessary JavaScript to the backend
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(t3lib_extMgm::extRelPath('workspaces') . 'Resources/Public/JavaScript/workspacemenu.js');
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="workspace-selector-menu"';
	}
}


if(!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$GLOBALS['TYPO3backend']->addToolbarItem('workSpaceSelector', 'WorkspaceSelectorToolbarItem');
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/BackendUserInterface/WorkspaceSelectorToolbarItem.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/BackendUserInterface/WorkspaceSelectorToolbarItem.php']);
}
?>