<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;

/**
 * User toobar item
 */
class UserToolbarItem implements ToolbarItemInterface {

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference TYPO3 backend object reference
	 * @throws \UnexpectedValueException
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		return TRUE;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return string Workspace selector as HTML select
	 */
	public function render() {
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-user-' . ($GLOBALS['BE_USER']->isAdmin() ? 'admin' : 'backend'));

		$realName = $GLOBALS['BE_USER']->user['realName'];
		$username = $GLOBALS['BE_USER']->user['username'];
		$label = $realName ?: $username;
		$title = $username;

		// Superuser mode
		if ($GLOBALS['BE_USER']->user['ses_backuserid']) {
			$title = $GLOBALS['LANG']->getLL('switchtouser') . ': ' . $username;
			$label = $GLOBALS['LANG']->getLL('switchtousershort') . ' ' . ($realName ? $realName . ' (' . $username . ')' : $username);
		}


		$html = array();
		$html[] = '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
		$html[] = $icon . '<span title="' . htmlspecialchars($title) . '">' . htmlspecialchars($label) . ' <span class="caret"></span></span>';
		$html[] = '</a>';

		$html[] = '<ul class="dropdown-menu" role="menu">';

		/** @var \TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository $backendModuleRepository */
		$backendModuleRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository::class);
		/** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $userModuleMenu */
		$userModuleMenu = $backendModuleRepository->findByModuleName('user');
		if ($userModuleMenu != FALSE && $userModuleMenu->getChildren()->count() > 0) {
			foreach ($userModuleMenu->getChildren() as $module) {
				$moduleIcon = $module->getIcon();
				$html[] = '
					<li id="' . $module->getName() . '" class="t3-menuitem-submodule submodule mod-' . $module->getName() . '" data-modulename="' . $module->getName() . '" data-navigationcomponentid="' . $module->getNavigationComponentId() . '" data-navigationframescript="' . $module->getNavigationFrameScript() . '" data-navigationframescriptparameters="' . $module->getNavigationFrameScriptParameters() . '">
						<a title="' .$module->getDescription() . '" href="' . $module->getLink() . '" class="modlink">
							<span class="submodule-icon">' . ($moduleIcon['html'] ?: $moduleIcon['html']) . '</span>
							<span class="submodule-label">' . $module->getTitle() . '</span>
						</a>
					</li>';
			}
			$html[] = '<li class="divider"></li>';
		}

		// logout button
		$buttonLabel = 'LLL:EXT:lang/locallang_core.xlf:' . ($GLOBALS['BE_USER']->user['ses_backuserid'] ? 'buttons.exit' : 'buttons.logout');
		$html[] = '<li><a href="logout.php" target="_top">' . $GLOBALS['LANG']->sL($buttonLabel, TRUE) . '</a></li>';

		$html[] = '</ul>';

		return implode(LF, $html);
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
		if ($GLOBALS['BE_USER']->user['ses_backuserid']) {
			return 'su-user';
		}
	}

	/**
	 * Return attribute id name
	 *
	 * @return string The name of the ID attribute
	 */
	public function getIdAttribute() {
		return 'topbar-user-menu';
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
		return 80;
	}

}
