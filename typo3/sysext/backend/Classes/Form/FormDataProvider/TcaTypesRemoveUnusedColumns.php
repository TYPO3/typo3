<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Remove fields from columns not in showitem or palette list.
 * This is a relatively effective performance improvement preventing other
 * providers from resolving stuff of fields that are not shown later.
 * Especially effective for fal related tables.
 */
class TcaTypesRemoveUnusedColumns implements FormDataProviderInterface {

	/**
	 * Remove unused column fields to speed up further processing.
	 *
	 * @param array $result
	 * @return array
	 */
	public function addData(array $result) {
		$recordTypeValue = $result['recordTypeValue'];
		if (empty($result['processedTca']['types'][$recordTypeValue]['showitem'])
			|| !is_string($result['processedTca']['types'][$recordTypeValue]['showitem'])
			|| empty($result['processedTca']['columns'])
			|| !is_array($result['processedTca']['columns'])
		) {
			return $result;
		}

		$showItemFieldString = $result['processedTca']['types'][$recordTypeValue]['showitem'];
		$showItemFieldArray = GeneralUtility::trimExplode(',', $showItemFieldString, TRUE);
		$shownColumnFields = [];
		foreach ($showItemFieldArray as $fieldConfigurationString) {
			$fieldConfigurationArray = GeneralUtility::trimExplode(';', $fieldConfigurationString);
			$fieldName = $fieldConfigurationArray[0];
			if ($fieldName === '--div--') {
				continue;
			}
			if ($fieldName === '--palette--') {
				if (isset($fieldConfigurationArray[2])) {
					$paletteName = $fieldConfigurationArray[2];
					if (!empty($result['processedTca']['palettes'][$paletteName]['showitem'])) {
						$paletteFields = GeneralUtility::trimExplode(',', $result['processedTca']['palettes'][$paletteName]['showitem'], TRUE);
						foreach ($paletteFields as $paletteFieldConfiguration) {
							$paletteFieldConfigurationArray = GeneralUtility::trimExplode(';', $paletteFieldConfiguration);
							$shownColumnFields[] = $paletteFieldConfigurationArray[0];
						}
					}
				}
			} else {
				$shownColumnFields[] = $fieldName;
			}
		}
		array_unique($shownColumnFields);
		$columns = array_keys($result['processedTca']['columns']);
		foreach ($columns as $column) {
			if (!in_array($column, $shownColumnFields)) {
				unset($result['processedTca']['columns'][$column]);
			}
		}

		return $result;
	}

}
