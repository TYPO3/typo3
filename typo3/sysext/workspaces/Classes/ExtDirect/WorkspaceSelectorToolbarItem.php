<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
	require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('backend') . 'Classes/Toolbar/ToolbarItemHookInterface.php';
}

/**
 * class to render the workspace selector
 *
 * @author 	Ingo Renner <ingo@typo3.org>
 */
class WorkspaceSelectorToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	protected $changeWorkspace;

	protected $changeWorkspacePreview;

	/**
	 * reference back to the backend object
	 *
	 * @var \TYPO3\CMS\Backend\Controller\BackendController
	 */
	protected $backendReference;

	protected $checkAccess = NULL;

	/**
	 * constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
		$this->changeWorkspace = GeneralUtility::_GP('changeWorkspace');
		$this->changeWorkspacePreview = GeneralUtility::_GP('changeWorkspacePreview');
		$pageRenderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
		$this->backendReference->addJavaScript('TYPO3.Workspaces = { workspaceTitle : \'' . addslashes(\TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($GLOBALS['BE_USER']->workspace)) . '\'};
');
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			return FALSE;
		}

		if ($this->checkAccess == NULL) {
			/** @var \TYPO3\CMS\Workspaces\Service\WorkspaceService $wsService */
			$wsService = GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
			$availableWorkspaces = $wsService->getAvailableWorkspaces();
			if (count($availableWorkspaces) > 0) {
				$this->checkAccess = TRUE;
			} else {
				$this->checkAccess = FALSE;
			}
		}
		return $this->checkAccess;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return 	string		workspace selector as HTML select
	 */
	public function render() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.workspace', TRUE);
		$this->addJavascriptToBackend();

		$index = 0;
		$availableWorkspaces = \TYPO3\CMS\Workspaces\Service\WorkspaceService::getAvailableWorkspaces();
		$activeWorkspace = (int)$GLOBALS['BE_USER']->workspace;
		$stateCheckedIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-checked');
		$stateUncheckedIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('empty-empty', array(
			'title' => $GLOBALS['LANG']->getLL('bookmark_inactive')
		));

		$workspaceSections = array(
			'top' => array(),
			'items' => array(),
		);

		foreach ($availableWorkspaces as $workspaceId => $label) {
			$workspaceId = (int)$workspaceId;
			$iconState = ($workspaceId === $activeWorkspace ? $stateCheckedIcon : $stateUncheckedIcon);
			$classValue = ($workspaceId === $activeWorkspace ? ' class="selected"' : '');
			$sectionName = ($index++ === 0 ? 'top' : 'items');
			$workspaceSections[$sectionName][] = '<li' . $classValue . '>' . '<a href="backend.php?changeWorkspace=' . $workspaceId . '" id="ws-' . $workspaceId . '" class="ws">' . $iconState . ' ' . htmlspecialchars($label) . '</a></li>';
		}

		if (count($workspaceSections['top']) > 0) {
			// Go to workspace module link
			if ($GLOBALS['BE_USER']->check('modules', 'web_WorkspacesWorkspaces')) {
				$workspaceSections['top'][] = '<li>' . '<a href="javascript:top.goToModule(\'web_WorkspacesWorkspaces\');" target="content" id="goToWsModule">' . $stateUncheckedIcon . ' ' . $GLOBALS['LANG']->getLL('bookmark_workspace', TRUE) . '</a></li>';
			}
			$workspaceSections['top'][] = '<li class="divider"></li>';
		} else {
			$workspaceSections['top'][] = '<li>' . $stateUncheckedIcon . ' ' . $GLOBALS['LANG']->getLL('bookmark_noWSfound', TRUE) . '</li>';
		}


		$workspaceMenu = array(
			'<a href="#" class="toolbar-item">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-workspace', array('title' => $title)) . '</a>',
			'<div class="toolbar-item-menu" style="display: none">' ,
				'<ul class="top">',
					implode(LF, $workspaceSections['top']),
				'</ul>',
				'<ul class="items">',
					implode(LF, $workspaceSections['items']),
				'</ul>',
			'</div>'
		);

		return implode(LF, $workspaceMenu);
	}

	/**
	 * adds the necessary JavaScript to the backend
	 *
	 * @return 	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('workspaces') . 'Resources/Public/JavaScript/workspacemenu.js');
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return 	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return 'id="workspace-selector-menu"';
	}

}


if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$GLOBALS['TYPO3backend']->addToolbarItem('workSpaceSelector', 'TYPO3\\CMS\\Workspaces\\ExtDirect\\WorkspaceSelectorToolbarItem');
}
