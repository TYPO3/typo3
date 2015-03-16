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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Render system info toolbar item
 */
abstract class AbstractToolbarItem {

	/**
	 * @var StandaloneView
	 */
	protected $standaloneView = NULL;

	public function __construct() {
		$extPath = ExtensionManagementUtility::extPath('backend');
		/* @var $view StandaloneView */
		$this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
		$this->standaloneView->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/ToolbarMenu/' . static::TOOLBAR_MENU_TEMPLATE);
	}

	/**
	 * @param string $extension Set the extension context (required for shorthand locallang.xlf references)
	 * @return StandaloneView
	 */
	protected function getStandaloneView($extension = NULL) {
		if (!empty($extension)) {
			$request = $this->standaloneView->getRequest();
			$request->setControllerExtensionName($extension);
		}
		return $this->standaloneView;
	}
}
