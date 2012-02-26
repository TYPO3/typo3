<?php
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

/**
 * Icon for plugin wizard
 */
class tx_indexed_search_pi_wizicon {

	/**
	 * Adds the indexed_search pi1 wizard icon
	 *
	 * @param array $wizardItems Input array with wizard items for plugins
	 * @return array Modified input array, having the item for indexed_search pi1 added.
	 */
	public function proc($wizardItems) {
		$wizardItems['plugins_tx_indexed_search'] = array(
			'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('indexed_search') . 'pi/ce_wiz.png',
			'title' => $GLOBALS['LANG']->sL('LLL:EXT:indexed_search/pi/locallang.xlf:pi_wizard_title'),
			'description' => $GLOBALS['LANG']->sL('LLL:EXT:indexed_search/pi/locallang.xlf:pi_wizard_description'),
			'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=indexed_search'
		);
		return $wizardItems;
	}

}
