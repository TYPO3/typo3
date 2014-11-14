<?php
namespace TYPO3\CMS\Workspaces\Backend\ToolbarItems;

/**
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to render the workspace selector
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class WorkspaceSelectorToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface {

	/**
	 * @var \TYPO3\CMS\Backend\Controller\BackendController
	 */
	protected $backendReference;

	/**
	 * @var bool|null
	 */
	protected $checkAccess = NULL;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
		$this->backendReference->getPageRenderer()->addInlineLanguageLabel('Workspaces.workspaceTitle', \TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($GLOBALS['BE_USER']->workspace));
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		if ($this->checkAccess === NULL) {
			/** @var \TYPO3\CMS\Workspaces\Service\WorkspaceService $wsService */
			$wsService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
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
	 * @return string workspace selector as HTML select
	 */
	public function render() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.workspace', TRUE);
		$this->backendReference->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Workspaces/Toolbar/WorkspacesMenu');

		$index = 0;
		/** @var \TYPO3\CMS\Workspaces\Service\WorkspaceService $wsService */
		$wsService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
		$availableWorkspaces = $wsService->getAvailableWorkspaces();
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
			$workspaceSections[$sectionName][] = '<li' . $classValue . '>' . '<a href="backend.php?changeWorkspace=' . $workspaceId . '" data-workspaceid="' . $workspaceId . '" class="tx-workspaces-switchlink">' . $iconState . ' ' . htmlspecialchars($label) . '</a></li>';
		}

		if (!empty($workspaceSections['top'])) {
			// Add the "Go to workspace module" link
			// if there is at least one icon on top and if the access rights are there
			if ($GLOBALS['BE_USER']->check('modules', 'web_WorkspacesWorkspaces')) {
				$workspaceSections['top'][] = '<li><a target="content" data-module="web_WorkspacesWorkspaces" class="tx-workspaces-modulelink">' . $stateUncheckedIcon . ' ' . $GLOBALS['LANG']->getLL('bookmark_workspace', TRUE) . '</a></li>';
			}
		} else {
			// no items on top (= no workspace to work in)
			$workspaceSections['top'][] = '<li>' . $stateUncheckedIcon . ' ' . $GLOBALS['LANG']->getLL('bookmark_noWSfound', TRUE) . '</li>';
		}

		$workspaceMenu = array(
			'<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-workspace', array('title' => $title)) . '</a>',
			'<ul class="dropdown-menu" role="menu">' ,
				implode(LF, $workspaceSections['top']),
				(!empty($workspaceSections['items']) ? '<li class="divider"></li>' : ''),
				implode(LF, $workspaceSections['items']),
			'</ul>'
		);

		return implode(LF, $workspaceMenu);
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * This should not contain the "class" or "id" attribute.
	 * Use the methods for setting these attributes
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return '';
	}

	/**
	 * Return attribute id name
	 *
	 * @return string The name of the ID attribute
	 */
	public function getIdAttribute() {
		return 'workspace-selector-menu';
	}

	/**
	 * Returns extra classes
	 *
	 * @return array
	 */
	public function getExtraClasses() {
		return array();
	}

	/**
	 * Get dropdown
	 *
	 * @return bool
	 */
	public function getDropdown() {
		return TRUE;
	}

	/**
	 * Position relative to others
	 *
	 * @return int
	 */
	public function getIndex() {
		return 40;
	}

}
