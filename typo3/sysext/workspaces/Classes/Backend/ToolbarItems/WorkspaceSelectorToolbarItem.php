<?php
namespace TYPO3\CMS\Workspaces\Backend\ToolbarItems;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Backend\ToolbarItems\AbstractToolbarItem;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Class to render the workspace selector
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class WorkspaceSelectorToolbarItem extends AbstractToolbarItem implements ToolbarItemInterface {

	/**
	 * @var string Extension context
	 */
	protected $extension = 'workspaces';

	/**
	 * @var string Template file for the dropdown menu
	 */
	protected $templateFile = 'WorkspaceSelector.html';

	/**
	 * @var array
	 */
	protected $availableWorkspaces;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		/** @var WorkspaceService $wsService */
		$wsService = GeneralUtility::makeInstance(WorkspaceService::class);
		$this->availableWorkspaces = $wsService->getAvailableWorkspaces();

		$pageRenderer = $this->getPageRenderer();
		$pageRenderer->addInlineLanguageLabel('Workspaces.workspaceTitle', WorkspaceService::getWorkspaceTitle($this->getBackendUser()->workspace));
		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Toolbar/WorkspacesMenu');
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		return count($this->availableWorkspaces) > 1;
	}

	/**
	 * Render item
	 *
	 * @return string HTML
	 */
	public function getItem() {
		if (empty($this->availableWorkspaces)) {
			return '';
		}

		return IconUtility::getSpriteIcon(
			'apps-toolbar-menu-workspace',
			array(
				'title' => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.workspace', TRUE),
			)
		);
	}

	/**
	 * Get drop down
	 *
	 * @return string
	 */
	public function getDropDown() {
		$backendUser = $this->getBackendUser();
		$languageService = $this->getLanguageService();

		$index = 0;
		$activeWorkspace = (int)$backendUser->workspace;
		$stateCheckedIcon = IconUtility::getSpriteIcon('status-status-checked');
		$stateUncheckedIcon = IconUtility::getSpriteIcon('empty-empty', array(
			'title' => $languageService->getLL('bookmark_inactive')
		));

		$workspaceSections = array(
			'top' => array(),
			'items' => array(),
		);

		foreach ($this->availableWorkspaces as $workspaceId => $label) {
			$workspaceId = (int)$workspaceId;
			$iconState = ($workspaceId === $activeWorkspace ? $stateCheckedIcon : $stateUncheckedIcon);
			$classValue = ($workspaceId === $activeWorkspace ? 'selected' : '');
			$sectionName = ($index++ === 0 ? 'top' : 'items');
			$workspaceSections[$sectionName][] = array(
				'href' => 'backend.php?changeWorkspace=' . $workspaceId,
				'class' => $classValue,
				'wsid' => $workspaceId,
				'icon' => $iconState,
				'label' => $label
			);
		}

		if (!empty($workspaceSections['top'])) {
			// Add the "Go to workspace module" link
			// if there is at least one icon on top and if the access rights are there
			if ($backendUser->check('modules', 'web_WorkspacesWorkspaces')) {
				$workspaceSections['top'][] = array(
					'module' => 'web_WorkspacesWorkspaces',
					'icon' => $stateUncheckedIcon,
					'label' => $languageService->getLL('bookmark_workspace', TRUE)
				);
			}
		} else {
			// no items on top (= no workspace to work in)
			$workspaceSections['top'][] = array(
				'icon' => $stateUncheckedIcon,
				'label' => $languageService->getLL('bookmark_noWSfound', TRUE)
			);
		}

		$standaloneView = $this->getStandaloneView();
		$standaloneView->assignMultiple(array(
			'workspaceSections' => $workspaceSections
		));
		return $standaloneView->render();
	}

	/**
	 * This toolbar needs no additional attributes
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		return array();
	}

	/**
	 * This item has a drop down
	 *
	 * @return bool
	 */
	public function hasDropDown() {
		return !empty($this->availableWorkspaces);
	}

	/**
	 * Position relative to others
	 *
	 * @return int
	 */
	public function getIndex() {
		return 40;
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns current PageRenderer
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer() {
		/** @var  \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplate */
		$documentTemplate = $GLOBALS['TBE_TEMPLATE'];
		return $documentTemplate->getPageRenderer();
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
