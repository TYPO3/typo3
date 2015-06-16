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
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Domain\Model\Module\BackendModule;

/**
 * Help toolbar item
 */
class HelpToolbarItem extends AbstractToolbarItem implements ToolbarItemInterface {

	/**
	 * @var string Template file for the dropdown menu
	 */
	protected $templateFile = 'Help.html';

	/**
	 * @var \SplObjectStorage<BackendModule>
	 */
	protected $helpModuleMenu = NULL;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		/** @var BackendModuleRepository $backendModuleRepository */
		$backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
		/** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $userModuleMenu */
		$helpModuleMenu = $backendModuleRepository->findByModuleName('help');
		if ($helpModuleMenu && $helpModuleMenu->getChildren()->count() > 0) {
			$this->helpModuleMenu = $helpModuleMenu;
		}
	}

	/**
	 * Users see this if a module is available
	 *
	 * @return bool TRUE
	 */
	public function checkAccess() {
		$result = $this->helpModuleMenu ? TRUE : FALSE;
		return $result;
	}

	/**
	 * Render help icon
	 *
	 * @return string Help
	 */
	public function getItem() {
		return $this->helpModuleMenu->getIcon();
	}

	/**
	 * Render drop down
	 *
	 * @return string
	 */
	public function getDropDown() {
		$dropdown = array();
		foreach ($this->helpModuleMenu->getChildren() as $module) {
			/** @var BackendModule $module */
			$dropdown[] = array(
				'id' => $module->getName(),
				'navigation' => array(
					'componentId' => $module->getNavigationComponentId(),
					'frameScript' => $module->getNavigationFrameScript(),
					'frameScriptParameters' => $module->getNavigationFrameScriptParameters(),
				),
				'href' => $module->getLink(),
				'description' => $module->getDescription(),
				'icon' => $module->getIcon(),
				'label' => $module->getTitle()
			);
		}

		$standaloneView = $this->getStandaloneView();
		$standaloneView->assign('dropdown', $dropdown);
		return $standaloneView->render();
	}

	/**
	 * No additional attributes needed.
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		return array('class' => 'typo3-module-menu-group');
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
		return 70;
	}

}
