<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

/**
 * Handle a record that has no tabs.
 *
 * This container is called by FullRecordContainer and just wraps the output
 * of PaletteAndSingleContainer in some HTML.
 */
class NoTabsContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		/** @var PaletteAndSingleContainer $paletteAndSingleContainer */
		$paletteAndSingleContainer = GeneralUtility::makeInstance(PaletteAndSingleContainer::class);
		$paletteAndSingleContainer->setGlobalOptions($this->globalOptions);
		$resultArray = $paletteAndSingleContainer->render();
		$resultArray['html'] = '<div class="tab-content">' . $resultArray['html'] . '</div>';
		return $resultArray;
	}

}