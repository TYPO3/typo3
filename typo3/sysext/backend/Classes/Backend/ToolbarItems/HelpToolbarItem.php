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
 * Help toobar item
 */
class HelpToolbarItem implements ToolbarItemInterface {

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
	 * @return string Help
	 */
	public function render() {
		/** @var \TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository $backendModuleRepository */
		$backendModuleRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository::class);
		/** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $userModuleMenu */
		$helpModuleMenu = $backendModuleRepository->findByModuleName('help');

		if ($helpModuleMenu === FALSE || $helpModuleMenu->getChildren()->count() < 1) {
			return '';
		}

		$dropdown = array();

		$dropdown[] = '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
		$dropdown[] = '<span class="fa fa-fw fa-question-circle"></span>';
		$dropdown[] = '</a>';

		$dropdown[] = '<ul class="dropdown-menu">';
		foreach ($helpModuleMenu->getChildren() as $module) {
			$moduleIcon = $module->getIcon();
			$dropdown[] = '
				<li id="' . $module->getName() . '" class="t3-menuitem-submodule submodule mod-' . $module->getName() . '" data-modulename="' . $module->getName() . '" data-navigationcomponentid="' . $module->getNavigationComponentId() . '" data-navigationframescript="' . $module->getNavigationFrameScript() . '" data-navigationframescriptparameters="' . $module->getNavigationFrameScriptParameters() . '">
					<a title="' .$module->getDescription() . '" href="' . $module->getLink() . '" class="modlink">
						<span class="submodule-icon">' . ($moduleIcon['html'] ?: $moduleIcon['html']) . '</span>
						<span class="submodule-label">' . $module->getTitle() . '</span>
					</a>
				</li>';
		}
		$dropdown[] = '</ul>';

		return implode(LF, $dropdown);
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
		return 'topbar-help-menu';
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
		return 70;
	}

}
