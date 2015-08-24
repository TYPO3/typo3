<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

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
use TYPO3\CMS\Recordlist\Browser\ElementBrowser;
use TYPO3\CMS\Recordlist\Controller\ElementBrowserController;
use TYPO3\CMS\Rtehtmlarea\SelectImage;

/**
 * Script class for the Element Browser window to select images in RTE
 */
class SelectImageController extends ElementBrowserController {

	/**
	 * Initialize controller
	 */
	protected function init() {
		parent::init();

		$lang = $this->getLanguageService();
		$lang->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_selectimagecontroller.xlf');
		$lang->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_dialogs.xlf');

		$this->mode = 'rte';
	}

	/**
	 * Get instance of ElementBrowser
	 *
	 * This method shall be overwritten in subclasses
	 *
	 * @return ElementBrowser
	 */
	protected function getElementBrowserInstance() {
		return GeneralUtility::makeInstance(SelectImage::class);
	}

}
