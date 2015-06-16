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
	 * @var string Extension context
	 */
	protected $extension = 'backend';

	/**
	 * @var string Template file for the dropdown menu
	 */
	protected $templateFile = '';

	/**
	 * @var StandaloneView
	 */
	protected $standaloneView = NULL;

	/**
	 * Constructor
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct() {
		if (empty($this->templateFile)) {
			throw new \InvalidArgumentException('The template file for class "' . get_class($this) . '" is not set.', 1434530382);
		}

		$extPath = ExtensionManagementUtility::extPath($this->extension);
		/* @var $view StandaloneView */
		$this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
		$this->standaloneView->setTemplatePathAndFilename($extPath . 'Resources/Private/Templates/ToolbarMenu/' . $this->templateFile);
		$this->standaloneView->setPartialRootPaths(array(
			$extPath . 'Resources/Private/Partials/ToolbarMenu/'
		));
	}

	/**
	 * @return StandaloneView
	 */
	protected function getStandaloneView() {
		$request = $this->standaloneView->getRequest();
		$request->setControllerExtensionName($this->extension);

		return $this->standaloneView;
	}
}
