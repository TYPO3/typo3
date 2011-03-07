<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Ritter <info@steffen-ritter.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Builds a t3lib_tree_Tca_DatabaseTreeDataProvider object based on some TCA configuration
 *
 * @author Steffen Ritter <info@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib_tree
 */
class t3lib_tree_Tca_DataProviderFactory {

	/**
	 * Gets the data provider, depending on TCA configuration
	 *
	 * @static
	 * @param array $tcaConfiguration
	 * @return t3lib_tree_Tca_DatabaseTreeDataProvider
	 * @throws InvalidArgumentException
	 */
	public static function getDataProvider(array $tcaConfiguration, $table, $field, $currentValue) {
		$dataProvider = NULL;

		if (!isset($tcaConfiguration['internal_type'])) {
			$tcaConfiguration['internal_type'] = 'db';
		}

		if ($tcaConfiguration['internal_type'] == 'db') {
			/**
			 * @var $dataProvider t3lib_tree_Tca_DatabaseTreeDataProvider
			 */
			$dataProvider = t3lib_div::makeInstance('t3lib_tree_Tca_DatabaseTreeDataProvider');

			if (isset($tcaConfiguration['foreign_table'])) {
				$tableName = $tcaConfiguration['foreign_table'];
				$dataProvider->setTableName($tableName);

				t3lib_div::loadTCA($tableName);
			} else {
				throw new InvalidArgumentException(
					'TCA Tree configuration is invalid: "foreign_table" not set',
					1288215888
				);
			}

			if (isset($tcaConfiguration['foreign_label'])) {
				$dataProvider->setLabelField($tcaConfiguration['foreign_label']);
			} else {
				$dataProvider->setLabelField($GLOBALS['TCA'][$tableName]['ctrl']['label']);
			}
			$dataProvider->setTreeId(md5($table . '|' . $field));
			$dataProvider->setSelectedList($currentValue);
			if (isset($tcaConfiguration['treeConfig']) && is_array($tcaConfiguration['treeConfig'])) {
				$treeConfiguration = $tcaConfiguration['treeConfig'];

				if (isset($treeConfiguration['rootUid'])) {
					$dataProvider->setRootUid(intval($treeConfiguration['rootUid']));
				}

				if (isset($treeConfiguration['appearance']['expandAll'])) {
					$dataProvider->setExpandAll((boolean) $treeConfiguration['appearance']['expandAll']);
				}

				if (isset($treeConfiguration['appearance']['maxLevels'])) {
					$dataProvider->setLevelMaximum(intval($treeConfiguration['appearance']['maxLevels']));
				}

				if (isset($treeConfiguration['appearance']['nonSelectableLevels'])) {
					$dataProvider->setNonSelectableLevelList($treeConfiguration['appearance']['nonSelectableLevels']);
				} elseif (isset($treeConfiguration['rootUid'])) {
					$dataProvider->setNonSelectableLevelList('');
				}

				if (isset($treeConfiguration['childrenField'])) {
					$dataProvider->setLookupMode(t3lib_tree_tca_DatabaseTreeDataProvider::MODE_CHILDREN);
					$dataProvider->setLookupField($treeConfiguration['childrenField']);
				} elseif (isset($treeConfiguration['parentField'])) {
					$dataProvider->setLookupMode(t3lib_tree_tca_DatabaseTreeDataProvider::MODE_PARENT);
					$dataProvider->setLookupField($treeConfiguration['parentField']);
				} else {
					throw new InvalidArgumentException(
						'TCA Tree configuration is invalid: neither "childrenField" nor "parentField" is set',
						1288215889
					);
				}
			} else {
				throw new InvalidArgumentException(
					'TCA Tree configuration is invalid: "treeConfig" array is missing',
					1288215890
				);
			}

		} elseif ($tcaConfiguration['internal_type'] == 'file') {
			// Not implemented yet
			throw new InvalidArgumentException(
				'TCA Tree configuration is invalid: tree for "internal_type=file" not implemented yet',
				1288215891
			);
		} else {
			throw new InvalidArgumentException(
				'TCA Tree configuration is invalid: tree for "internal_type=' .
				$tcaConfiguration['internal_type'] .
				'" not implemented yet',
				1288215892
			);
		}

		return $dataProvider;
	}
}

?>