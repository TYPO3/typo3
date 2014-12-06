<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Domain\Model\Module\BackendModule;

/**
 * User toolbar item
 */
class UserToolbarItem implements ToolbarItemInterface {

	/**
	 * Item is always enabled
	 *
	 * @return bool TRUE
	 */
	public function checkAccess() {
		return TRUE;
	}

	/**
	 * Render username
	 *
	 * @return string HTML
	 */
	public function getItem() {
		$backendUser = $this->getBackendUser();
		$languageService = $this->getLanguageService();
		$icon = IconUtility::getSpriteIcon('status-user-' . ($backendUser->isAdmin() ? 'admin' : 'backend'));

		$realName = $backendUser->user['realName'];
		$username = $backendUser->user['username'];
		$label = $realName ?: $username;
		$title = $username;

		// Switch user mode
		if ($backendUser->user['ses_backuserid']) {
			$title = $languageService->getLL('switchtouser') . ': ' . $username;
			$label = $languageService->getLL('switchtousershort') . ' ' . ($realName ? $realName . ' (' . $username . ')' : $username);
		}

		$html = array();
		$html[] = $icon;
		$html[] = '<span title="' . htmlspecialchars($title) . '">';
		$html[] = htmlspecialchars($label);
		$html[] = '<span class="caret"></span></span>';

		return implode(LF, $html);
	}

	/**
	 * Render drop down
	 *
	 * @return string HTML
	 */
	public function getDropDown() {
		$backendUser = $this->getBackendUser();
		$languageService = $this->getLanguageService();

		$dropdown = array();
		$dropdown[] = '<ul class="dropdown-list">';

		/** @var BackendModuleRepository $backendModuleRepository */
		$backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
		/** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $userModuleMenu */
		$userModuleMenu = $backendModuleRepository->findByModuleName('user');
		if ($userModuleMenu != FALSE && $userModuleMenu->getChildren()->count() > 0) {
			foreach ($userModuleMenu->getChildren() as $module) {
				/** @var BackendModule $module */
				$moduleIcon = $module->getIcon();
				$dropdown[] ='<li'
					. ' id="' . htmlspecialchars($module->getName()) . '"'
					. ' class="typo3-module-menu-item submodule mod-' . htmlspecialchars($module->getName()) . '" '
					. ' data-modulename="' . htmlspecialchars($module->getName()) . '"'
					. ' data-navigationcomponentid="' . htmlspecialchars($module->getNavigationComponentId()) . '"'
					. ' data-navigationframescript="' . htmlspecialchars($module->getNavigationFrameScript()) . '"'
					. ' data-navigationframescriptparameters="' . htmlspecialchars($module->getNavigationFrameScriptParameters()) . '"'
					. '>';
				$dropdown[] = '<a title="' . htmlspecialchars($module->getDescription()) . '" href="' . htmlspecialchars($module->getLink()) . '" class="dropdown-list-link modlink">';
				$dropdown[] = '<span class="submodule-icon typo3-app-icon"><span><span>' . ($moduleIcon['html'] ?: $moduleIcon['html']) . '</span></span></span>';
				$dropdown[] = '<span class="submodule-label">' . htmlspecialchars($module->getTitle()) . '</span>';
				$dropdown[] = '</a>';
				$dropdown[] = '</li>';
			}
			$dropdown[] = '<li class="divider"></li>';
		}

		// Logout button
		$buttonLabel = 'LLL:EXT:lang/locallang_core.xlf:' . ($backendUser->user['ses_backuserid'] ? 'buttons.exit' : 'buttons.logout');
		$dropdown[] = '<li class="reset-dropdown">';
		$dropdown[] = '<a href="logout.php" class="btn btn-danger pull-right" target="_top"><i class="fa fa-power-off"></i> ';
		$dropdown[] = $languageService->sL($buttonLabel, TRUE);
		$dropdown[] = '</a>';
		$dropdown[] = '</li>';

		$dropdown[] = '</ul>';

		return implode(LF, $dropdown);
	}

	/**
	 * Returns an additional class if user is in "switch user" mode
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		$result = array();
		if ($this->getBackendUser()->user['ses_backuserid']) {
			$result['class'] = 'su-user';
		}
		return $result;
	}

	/**
	 * This item has a drop down
	 *
	 * @return bool
	 */
	public function hasDropDown() {
		return TRUE;
	}

	/**
	 * Position relative to others
	 *
	 * @return int
	 */
	public function getIndex() {
		return 80;
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
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
