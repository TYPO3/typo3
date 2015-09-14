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
 * On "new" command, initialize new database row with default data
 */
class DatabaseRowInitializeNew implements FormDataProviderInterface {

	/**
	 * Initialize new row with default values from various sources
	 * There are 4 sources of default values. Mind the order, the last takes precedence.
	 *
	 * @param array $result
	 * @return array
	 */
	public function addData(array $result) {
		if ($result['command'] !== 'new') {
			return $result;
		}

		$databaseRow = array();

		$tableName = $result['tableName'];
		$tableNameWithDot = $tableName . '.';

		// Apply default values from user typo script "TCAdefaults" if any
		if (isset($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot])
			&& is_array($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot])
		) {
			foreach ($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot] as $fieldName => $fieldValue) {
				if (isset($result['vanillaTableTca']['columns'][$fieldName])) {
					$databaseRow[$fieldName] = $fieldValue;
				}
			}
		}

		// Apply defaults from pageTsConfig
		if (isset($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot])
			&& is_array($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot])
		) {
			foreach ($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot] as $fieldName => $fieldValue) {
				if (isset($result['vanillaTableTca']['columns'][$fieldName])) {
					$databaseRow[$fieldName] = $fieldValue;
				}
			}
		}

		// If a neighbor row is given (if vanillaUid was negative), field can be initialized with values
		// from neighbor for fields registered in TCA['ctrl']['useColumnsForDefaultValues'].
		if (is_array($result['neighborRow'])
			&& !empty($result['vanillaTableTca']['ctrl']['useColumnsForDefaultValues'])
		) {
			$defaultColumns = GeneralUtility::trimExplode(',', $result['vanillaTableTca']['ctrl']['useColumnsForDefaultValues'], TRUE);
			foreach ($defaultColumns as $fieldName) {
				if (isset($result['vanillaTableTca']['columns'][$fieldName])
					&& isset($result['neighborRow'][$fieldName])
				) {
					$databaseRow[$fieldName] = $result['neighborRow'][$fieldName];
				}
			}
		}

		// Apply default values from GET / POST
		// @todo: Fetch this stuff from request object as soon as modules were moved to PSR-7
		$defaultValuesFromGetPost = GeneralUtility::_GP('defVals');
		if (isset($defaultValuesFromGetPost[$tableName])
			&& is_array($defaultValuesFromGetPost[$tableName])
		) {
			foreach ($defaultValuesFromGetPost[$tableName] as $fieldName => $fieldValue) {
				if (isset($result['vanillaTableTca']['columns'][$fieldName])) {
					$databaseRow[$fieldName] = $fieldValue;
				}
			}
		}

		// Set pid to vanillaUid. This means, it *can* be negative, if the record is added relative to another record
		$databaseRow['pid'] = $result['vanillaUid'];

		$result['databaseRow'] = $databaseRow;
		return $result;
	}

}
